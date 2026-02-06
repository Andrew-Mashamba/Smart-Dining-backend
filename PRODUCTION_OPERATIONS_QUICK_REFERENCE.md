# Production Operations Quick Reference

## Essential Commands

### Application Management

```bash
# Put application in maintenance mode
php artisan down

# Bring application back online
php artisan up

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches (after deployment)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Queue Workers

```bash
# Check queue worker status
sudo supervisorctl status laravel-worker:*

# Start queue workers
sudo supervisorctl start laravel-worker:*

# Stop queue workers
sudo supervisorctl stop laravel-worker:*

# Restart queue workers (after code changes)
sudo supervisorctl restart laravel-worker:*

# View queue worker logs
tail -f storage/logs/worker.log

# Check queue size
php artisan queue:size

# Retry failed jobs
php artisan queue:retry all
```

### Task Scheduler

```bash
# Manually run scheduler (for testing)
php artisan schedule:run

# List scheduled tasks
php artisan schedule:list

# View cron configuration
crontab -l

# Edit cron configuration
crontab -e
```

### Reverb WebSocket Server

```bash
# Check Reverb status
sudo supervisorctl status laravel-reverb

# Start Reverb
sudo supervisorctl start laravel-reverb

# Restart Reverb
sudo supervisorctl restart laravel-reverb

# View Reverb logs
tail -f storage/logs/reverb.log

# Manually start Reverb (for testing)
php artisan reverb:start
```

### Database Backup

```bash
# Run manual backup
php artisan backup:run

# List all backups
php artisan backup:list

# Monitor backup health
php artisan backup:monitor

# Clean old backups
php artisan backup:clean
```

### Database Operations

```bash
# Run migrations
php artisan migrate --force

# Rollback last migration
php artisan migrate:rollback --force

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Seed database
php artisan db:seed --force
```

### Logging & Monitoring

```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# View specific date logs
tail -f storage/logs/laravel-2026-02-06.log

# View critical errors only
tail -f storage/logs/critical.log

# Clear old logs
php artisan log:clear

# View Nginx error logs
sudo tail -f /var/log/nginx/error.log

# View Nginx access logs
sudo tail -f /var/log/nginx/access.log
```

### File Permissions

```bash
# Fix storage permissions
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache

# Create storage link
php artisan storage:link

# Verify permissions
ls -la storage/
ls -la bootstrap/cache/
```

### Service Management

```bash
# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Restart Nginx
sudo systemctl restart nginx

# Restart Redis
sudo systemctl restart redis-server

# Restart PostgreSQL
sudo systemctl restart postgresql

# Check service status
sudo systemctl status php8.2-fpm
sudo systemctl status nginx
sudo systemctl status redis-server
sudo systemctl status postgresql
```

### Supervisor Management

```bash
# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# View all processes
sudo supervisorctl status

# Restart all processes
sudo supervisorctl restart all

# Stop all processes
sudo supervisorctl stop all

# Start all processes
sudo supervisorctl start all
```

## Deployment Workflow

### Standard Deployment

```bash
# 1. Enable maintenance mode
php artisan down

# 2. Pull latest code
git pull origin main

# 3. Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear and rebuild caches
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart services
sudo supervisorctl restart laravel-worker:*
sudo supervisorctl restart laravel-reverb
sudo systemctl reload php8.2-fpm

# 7. Disable maintenance mode
php artisan up
```

### Zero-Downtime Deployment (Advanced)

Use Laravel Envoyer or similar tools for zero-downtime deployments.

## Daily Operations Checklist

### Morning Checks
- [ ] Check queue worker status
- [ ] Review overnight logs for errors
- [ ] Verify scheduler ran successfully
- [ ] Check backup status from yesterday
- [ ] Monitor application performance

### Throughout the Day
- [ ] Monitor error logs
- [ ] Watch queue size
- [ ] Check disk space usage
- [ ] Monitor database connections

### Evening Review
- [ ] Review daily sales summary email
- [ ] Check for any critical errors
- [ ] Verify all scheduled tasks completed
- [ ] Monitor server resources

## Emergency Procedures

### High Queue Size

```bash
# Check queue size
php artisan queue:size

# Increase workers temporarily
# Edit supervisor config to increase numprocs
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
sudo supervisorctl reread
sudo supervisorctl update

# Monitor queue processing
watch -n 5 'php artisan queue:size'
```

### Application Down

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check Nginx logs
sudo tail -f /var/log/nginx/error.log

# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

### Database Connection Issues

```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Check database logs
sudo tail -f /var/log/postgresql/postgresql-14-main.log

# Restart PostgreSQL
sudo systemctl restart postgresql

# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Redis Connection Issues

```bash
# Check Redis status
sudo systemctl status redis-server

# Test Redis connection
redis-cli ping

# Check Redis logs
sudo tail -f /var/log/redis/redis-server.log

# Restart Redis
sudo systemctl restart redis-server
```

### Disk Space Full

```bash
# Check disk usage
df -h

# Find large files
du -sh storage/logs/*
du -sh storage/app/backups/*

# Clean old logs
php artisan log:clear

# Clean old backups
php artisan backup:clean

# Clear cache
php artisan cache:clear
```

## Monitoring Commands

### System Resources

```bash
# Check disk space
df -h

# Check memory usage
free -h

# Check CPU usage
top

# Check running processes
ps aux | grep php
ps aux | grep nginx
```

### Application Health

```bash
# Check all services
sudo supervisorctl status

# Test application response
curl -I https://yourdomain.com

# Check queue size
php artisan queue:size

# Check failed jobs
php artisan queue:failed

# View application routes
php artisan route:list
```

## Configuration Files

### Key Configuration Locations

```
.env                                    - Environment configuration
config/                                 - Application configuration
/etc/nginx/sites-available/laravel-app - Nginx configuration
/etc/supervisor/conf.d/                - Supervisor configurations
/etc/php/8.2/fpm/php.ini               - PHP configuration
/etc/redis/redis.conf                  - Redis configuration
```

## Common Issues & Solutions

### Issue: Queue workers not processing jobs

```bash
# Solution 1: Restart workers
sudo supervisorctl restart laravel-worker:*

# Solution 2: Check Redis connection
redis-cli ping

# Solution 3: Check worker logs
tail -f storage/logs/worker.log
```

### Issue: Scheduler not running tasks

```bash
# Solution 1: Verify cron entry
crontab -l | grep schedule:run

# Solution 2: Check scheduler manually
php artisan schedule:run

# Solution 3: View Laravel logs
tail -f storage/logs/laravel.log
```

### Issue: High memory usage

```bash
# Solution 1: Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Solution 2: Restart queue workers
sudo supervisorctl restart laravel-worker:*

# Solution 3: Clear application cache
php artisan cache:clear
```

### Issue: 500 Internal Server Error

```bash
# Solution 1: Check Laravel logs
tail -f storage/logs/laravel.log

# Solution 2: Check Nginx logs
sudo tail -f /var/log/nginx/error.log

# Solution 3: Clear caches and rebuild
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

## Performance Optimization

### Regular Maintenance

```bash
# Weekly: Optimize database
php artisan optimize:clear
php artisan optimize

# Weekly: Prune old data
php artisan model:prune

# Monthly: Clean old sessions
php artisan session:gc

# Monthly: Analyze database
php artisan tinker
>>> DB::statement('ANALYZE');
```

### Cache Warming

```bash
# After deployment, warm up caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## Security Checks

### Regular Security Audits

```bash
# Check for outdated packages
composer outdated

# Update security patches
composer update --with-dependencies

# Review failed login attempts (if implemented)
php artisan tinker
>>> DB::table('logs')->where('level', 'warning')->where('message', 'like', '%login%')->get();

# Check file permissions
ls -la storage/
ls -la bootstrap/cache/
```

## Backup & Recovery

### Manual Backup

```bash
# Full backup
php artisan backup:run

# Database only
php artisan backup:run --only-db

# Files only
php artisan backup:run --only-files
```

### Restore from Backup

```bash
# List available backups
php artisan backup:list

# Manual restore (example for PostgreSQL)
pg_restore -U postgres -d database_name backup_file.dump
```

## Contact Information

### Emergency Contacts
- System Administrator: admin@yourdomain.com
- DevOps Team: devops@yourdomain.com
- On-Call Phone: [Your phone number]

### Useful Resources
- DEPLOYMENT.md - Full deployment guide
- STORY_50_COMPLETE.md - Implementation details
- Laravel Documentation: https://laravel.com/docs

---

**Last Updated**: February 6, 2026
**Version**: 1.0.0
