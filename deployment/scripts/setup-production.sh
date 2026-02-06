#!/bin/bash

###############################################################################
# Laravel Production Environment Setup Script
###############################################################################
# This script sets up a fresh production environment for the Laravel application
# Run this ONCE when setting up a new production server
###############################################################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/html/laravel-app"
PHP_BIN="/usr/bin/php"
COMPOSER_BIN="/usr/local/bin/composer"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Laravel Production Setup${NC}"
echo -e "${GREEN}========================================${NC}"

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}This script must be run as root (use sudo)${NC}"
   exit 1
fi

# Navigate to application directory
echo -e "${YELLOW}Navigating to application directory...${NC}"
cd "$APP_DIR" || exit 1

# Install Composer dependencies
echo -e "${YELLOW}Installing Composer dependencies...${NC}"
$COMPOSER_BIN install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Copy environment file
if [ ! -f .env ]; then
    echo -e "${YELLOW}Copying .env.production to .env...${NC}"
    cp .env.production .env
    echo -e "${RED}IMPORTANT: Edit .env file with your production credentials!${NC}"
else
    echo -e "${YELLOW}.env file already exists, skipping...${NC}"
fi

# Generate application key if not set
echo -e "${YELLOW}Generating application key...${NC}"
$PHP_BIN artisan key:generate --force

# Generate Reverb credentials if not set
echo -e "${YELLOW}Installing Reverb (if not already installed)...${NC}"
$PHP_BIN artisan reverb:install || true

# Create storage directories
echo -e "${YELLOW}Creating storage directories...${NC}"
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set proper ownership
echo -e "${YELLOW}Setting proper ownership...${NC}"
chown -R www-data:www-data "$APP_DIR"

# Set proper permissions
echo -e "${YELLOW}Setting proper permissions...${NC}"
find "$APP_DIR" -type f -exec chmod 644 {} \;
find "$APP_DIR" -type d -exec chmod 755 {} \;
chmod -R 775 "$APP_DIR/storage"
chmod -R 775 "$APP_DIR/bootstrap/cache"

# Create storage link
echo -e "${YELLOW}Creating storage link...${NC}"
$PHP_BIN artisan storage:link

# Run database migrations
echo -e "${YELLOW}Running database migrations...${NC}"
read -p "Do you want to run database migrations? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    $PHP_BIN artisan migrate --force
fi

# Install Node dependencies and build assets
echo -e "${YELLOW}Installing Node dependencies...${NC}"
npm ci --production
echo -e "${YELLOW}Building frontend assets...${NC}"
npm run build

# Setup supervisor for queue workers
echo -e "${YELLOW}Setting up supervisor for queue workers...${NC}"
if [ -f deployment/supervisor/laravel-worker.conf ]; then
    cp deployment/supervisor/laravel-worker.conf /etc/supervisor/conf.d/
    cp deployment/supervisor/laravel-reverb.conf /etc/supervisor/conf.d/
    supervisorctl reread
    supervisorctl update
    supervisorctl start all
    echo -e "${GREEN}Supervisor configured successfully!${NC}"
else
    echo -e "${RED}Supervisor config files not found in deployment/supervisor/${NC}"
fi

# Setup cron for task scheduler
echo -e "${YELLOW}Setting up cron for task scheduler...${NC}"
if [ -f deployment/cron/laravel-scheduler ]; then
    cp deployment/cron/laravel-scheduler /etc/cron.d/
    chmod 644 /etc/cron.d/laravel-scheduler
    service cron restart
    echo -e "${GREEN}Cron configured successfully!${NC}"
else
    echo -e "${RED}Cron config file not found in deployment/cron/${NC}"
fi

# Cache configuration
echo -e "${YELLOW}Caching configuration...${NC}"
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan optimize

# Test queue connection
echo -e "${YELLOW}Testing queue connection...${NC}"
$PHP_BIN artisan queue:monitor

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Production setup completed!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "${YELLOW}Next steps:${NC}"
echo -e "1. Edit .env file with production credentials"
echo -e "2. Run 'php artisan config:cache' after editing .env"
echo -e "3. Configure your web server (Nginx/Apache)"
echo -e "4. Setup SSL certificate (Let's Encrypt recommended)"
echo -e "5. Test the application"
echo -e "6. Setup backup monitoring and alerts"
