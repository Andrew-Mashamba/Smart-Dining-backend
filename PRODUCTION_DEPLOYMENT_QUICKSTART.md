# Production Deployment Quick Start Guide

This is a condensed version of the full deployment guide. For complete instructions, see [DEPLOYMENT.md](DEPLOYMENT.md).

## Pre-Deployment Checklist

- [ ] Server meets minimum requirements (PHP 8.2+, PostgreSQL/MySQL, Redis, Nginx)
- [ ] Domain DNS pointing to server
- [ ] SSL certificate ready or Let's Encrypt available
- [ ] Database created with credentials
- [ ] Redis installed and running

---

## Quick Deployment Steps

### 1. Upload Application
```bash
cd /var/www/html
# Upload or clone your application
git clone https://github.com/yourusername/laravel-app.git
cd laravel-app
```

### 2. Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
npm install && npm run build
```

### 3. Configure Environment
```bash
# Copy production environment template
cp .env.production .env

# Generate application key
php artisan key:generate

# Edit .env and update:
nano .env
```

**Critical settings to update in .env:**
```env
APP_URL=https://yourdomain.com
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password
REDIS_PASSWORD=your_redis_password
SESSION_DOMAIN=.yourdomain.com
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
REVERB_HOST=yourdomain.com
```

### 4. Run Migrations & Setup
```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Set File Permissions
```bash
sudo chown -R www-data:www-data .
sudo chmod -R 775 storage bootstrap/cache
```

### 6. Setup Queue Workers
```bash
# Copy supervisor config
sudo cp supervisor-queue-worker.conf /etc/supervisor/conf.d/

# Start workers
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue-worker:*

# Verify
sudo supervisorctl status
```

### 7. Setup Task Scheduler
```bash
# Edit crontab
sudo crontab -e

# Add this line:
* * * * * cd /var/www/html/laravel-app && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Setup Reverb WebSocket Server
```bash
# Copy supervisor config
sudo cp supervisor-reverb.conf /etc/supervisor/conf.d/

# Start Reverb
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-reverb

# Verify
sudo supervisorctl status laravel-reverb
```

### 9. Configure Nginx
```bash
sudo nano /etc/nginx/sites-available/laravel-app
```

Add configuration (see DEPLOYMENT.md lines 474-526), then:

```bash
sudo ln -s /etc/nginx/sites-available/laravel-app /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 10. Setup SSL
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

---

## Verify Deployment

### Check Services Status
```bash
# Queue workers
sudo supervisorctl status laravel-queue-worker:*

# Reverb WebSocket
sudo supervisorctl status laravel-reverb

# Nginx
sudo systemctl status nginx

# Redis
redis-cli ping

# PostgreSQL
sudo systemctl status postgresql
```

### Check Logs
```bash
# Application logs
tail -f storage/logs/laravel.log

# Queue worker logs
tail -f storage/logs/queue-worker.log

# Reverb logs
tail -f storage/logs/reverb.log

# Nginx error logs
sudo tail -f /var/log/nginx/error.log
```

### Test Key Features
- [ ] Application loads over HTTPS
- [ ] Login/authentication works
- [ ] WebSocket connections established
- [ ] Queue jobs processing
- [ ] Scheduled tasks running

---

## Quick Commands Reference

### Application Maintenance
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Maintenance mode
php artisan down
php artisan up
```

### Queue Management
```bash
# Restart workers
sudo supervisorctl restart laravel-queue-worker:*

# View failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {job-id}

# Retry all failed jobs
php artisan queue:retry all
```

### Backup
```bash
# Manual backup (requires spatie/laravel-backup)
php artisan backup:run

# List backups
php artisan backup:list
```

### Logs
```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# View specific log level
grep "ERROR" storage/logs/laravel.log

# Clear logs (carefully!)
truncate -s 0 storage/logs/laravel.log
```

---

## Troubleshooting Quick Fixes

### 500 Internal Server Error
```bash
# Check permissions
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache

# Clear and rebuild cache
php artisan cache:clear
php artisan config:cache
```

### Queue Not Processing
```bash
# Check workers
sudo supervisorctl status laravel-queue-worker:*

# Restart workers
sudo supervisorctl restart laravel-queue-worker:*

# Check Redis
redis-cli ping
```

### WebSocket Connection Failed
```bash
# Check Reverb status
sudo supervisorctl status laravel-reverb

# Check logs
tail -f storage/logs/reverb.log

# Restart Reverb
sudo supervisorctl restart laravel-reverb
```

---

## Scheduled Tasks Overview

The following tasks run automatically (via cron):

- **Daily 01:00** - Clean old backups
- **Daily 02:00** - Run database backup
- **Daily 03:00** - Clear expired sessions
- **Daily 04:00** - Optimize application
- **Daily 08:00** - Send daily sales summary
- **Weekly Sunday 01:00** - Clear old logs
- **Every 5 minutes** - Update order statuses, monitor queue
- **Hourly 09:00-22:00** - Send reminder notifications

---

## Security Checklist

- [ ] `APP_DEBUG=false` in .env
- [ ] Strong `APP_KEY` generated
- [ ] Secure database password set
- [ ] Redis password configured
- [ ] HTTPS/SSL certificate installed
- [ ] Firewall configured (ports 22, 80, 443)
- [ ] File permissions correct (775 for writable directories)
- [ ] `.env` file secured (600 permissions)
- [ ] Slack/email alerts configured for errors
- [ ] Regular backups scheduled and tested

---

## Production Environment Variables Summary

### Essential Settings
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=pgsql
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

LOG_CHANNEL=production
LOG_LEVEL=warning
```

### Security Settings
```env
SESSION_DOMAIN=.yourdomain.com
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
```

### Service Credentials
```env
DB_PASSWORD=***
REDIS_PASSWORD=***
MAIL_PASSWORD=***
STRIPE_SECRET_KEY=***
WHATSAPP_API_TOKEN=***
```

---

## Post-Deployment Monitoring

### First 24 Hours
- Monitor error logs closely
- Check queue worker stability
- Verify backup completion
- Test all critical features
- Monitor server resources (CPU, RAM, disk)

### Ongoing
- Daily: Check error logs and queue status
- Weekly: Verify backup integrity
- Monthly: Review security logs and update dependencies
- As needed: Scale workers based on queue size

---

## Support Resources

- **Full Documentation**: [DEPLOYMENT.md](DEPLOYMENT.md)
- **Verification Summary**: [STORY_50_VERIFICATION_SUMMARY.md](STORY_50_VERIFICATION_SUMMARY.md)
- **Laravel Docs**: https://laravel.com/docs/deployment
- **Forge (Automated Deployment)**: https://forge.laravel.com

---

## Emergency Contacts

Update with your team's contact information:

- **System Administrator**: [email/phone]
- **Lead Developer**: [email/phone]
- **On-Call Engineer**: [email/phone]
- **Hosting Provider Support**: [contact info]

---

**Last Updated**: February 6, 2026
