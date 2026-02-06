# Production Deployment Quick Start Guide

**Quick reference for deploying SeaCliff Dining to production**

---

## Pre-Deployment Checklist

- [ ] Server provisioned (Ubuntu 22.04 LTS, 4GB RAM, 2 CPU cores)
- [ ] Domain DNS pointing to server IP
- [ ] SSH access configured
- [ ] Production credentials ready (database, mail, API keys)

---

## 1. Server Setup (One-Time)

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 and extensions
sudo apt install -y php8.2-fpm php8.2-cli php8.2-pgsql php8.2-redis \
    php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-curl php8.2-zip \
    php8.2-gd php8.2-intl php8.2-soap

# Install services
sudo apt install -y postgresql postgresql-contrib redis-server nginx supervisor

# Install Node.js 18.x
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## 2. Database Setup

```bash
# Create database and user
sudo -u postgres psql

CREATE DATABASE seacliff_dining_production;
CREATE USER seacliff_user WITH ENCRYPTED PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE seacliff_dining_production TO seacliff_user;
\q
```

---

## 3. Configure Redis

```bash
# Edit Redis config
sudo nano /etc/redis/redis.conf

# Add password
requirepass your_redis_password

# Restart Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

---

## 4. Deploy Application

```bash
# Create directory
sudo mkdir -p /var/www/html/laravel-app
sudo chown -R $USER:www-data /var/www/html/laravel-app

# Clone or upload application
cd /var/www/html
git clone https://github.com/yourusername/laravel-app.git
cd laravel-app

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

---

## 5. Configure Environment

```bash
# Copy production environment file
cp .env.production .env

# Generate application key
php artisan key:generate

# Edit .env with production values
nano .env
```

**Critical settings to update:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_DATABASE=seacliff_dining_production
DB_USERNAME=seacliff_user
DB_PASSWORD=your_secure_password

REDIS_PASSWORD=your_redis_password

SESSION_DOMAIN=.yourdomain.com
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com

MAIL_HOST=smtp.mailgun.org
MAIL_USERNAME=your_mailgun_username
MAIL_PASSWORD=your_mailgun_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_ERROR_TO=admin@yourdomain.com

REVERB_HOST=yourdomain.com
REVERB_APP_KEY=your_generated_key

LOG_SLACK_WEBHOOK_URL=your_slack_webhook

STRIPE_PUBLIC_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
WHATSAPP_API_TOKEN=your_production_token
```

---

## 6. Database Migration

```bash
# Run migrations
php artisan migrate --force

# Seed database (if needed)
php artisan db:seed --force

# Create storage link
php artisan storage:link
```

---

## 7. Setup Queue Workers

```bash
# Copy supervisor config
sudo cp supervisor-queue-worker.conf /etc/supervisor/conf.d/laravel-queue-worker.conf

# Update paths in config if needed
sudo nano /etc/supervisor/conf.d/laravel-queue-worker.conf

# Start workers
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue-worker:*

# Verify workers are running
sudo supervisorctl status
```

---

## 8. Setup Reverb WebSocket

```bash
# Copy Reverb supervisor config
sudo cp supervisor-reverb.conf /etc/supervisor/conf.d/laravel-reverb.conf

# Start Reverb
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-reverb

# Verify Reverb is running
sudo supervisorctl status laravel-reverb
```

---

## 9. Setup Task Scheduler

```bash
# Edit crontab
sudo crontab -e

# Add this line (update path if needed)
* * * * * cd /var/www/html/laravel-app && php artisan schedule:run >> /dev/null 2>&1

# Verify crontab entry
sudo crontab -l | grep schedule:run
```

---

## 10. Configure SSL & Nginx

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Create Nginx configuration
sudo nano /etc/nginx/sites-available/laravel-app
```

**Nginx Configuration:**
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/html/laravel-app/public;
    index index.php index.html;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Strict-Transport-Security "max-age=31536000" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # WebSocket proxy for Reverb
    location /app {
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_pass http://127.0.0.1:8080;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/laravel-app /etc/nginx/sites-enabled/

# Test Nginx config
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

---

## 11. Set File Permissions

```bash
cd /var/www/html/laravel-app

# Set ownership
sudo chown -R www-data:www-data .

# Set permissions
sudo find . -type d -exec chmod 755 {} \;
sudo find . -type f -exec chmod 644 {} \;

# Set writable directories
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

---

## 12. Cache Configuration

```bash
# Cache application configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## 13. Configure Firewall

```bash
# Allow SSH, HTTP, HTTPS
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

---

## 14. Verify Deployment

### Check Services
```bash
# Queue workers
sudo supervisorctl status laravel-queue-worker:*
# Should show 3 RUNNING processes

# Reverb WebSocket
sudo supervisorctl status laravel-reverb
# Should show RUNNING

# Nginx
sudo systemctl status nginx

# Redis
redis-cli ping
# Should return PONG

# PostgreSQL
sudo systemctl status postgresql
```

### Test Application
```bash
# Test HTTPS redirect
curl -I http://yourdomain.com
# Should return 301 redirect

# Test HTTPS
curl -I https://yourdomain.com
# Should return 200 OK

# Check logs
tail -f storage/logs/laravel.log
```

### Test Scheduler
```bash
# Run manually
php artisan schedule:run

# Check scheduled tasks
php artisan schedule:list
```

### Test Backup
```bash
# Run manual backup
php artisan backup:run

# Check backup was created
ls -lh storage/app/backups/
```

---

## 15. Post-Deployment

### Monitor Logs
```bash
# Application logs
tail -f /var/www/html/laravel-app/storage/logs/laravel.log

# Queue worker logs
tail -f /var/www/html/laravel-app/storage/logs/queue-worker.log

# Reverb logs
tail -f /var/www/html/laravel-app/storage/logs/reverb.log

# Nginx error logs
sudo tail -f /var/log/nginx/error.log
```

### Test Critical Features
- [ ] User login/registration
- [ ] Order creation
- [ ] Payment processing (Stripe)
- [ ] Real-time updates (Reverb)
- [ ] Email notifications
- [ ] WhatsApp notifications (if configured)
- [ ] API endpoints
- [ ] Kitchen display system

---

## Quick Commands Reference

### Maintenance Mode
```bash
# Enable maintenance mode
php artisan down

# Disable maintenance mode
php artisan up
```

### Cache Management
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Queue Management
```bash
# Restart queue workers
sudo supervisorctl restart laravel-queue-worker:*

# Check queue worker status
sudo supervisorctl status

# View queue worker logs
tail -f storage/logs/queue-worker.log
```

### Backup Management
```bash
# Run backup
php artisan backup:run

# Clean old backups
php artisan backup:clean

# List backups
php artisan backup:list
```

### Service Management
```bash
# Restart Reverb
sudo supervisorctl restart laravel-reverb

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Restart Nginx
sudo systemctl restart nginx

# Restart Redis
sudo systemctl restart redis-server
```

---

## Troubleshooting

### Queue Workers Not Working
```bash
sudo supervisorctl restart laravel-queue-worker:*
tail -f storage/logs/queue-worker.log
```

### 500 Internal Server Error
```bash
# Check permissions
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache

# Clear caches
php artisan config:clear
php artisan cache:clear

# Check logs
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

### WebSocket Not Connecting
```bash
# Check Reverb is running
sudo supervisorctl status laravel-reverb

# Restart Reverb
sudo supervisorctl restart laravel-reverb

# Check logs
tail -f storage/logs/reverb.log
```

### Scheduler Not Running
```bash
# Check crontab entry
sudo crontab -l | grep schedule:run

# Test manually
php artisan schedule:run

# Check logs
tail -f storage/logs/laravel.log
```

---

## Security Reminders

- [ ] `APP_DEBUG=false` in production
- [ ] Strong passwords for database and Redis
- [ ] HTTPS enforced (no HTTP access)
- [ ] Firewall configured (only necessary ports open)
- [ ] Regular security updates
- [ ] Monitor error logs daily
- [ ] Backup tested and verified
- [ ] API keys secured (use environment variables)

---

## Support

For detailed information, see **DEPLOYMENT.md** (full deployment guide)

For questions or issues, contact: admin@yourdomain.com

---

**Last Updated:** February 6, 2026
