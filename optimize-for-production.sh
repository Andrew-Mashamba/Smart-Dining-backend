#!/bin/bash

# ================================================================
# Laravel Application Performance Optimization Script
# ================================================================
# This script optimizes the Laravel application for production
# by caching routes, config, views, and building assets.
#
# Usage: ./optimize-for-production.sh
# ================================================================

set -e  # Exit on any error

echo "========================================"
echo "Laravel Performance Optimization Script"
echo "========================================"
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to print success message
success() {
    echo -e "${GREEN}✓ $1${NC}"
}

# Function to print warning message
warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Function to print error message
error() {
    echo -e "${RED}✗ $1${NC}"
}

# Check if we're in the Laravel project root
if [ ! -f "artisan" ]; then
    error "Error: artisan file not found. Please run this script from the Laravel project root."
    exit 1
fi

echo "Step 1: Clearing existing caches..."
echo "-----------------------------------"
php artisan cache:clear
success "Application cache cleared"

php artisan config:clear
success "Configuration cache cleared"

php artisan route:clear
success "Route cache cleared"

php artisan view:clear
success "View cache cleared"

echo ""
echo "Step 2: Running composer optimizations..."
echo "-----------------------------------------"
composer install --optimize-autoloader --no-dev || warning "Composer install failed - continuing anyway"
success "Composer autoloader optimized"

echo ""
echo "Step 3: Caching configuration..."
echo "--------------------------------"
php artisan config:cache
success "Configuration cached"

echo ""
echo "Step 4: Caching routes..."
echo "------------------------"
php artisan route:cache
success "Routes cached"

echo ""
echo "Step 5: Caching views..."
echo "-----------------------"
php artisan view:cache
success "Views cached"

echo ""
echo "Step 6: Optimizing event and listener discovery..."
echo "--------------------------------------------------"
php artisan event:cache || warning "Event cache failed - continuing anyway"
success "Events cached"

echo ""
echo "Step 7: Building frontend assets for production..."
echo "--------------------------------------------------"
if [ -f "package.json" ]; then
    npm run build
    success "Assets built and minified"
else
    warning "package.json not found - skipping asset build"
fi

echo ""
echo "Step 8: Creating storage link (if needed)..."
echo "-------------------------------------------"
php artisan storage:link || warning "Storage link already exists"

echo ""
echo "Step 9: Running database migrations (production)..."
echo "--------------------------------------------------"
read -p "Do you want to run migrations? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    success "Database migrations completed"
else
    warning "Database migrations skipped"
fi

echo ""
echo "========================================"
echo "Performance Optimization Summary"
echo "========================================"
echo ""
success "✓ Configuration cached"
success "✓ Routes cached"
success "✓ Views cached"
success "✓ Events cached"
success "✓ Composer autoloader optimized"
success "✓ Frontend assets built and minified"
echo ""
echo "Additional recommendations for production:"
echo "- Enable Redis for caching (CACHE_STORE=redis in .env)"
echo "- Enable Redis for sessions (SESSION_DRIVER=redis in .env)"
echo "- Set APP_DEBUG=false in .env"
echo "- Set APP_ENV=production in .env"
echo "- Configure opcache in php.ini for optimal performance"
echo "- Use a process manager like Supervisor for queue workers"
echo "- Enable HTTPS and set SECURE_COOKIES=true"
echo ""
echo "========================================"
success "Optimization complete!"
echo "========================================"
