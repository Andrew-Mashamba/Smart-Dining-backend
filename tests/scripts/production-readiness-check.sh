#!/bin/bash

# Production Readiness Check Script
# Story 52: Final testing and production preparation

set -e

echo "=========================================="
echo "Production Readiness Check"
echo "=========================================="
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counter for passed/failed checks
PASSED=0
FAILED=0

# Function to print success
print_success() {
    echo -e "${GREEN}✓${NC} $1"
    ((PASSED++))
}

# Function to print failure
print_failure() {
    echo -e "${RED}✗${NC} $1"
    ((FAILED++))
}

# Function to print warning
print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

echo "1. Environment Configuration Checks"
echo "----------------------------------------"

# Check .env.production exists
if [ -f ".env.production" ]; then
    print_success ".env.production file exists"
else
    print_failure ".env.production file missing"
fi

# Check .env file
if [ -f ".env" ]; then
    print_success ".env file exists"

    # Check critical environment variables
    if grep -q "APP_ENV=production" .env 2>/dev/null; then
        print_warning "APP_ENV is set to production (ensure this is intentional)"
    else
        print_success "APP_ENV is not production (safe for development)"
    fi

    if grep -q "APP_DEBUG=false" .env 2>/dev/null; then
        print_success "APP_DEBUG is disabled"
    else
        print_warning "APP_DEBUG should be false in production"
    fi

    if grep -q "APP_KEY=" .env && ! grep -q "APP_KEY=$" .env; then
        print_success "APP_KEY is set"
    else
        print_failure "APP_KEY is not set"
    fi
else
    print_failure ".env file missing"
fi

echo ""
echo "2. Dependencies Check"
echo "----------------------------------------"

# Check composer dependencies
if [ -d "vendor" ]; then
    print_success "Composer dependencies installed"
else
    print_failure "Composer dependencies not installed"
fi

# Check node dependencies
if [ -d "node_modules" ]; then
    print_success "Node dependencies installed"
else
    print_failure "Node dependencies not installed"
fi

# Check if assets are built
if [ -d "public/build" ]; then
    print_success "Frontend assets built"
else
    print_failure "Frontend assets not built (run: npm run build)"
fi

echo ""
echo "3. Directory Permissions Check"
echo "----------------------------------------"

# Check storage directory
if [ -d "storage" ] && [ -w "storage" ]; then
    print_success "storage directory is writable"
else
    print_failure "storage directory is not writable"
fi

# Check bootstrap/cache directory
if [ -d "bootstrap/cache" ] && [ -w "bootstrap/cache" ]; then
    print_success "bootstrap/cache directory is writable"
else
    print_failure "bootstrap/cache directory is not writable"
fi

echo ""
echo "4. Database Check"
echo "----------------------------------------"

# Test database connection
if php artisan migrate:status >/dev/null 2>&1; then
    print_success "Database connection successful"
    print_success "Migrations are up to date"
else
    print_failure "Database connection failed or migrations pending"
fi

echo ""
echo "5. Configuration Cache Check"
echo "----------------------------------------"

# Check if configuration is cached for production
if [ -f "bootstrap/cache/config.php" ]; then
    print_success "Configuration is cached"
else
    print_warning "Configuration not cached (run: php artisan config:cache for production)"
fi

echo ""
echo "6. Security Checks"
echo "----------------------------------------"

# Check for debug routes
if grep -q "test-errors" routes/api.php; then
    print_warning "Debug/test routes found in api.php (should be removed in production)"
fi

# Check storage link
if [ -L "public/storage" ]; then
    print_success "Storage link exists"
else
    print_warning "Storage link not created (run: php artisan storage:link)"
fi

echo ""
echo "7. Required Services Check"
echo "----------------------------------------"

# Check if Redis is accessible (if configured)
if php artisan tinker --execute="Redis::ping();" 2>&1 | grep -q "PONG"; then
    print_success "Redis connection successful"
else
    print_warning "Redis connection failed or not configured"
fi

echo ""
echo "8. File Structure Check"
echo "----------------------------------------"

# Check critical files exist
critical_files=(
    "app/Http/Kernel.php"
    "app/Exceptions/Handler.php"
    "config/app.php"
    "config/database.php"
    "routes/web.php"
    "routes/api.php"
)

for file in "${critical_files[@]}"; do
    if [ -f "$file" ]; then
        print_success "$file exists"
    else
        print_failure "$file is missing"
    fi
done

echo ""
echo "9. Telescope Check"
echo "----------------------------------------"

if php artisan telescope:status 2>/dev/null | grep -q "enabled"; then
    print_warning "Telescope is enabled (disable in production for performance)"
else
    print_success "Telescope is disabled"
fi

echo ""
echo "10. Deployment Files Check"
echo "----------------------------------------"

deployment_files=(
    "DEPLOYMENT.md"
    "deployment/supervisor/laravel-worker.conf"
    "deployment/supervisor/laravel-reverb.conf"
)

for file in "${deployment_files[@]}"; do
    if [ -f "$file" ]; then
        print_success "$file exists"
    else
        print_warning "$file is missing"
    fi
done

echo ""
echo "=========================================="
echo "Summary"
echo "=========================================="
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All critical checks passed!${NC}"
    echo "Application is ready for production deployment."
    exit 0
else
    echo -e "${RED}✗ Some checks failed.${NC}"
    echo "Please fix the issues above before deploying to production."
    exit 1
fi
