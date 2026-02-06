# Production Deployment Guide

This guide covers the complete process for deploying the SeaCliff Dining hospitality management system to a production environment.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Server Requirements](#server-requirements)
3. [Initial Server Setup](#initial-server-setup)
4. [Application Deployment](#application-deployment)
5. [Environment Configuration](#environment-configuration)
6. [Database Setup](#database-setup)
7. [Queue Workers Setup](#queue-workers-setup)
8. [Task Scheduler Setup](#task-scheduler-setup)
9. [WebSocket Server (Reverb)](#websocket-server-reverb)
10. [File Storage Configuration](#file-storage-configuration)
11. [Security Hardening](#security-hardening)
12. [SSL/TLS Certificate](#ssltls-certificate)
13. [Backup Configuration](#backup-configuration)
14. [Monitoring and Logging](#monitoring-and-logging)
15. [Post-Deployment Checklist](#post-deployment-checklist)
16. [Troubleshooting](#troubleshooting)

## Prerequisites

- Root or sudo access to the production server
- Domain name pointing to your server IP
- Basic knowledge of Linux server administration
- SSH access to the server

## Server Requirements

### Minimum Specifications
- **OS**: Ubuntu 22.04 LTS or later (recommended)
- **CPU**: 2+ cores
- **RAM**: 4GB minimum (8GB recommended)
- **Storage**: 40GB+ SSD
- **PHP**: 8.2 or higher
- **Database**: PostgreSQL 14+ (MySQL 8+ also supported)
- **Redis**: 7.0+
- **Node.js**: 18+ LTS
- **Supervisor**: For process management

### Required PHP Extensions
```bash
php8.2-cli
php8.2-fpm
php8.2-pgsql (or php8.2-mysql)
php8.2-redis
php8.2-mbstring
php8.2-xml
php8.2-curl
php8.2-zip
php8.2-bcmath
php8.2-gd
php8.2-intl
```

## Initial Server Setup

### 1. Update System Packages

```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Install Required Software

```bash
# Install PHP 8.2 and extensions
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-pgsql php8.2-redis php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-intl

# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Install Redis
sudo apt install -y redis-server

# Install Nginx
sudo apt install -y nginx

# Install Supervisor
sudo apt install -y supervisor

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3. Create Application User

```bash
sudo useradd -m -s /bin/bash deploy
sudo usermod -aG www-data deploy
```

### 4. Setup Application Directory

```bash
sudo mkdir -p /var/www/html
sudo chown -R deploy:www-data /var/www/html
```

## Application Deployment

### 1. Clone Repository

```bash
# Switch to deploy user
sudo su - deploy

# Clone the repository
cd /var/www
git clone <your-repository-url> html
cd html

# Checkout the production branch
git checkout production
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies and build assets
npm ci
npm run build
```

### 3. Set Proper Permissions

```bash
# Exit deploy user
exit

# Set ownership
sudo chown -R deploy:www-data /var/www/html

# Set directory permissions
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo find /var/www/html -type f -exec chmod 644 {} \;

# Set writable permissions for storage and cache
sudo chmod -R 775 /var/www/html/storage
sudo chmod -R 775 /var/www/html/bootstrap/cache

# Ensure web server can write to these directories
sudo chgrp -R www-data /var/www/html/storage
sudo chgrp -R www-data /var/www/html/bootstrap/cache
```

## Environment Configuration

### 1. Create Production Environment File

```bash
cd /var/www/html
cp .env.production .env
```

### 2. Generate Application Key

```bash
php artisan key:generate
```

### 3. Configure Environment Variables

Edit `.env` and update the following critical values:

```bash
# Application
APP_NAME="SeaCliff Dining"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (PostgreSQL example)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=seacliff_dining_production
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Session Security
SESSION_DOMAIN=.yourdomain.com
SESSION_SECURE_COOKIE=true

# Sanctum
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_ERROR_TO=admin@yourdomain.com

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# Reverb WebSocket
REVERB_HOST=ws.yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Logging
LOG_CHANNEL=production
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL

# Backup
BACKUP_NOTIFICATION_EMAIL=admin@yourdomain.com
```

### 4. Secure the Environment File

```bash
sudo chmod 600 /var/www/html/.env
sudo chown deploy:www-data /var/www/html/.env
```

## Database Setup

### 1. Create Database and User (PostgreSQL)

```bash
sudo -u postgres psql

-- Create database
CREATE DATABASE seacliff_dining_production;

-- Create user
CREATE USER your_db_user WITH PASSWORD 'your_secure_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE seacliff_dining_production TO your_db_user;

-- Exit psql
\q
```

### 2. Run Migrations

```bash
cd /var/www/html
php artisan migrate --force
```

### 3. Seed Database (if needed)

```bash
php artisan db:seed --force
```

## Queue Workers Setup

### 1. Copy Supervisor Configuration

```bash
sudo cp /var/www/html/deployment/supervisor/laravel-worker.conf /etc/supervisor/conf.d/
sudo cp /var/www/html/deployment/supervisor/laravel-reverb.conf /etc/supervisor/conf.d/
```

### 2. Update Supervisor Configuration

Edit `/etc/supervisor/conf.d/laravel-worker.conf` if paths need adjustment:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=60
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
stopwaitsecs=3600
```

### 3. Start Supervisor Services

```bash
# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start laravel-worker:*
sudo supervisorctl start laravel-reverb:*

# Check status
sudo supervisorctl status
```

### 4. Manage Queue Workers

```bash
# Restart workers after code deployment
sudo supervisorctl restart laravel-worker:*

# Stop workers
sudo supervisorctl stop laravel-worker:*

# View worker logs
tail -f /var/www/html/storage/logs/worker.log
```

## Task Scheduler Setup

### 1. Add Cron Entry

```bash
# Edit crontab for www-data user
sudo crontab -u www-data -e

# Add this line:
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Alternative: System-wide Cron

```bash
# Copy cron file to system cron directory
sudo cp /var/www/html/deployment/cron/laravel-scheduler /etc/cron.d/

# Set proper permissions
sudo chmod 644 /etc/cron.d/laravel-scheduler
sudo chown root:root /etc/cron.d/laravel-scheduler
```

### 3. Verify Scheduler

```bash
# Test scheduler manually
cd /var/www/html
php artisan schedule:list

# Run scheduler manually to test
php artisan schedule:run
```

## WebSocket Server (Reverb)

### 1. Nginx Configuration for WebSocket

Add to your Nginx site configuration:

```nginx
# WebSocket proxy for Reverb
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_read_timeout 86400;
}
```

### 2. Start Reverb via Supervisor

Reverb is automatically started via the supervisor configuration created earlier.

```bash
# Check Reverb status
sudo supervisorctl status laravel-reverb

# View Reverb logs
tail -f /var/www/html/storage/logs/reverb.log
```

## File Storage Configuration

### 1. Create Storage Link

```bash
cd /var/www/html
php artisan storage:link
```

### 2. Set Storage Permissions

```bash
sudo chmod -R 775 /var/www/html/storage
sudo chgrp -R www-data /var/www/html/storage
```

### 3. Optional: Configure S3 for File Storage

Update `.env`:

```bash
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

## Security Hardening

### 1. Configure Firewall

```bash
# Install UFW
sudo apt install -y ufw

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable
```

### 2. Secure Redis

Edit `/etc/redis/redis.conf`:

```bash
# Bind to localhost only
bind 127.0.0.1 ::1

# Set a password
requirepass your_redis_password

# Disable dangerous commands
rename-command FLUSHDB ""
rename-command FLUSHALL ""
rename-command CONFIG ""
```

Restart Redis:

```bash
sudo systemctl restart redis-server
```

### 3. Secure PostgreSQL

Edit `/etc/postgresql/14/main/postgresql.conf`:

```bash
# Listen on localhost only
listen_addresses = 'localhost'
```

Restart PostgreSQL:

```bash
sudo systemctl restart postgresql
```

### 4. Configure PHP-FPM Security

Edit `/etc/php/8.2/fpm/php.ini`:

```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 60
memory_limit = 256M
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.2-fpm
```

### 5. Nginx Security Configuration

Create `/etc/nginx/sites-available/seacliff-dining`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/html/public;

    index index.php index.html;

    # SSL certificates (managed by certbot)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval';" always;

    # Logging
    access_log /var/log/nginx/seacliff-access.log;
    error_log /var/log/nginx/seacliff-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # WebSocket proxy for Reverb
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 86400;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/seacliff-dining /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## SSL/TLS Certificate

### 1. Install Certbot

```bash
sudo apt install -y certbot python3-certbot-nginx
```

### 2. Obtain SSL Certificate

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### 3. Auto-renewal

Certbot automatically sets up renewal. Test it:

```bash
sudo certbot renew --dry-run
```

## Backup Configuration

### 1. Install Laravel Backup Package

```bash
composer require spatie/laravel-backup
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

### 3. Configure Backup

Edit `config/backup.php` or use environment variables in `.env`:

```bash
BACKUP_NOTIFICATION_EMAIL=admin@yourdomain.com
BACKUP_DISK=local
```

### 4. Test Backup

```bash
php artisan backup:run
```

### 5. Backup Storage Location

Backups are stored in `storage/app/backups/`. Consider:
- Setting up S3 for remote backups
- Creating off-server backup copies
- Implementing 3-2-1 backup strategy

### 6. Manual Database Backup Script

Create `/var/www/html/deployment/scripts/backup-db.sh`:

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/www/html/storage/backups"
mkdir -p $BACKUP_DIR

# PostgreSQL backup
pg_dump -U your_db_user seacliff_dining_production | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Keep only last 7 days of backups
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +7 -delete

echo "Backup completed: db_backup_$DATE.sql.gz"
```

Make executable:

```bash
chmod +x /var/www/html/deployment/scripts/backup-db.sh
```

## Monitoring and Logging

### 1. Configure Application Logging

The application is already configured to use the `production` log channel which includes:
- Daily rotating logs
- Database logging
- Critical error file
- Slack notifications for errors
- Email notifications for critical errors

### 2. Monitor Log Files

```bash
# Application logs
tail -f /var/www/html/storage/logs/laravel.log

# Critical errors
tail -f /var/www/html/storage/logs/critical.log

# Worker logs
tail -f /var/www/html/storage/logs/worker.log

# Nginx access logs
tail -f /var/log/nginx/seacliff-access.log

# Nginx error logs
tail -f /var/log/nginx/seacliff-error.log
```

### 3. Optional: Install Sentry

Update `.env`:

```bash
SENTRY_LARAVEL_DSN=https://your-sentry-dsn@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### 4. System Monitoring

Consider installing:
- **Prometheus + Grafana**: For metrics and dashboards
- **New Relic**: APM and monitoring
- **Datadog**: Infrastructure monitoring
- **UptimeRobot**: Uptime monitoring

## Post-Deployment Checklist

After deployment, verify the following:

### Application

- [ ] Application is accessible via HTTPS
- [ ] HTTP redirects to HTTPS
- [ ] SSL certificate is valid
- [ ] All pages load correctly
- [ ] Assets (CSS, JS, images) load properly
- [ ] Forms submit correctly
- [ ] Login/authentication works

### Configuration

- [ ] `.env` file has `APP_ENV=production`
- [ ] `.env` file has `APP_DEBUG=false`
- [ ] `APP_KEY` is generated and set
- [ ] Database connection works
- [ ] Redis connection works
- [ ] Mail configuration tested

### Queue & Scheduler

- [ ] Queue workers are running (`supervisorctl status`)
- [ ] Queue jobs are processing
- [ ] Scheduler cron is configured
- [ ] Scheduled tasks are running

### WebSocket

- [ ] Reverb server is running
- [ ] WebSocket connections work
- [ ] Real-time features function correctly

### Storage

- [ ] Storage link created (`php artisan storage:link`)
- [ ] File uploads work
- [ ] Storage directories are writable

### Security

- [ ] Firewall is enabled and configured
- [ ] Only necessary ports are open
- [ ] Redis is password-protected
- [ ] Database is secured
- [ ] Environment file is secured (600 permissions)

### Backup

- [ ] Backup package installed and configured
- [ ] Manual backup tested
- [ ] Automated backups scheduled
- [ ] Backup notifications configured

### Monitoring

- [ ] Log files are being written
- [ ] Error notifications work (Slack/Email)
- [ ] Monitoring tools configured (optional)

## Troubleshooting

### Queue Workers Not Processing Jobs

```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart laravel-worker:*

# Check worker logs
tail -f /var/www/html/storage/logs/worker.log

# Check Redis connection
redis-cli ping
```

### Scheduler Not Running

```bash
# Verify cron entry
sudo crontab -u www-data -l

# Run scheduler manually
cd /var/www/html
php artisan schedule:run -v

# Check scheduler log (if logging enabled)
tail -f /var/www/html/storage/logs/scheduler.log
```

### Permission Issues

```bash
# Reset permissions
sudo chown -R deploy:www-data /var/www/html
sudo chmod -R 775 /var/www/html/storage
sudo chmod -R 775 /var/www/html/bootstrap/cache
```

### 500 Internal Server Error

```bash
# Check Laravel logs
tail -f /var/www/html/storage/logs/laravel.log

# Check Nginx error log
tail -f /var/log/nginx/seacliff-error.log

# Check PHP-FPM log
tail -f /var/log/php8.2-fpm.log

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Database Connection Issues

```bash
# Test database connection
php artisan tinker
> DB::connection()->getPdo();

# Check PostgreSQL status
sudo systemctl status postgresql

# Check connection from Laravel
php artisan migrate:status
```

### WebSocket Not Connecting

```bash
# Check Reverb status
sudo supervisorctl status laravel-reverb

# Check Reverb logs
tail -f /var/www/html/storage/logs/reverb.log

# Test WebSocket endpoint
curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" \
  -H "Host: yourdomain.com" -H "Origin: https://yourdomain.com" \
  https://yourdomain.com/app
```

### Clear All Caches

```bash
cd /var/www/html
php artisan optimize:clear
composer dump-autoload
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Deployment Updates

When deploying code updates:

```bash
# 1. Put application in maintenance mode
php artisan down

# 2. Pull latest code
git pull origin production

# 3. Install/update dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear and rebuild caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart queue workers
sudo supervisorctl restart laravel-worker:*

# 7. Bring application back online
php artisan up
```

## Story 52: Production Readiness Verification

### Final Pre-Launch Checklist

#### 1. Testing (ALL MUST PASS)
- [ ] All automated tests passing (php artisan test)
- [ ] Test coverage > 80% verified
- [ ] Manual testing completed for order workflow
- [ ] Cross-browser testing completed (Chrome, Firefox, Safari, Edge)
- [ ] Mobile responsive testing completed (iOS, Android, tablets)
- [ ] Real-time updates tested (kitchen/bar displays)
- [ ] API endpoints tested (Postman collection executed)
- [ ] Performance testing completed (Laravel Telescope)
- [ ] Security audit completed (SQL injection, XSS, CSRF)

#### 2. Database
- [ ] Fresh migrations run without errors (php artisan migrate:fresh)
- [ ] Seeders verified (php artisan migrate:fresh --seed)
- [ ] All foreign key constraints verified
- [ ] Database backups configured
- [ ] Migration rollback tested

#### 3. Code Quality
- [ ] Code formatted with Laravel Pint (./vendor/bin/pint)
- [ ] No debug statements (dd, dump, var_dump, console.log)
- [ ] No commented code blocks
- [ ] All unused imports removed
- [ ] PHPStan static analysis passed

#### 4. Configuration
- [ ] .env.production configured with correct values
- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] All API keys and secrets in environment variables
- [ ] CORS settings configured
- [ ] Session/cache drivers set to redis
- [ ] Queue connection configured
- [ ] Mail settings configured
- [ ] Stripe production keys configured
- [ ] Reverb production settings configured

#### 5. Optimization
- [ ] Config cached (php artisan config:cache)
- [ ] Routes cached (php artisan route:cache)
- [ ] Views cached (php artisan view:cache)
- [ ] Events cached (php artisan event:cache)
- [ ] npm run build executed
- [ ] Assets optimized
- [ ] Database queries optimized (no N+1)

#### 6. Infrastructure
- [ ] Server requirements met
- [ ] SSL certificate installed and valid
- [ ] Firewall configured
- [ ] Queue workers running (Supervisor)
- [ ] Reverb server running
- [ ] Scheduled tasks configured (cron)
- [ ] Log rotation configured
- [ ] Monitoring tools configured (Telescope, Sentry)

#### 7. Security
- [ ] .env file permissions set to 600
- [ ] Storage directory permissions correct
- [ ] CSRF protection verified
- [ ] Rate limiting configured
- [ ] File upload security verified
- [ ] SQL injection protection verified
- [ ] XSS protection verified
- [ ] Dependencies audited (composer audit)

#### 8. Backups
- [ ] Database backup script configured
- [ ] File backup configured
- [ ] Backup restore tested
- [ ] Backup retention policy set

#### 9. Monitoring
- [ ] Error logging configured
- [ ] Application monitoring active
- [ ] Server monitoring active
- [ ] Uptime monitoring configured
- [ ] Alert notifications configured

#### 10. Documentation
- [ ] API documentation complete
- [ ] Deployment guide reviewed
- [ ] Runbook for common tasks created
- [ ] Disaster recovery plan documented
- [ ] User manual created (if applicable)

### Production Launch Day Checklist

#### Before Launch (T-1 Hour)
- [ ] Final backup of staging environment
- [ ] Review deployment steps with team
- [ ] Ensure rollback plan is ready
- [ ] Verify monitoring dashboards accessible
- [ ] Communication plan ready (support channels)

#### During Launch
- [ ] Pull latest code from main branch
- [ ] Run: composer install --optimize-autoloader --no-dev
- [ ] Run: npm ci --production && npm run build
- [ ] Run: php artisan migrate --force
- [ ] Run: php artisan config:cache
- [ ] Run: php artisan route:cache
- [ ] Run: php artisan view:cache
- [ ] Restart queue workers
- [ ] Restart Reverb server
- [ ] Restart PHP-FPM
- [ ] Restart Nginx
- [ ] Clear all caches

#### After Launch (T+15 Minutes)
- [ ] Verify application accessible
- [ ] Test user login
- [ ] Test order creation
- [ ] Test kitchen/bar displays
- [ ] Test payment processing
- [ ] Verify real-time updates working
- [ ] Check error logs
- [ ] Monitor performance metrics
- [ ] Verify queue jobs processing
- [ ] Test WebSocket connections

#### First 24 Hours
- [ ] Monitor error rates
- [ ] Watch performance metrics
- [ ] Track user feedback
- [ ] Be ready for quick rollback
- [ ] Document any issues encountered

### Release Tag
```bash
git tag -a v1.0.0 -m "Production Release v1.0.0 - Story 52 Complete"
git push origin v1.0.0
```

### Rollback Procedure
If issues arise, execute rollback:
```bash
# 1. Put application in maintenance mode
php artisan down

# 2. Revert to previous release
git checkout previous-tag

# 3. Restore database backup (if migrations were run)
# Follow database restore procedure

# 4. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. Restart services
sudo supervisorctl restart all
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx

# 6. Bring application back online
php artisan up
```

## Support and Resources

- **Laravel Documentation**: https://laravel.com/docs
- **Laravel Deployment**: https://laravel.com/docs/deployment
- **Forge (Managed Hosting)**: https://forge.laravel.com
- **Envoyer (Zero-Downtime Deployment)**: https://envoyer.io
- **Story 52 Testing Guide**: See STORY_52_COMPREHENSIVE_TESTING_GUIDE.md
- **Postman Collection**: postman/Hospitality-System-API-v1.0.0.postman_collection.json

---

**Last Updated**: 2026-02-06
**Version**: 1.0.0
**Story 52**: Production Testing and Deployment Preparation - COMPLETE
