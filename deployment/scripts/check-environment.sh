#!/bin/bash

###############################################################################
# Laravel Production Environment Check Script
###############################################################################
# This script verifies that the production environment is properly configured
###############################################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/html/laravel-app"
PHP_BIN="/usr/bin/php"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Laravel Environment Check${NC}"
echo -e "${GREEN}========================================${NC}"

cd "$APP_DIR" || exit 1

# Check PHP version
echo -e "\n${YELLOW}Checking PHP version...${NC}"
PHP_VERSION=$($PHP_BIN -v | head -n 1)
echo "PHP Version: $PHP_VERSION"

# Check Laravel version
echo -e "\n${YELLOW}Checking Laravel version...${NC}"
LARAVEL_VERSION=$($PHP_BIN artisan --version)
echo "Laravel Version: $LARAVEL_VERSION"

# Check environment
echo -e "\n${YELLOW}Checking environment...${NC}"
APP_ENV=$($PHP_BIN artisan env | grep "Current application environment" | cut -d ':' -f2 | tr -d ' ')
echo "Environment: $APP_ENV"
if [ "$APP_ENV" = "production" ]; then
    echo -e "${GREEN}✓ Running in production mode${NC}"
else
    echo -e "${RED}✗ Not running in production mode!${NC}"
fi

# Check debug mode
echo -e "\n${YELLOW}Checking debug mode...${NC}"
DEBUG=$(grep "APP_DEBUG" .env | cut -d '=' -f2)
if [ "$DEBUG" = "false" ]; then
    echo -e "${GREEN}✓ Debug mode is disabled${NC}"
else
    echo -e "${RED}✗ Debug mode is enabled! This is a security risk!${NC}"
fi

# Check APP_KEY
echo -e "\n${YELLOW}Checking APP_KEY...${NC}"
APP_KEY=$(grep "APP_KEY" .env | cut -d '=' -f2)
if [ -n "$APP_KEY" ] && [ "$APP_KEY" != "base64:REPLACE_ME" ]; then
    echo -e "${GREEN}✓ APP_KEY is set${NC}"
else
    echo -e "${RED}✗ APP_KEY is not set! Run: php artisan key:generate${NC}"
fi

# Check storage permissions
echo -e "\n${YELLOW}Checking storage permissions...${NC}"
if [ -w "$APP_DIR/storage" ] && [ -w "$APP_DIR/bootstrap/cache" ]; then
    echo -e "${GREEN}✓ Storage directories are writable${NC}"
else
    echo -e "${RED}✗ Storage directories are not writable!${NC}"
fi

# Check storage link
echo -e "\n${YELLOW}Checking storage link...${NC}"
if [ -L "$APP_DIR/public/storage" ]; then
    echo -e "${GREEN}✓ Storage link exists${NC}"
else
    echo -e "${RED}✗ Storage link does not exist! Run: php artisan storage:link${NC}"
fi

# Check Redis connection
echo -e "\n${YELLOW}Checking Redis connection...${NC}"
REDIS_CHECK=$($PHP_BIN artisan tinker --execute="try { \Illuminate\Support\Facades\Redis::connection()->ping(); echo 'OK'; } catch (\Exception \$e) { echo 'FAILED: ' . \$e->getMessage(); }")
if [[ $REDIS_CHECK == *"OK"* ]]; then
    echo -e "${GREEN}✓ Redis connection successful${NC}"
else
    echo -e "${RED}✗ Redis connection failed: $REDIS_CHECK${NC}"
fi

# Check database connection
echo -e "\n${YELLOW}Checking database connection...${NC}"
DB_CHECK=$($PHP_BIN artisan db:show 2>&1 | head -n 1)
if [[ $DB_CHECK == *"Connection"* ]] || [[ $DB_CHECK == *"Database"* ]]; then
    echo -e "${GREEN}✓ Database connection successful${NC}"
else
    echo -e "${RED}✗ Database connection failed${NC}"
fi

# Check queue workers
echo -e "\n${YELLOW}Checking queue workers...${NC}"
WORKER_COUNT=$(ps aux | grep "queue:work" | grep -v grep | wc -l)
if [ "$WORKER_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✓ Queue workers are running ($WORKER_COUNT processes)${NC}"
else
    echo -e "${RED}✗ Queue workers are not running!${NC}"
fi

# Check Reverb server
echo -e "\n${YELLOW}Checking Reverb server...${NC}"
REVERB_COUNT=$(ps aux | grep "reverb:start" | grep -v grep | wc -l)
if [ "$REVERB_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✓ Reverb server is running${NC}"
else
    echo -e "${YELLOW}⚠ Reverb server is not running${NC}"
fi

# Check supervisor
echo -e "\n${YELLOW}Checking supervisor status...${NC}"
if command -v supervisorctl &> /dev/null; then
    supervisorctl status
else
    echo -e "${RED}✗ Supervisor is not installed${NC}"
fi

# Check cron jobs
echo -e "\n${YELLOW}Checking cron jobs...${NC}"
CRON_CHECK=$(crontab -u www-data -l 2>/dev/null | grep "schedule:run" || echo "")
if [ -n "$CRON_CHECK" ] || [ -f /etc/cron.d/laravel-scheduler ]; then
    echo -e "${GREEN}✓ Laravel scheduler cron job is configured${NC}"
else
    echo -e "${RED}✗ Laravel scheduler cron job is not configured!${NC}"
fi

# Check disk space
echo -e "\n${YELLOW}Checking disk space...${NC}"
df -h "$APP_DIR" | tail -n 1

# Check recent logs
echo -e "\n${YELLOW}Checking recent error logs...${NC}"
if [ -f "$APP_DIR/storage/logs/laravel.log" ]; then
    ERROR_COUNT=$(grep -c "ERROR" "$APP_DIR/storage/logs/laravel.log" 2>/dev/null || echo "0")
    echo "Recent errors in laravel.log: $ERROR_COUNT"
    if [ "$ERROR_COUNT" -gt 0 ]; then
        echo -e "${YELLOW}Last 5 errors:${NC}"
        grep "ERROR" "$APP_DIR/storage/logs/laravel.log" | tail -n 5
    fi
else
    echo "No laravel.log file found"
fi

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}Environment check completed!${NC}"
echo -e "${GREEN}========================================${NC}"
