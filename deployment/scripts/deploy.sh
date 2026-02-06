#!/bin/bash

###############################################################################
# Laravel Production Deployment Script
###############################################################################
# This script automates the deployment process for the Laravel application
# Run this script on your production server after pulling the latest code
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
echo -e "${GREEN}Laravel Production Deployment${NC}"
echo -e "${GREEN}========================================${NC}"

# Check if running as correct user
CURRENT_USER=$(whoami)
echo -e "${YELLOW}Current user: $CURRENT_USER${NC}"

# Navigate to application directory
echo -e "${YELLOW}Navigating to application directory...${NC}"
cd "$APP_DIR" || exit 1

# Put application in maintenance mode
echo -e "${YELLOW}Putting application in maintenance mode...${NC}"
$PHP_BIN artisan down --retry=60 || true

# Pull latest code from repository
echo -e "${YELLOW}Pulling latest code from repository...${NC}"
git pull origin main

# Install/Update Composer dependencies (no dev dependencies)
echo -e "${YELLOW}Installing Composer dependencies...${NC}"
$COMPOSER_BIN install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Clear and cache configuration
echo -e "${YELLOW}Clearing and caching configuration...${NC}"
$PHP_BIN artisan config:clear
$PHP_BIN artisan config:cache

# Clear and cache routes
echo -e "${YELLOW}Clearing and caching routes...${NC}"
$PHP_BIN artisan route:clear
$PHP_BIN artisan route:cache

# Clear and cache views
echo -e "${YELLOW}Clearing and caching views...${NC}"
$PHP_BIN artisan view:clear
$PHP_BIN artisan view:cache

# Run database migrations
echo -e "${YELLOW}Running database migrations...${NC}"
$PHP_BIN artisan migrate --force

# Clear application cache
echo -e "${YELLOW}Clearing application cache...${NC}"
$PHP_BIN artisan cache:clear

# Optimize application
echo -e "${YELLOW}Optimizing application...${NC}"
$PHP_BIN artisan optimize

# Build frontend assets
echo -e "${YELLOW}Building frontend assets...${NC}"
npm ci --production
npm run build

# Create storage link if it doesn't exist
echo -e "${YELLOW}Creating storage link...${NC}"
$PHP_BIN artisan storage:link || true

# Set proper permissions
echo -e "${YELLOW}Setting proper permissions...${NC}"
chmod -R 755 "$APP_DIR/storage"
chmod -R 755 "$APP_DIR/bootstrap/cache"
chown -R www-data:www-data "$APP_DIR/storage"
chown -R www-data:www-data "$APP_DIR/bootstrap/cache"

# Restart queue workers
echo -e "${YELLOW}Restarting queue workers...${NC}"
$PHP_BIN artisan queue:restart

# Restart supervisor processes
echo -e "${YELLOW}Restarting supervisor processes...${NC}"
sudo supervisorctl restart all

# Take application out of maintenance mode
echo -e "${YELLOW}Taking application out of maintenance mode...${NC}"
$PHP_BIN artisan up

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Deployment completed successfully!${NC}"
echo -e "${GREEN}========================================${NC}"
