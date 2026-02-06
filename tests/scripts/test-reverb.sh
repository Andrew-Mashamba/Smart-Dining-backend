#!/bin/bash

# Laravel Reverb Testing Script
# This script helps test the Reverb WebSocket implementation

echo "======================================"
echo "Laravel Reverb Testing Script"
echo "======================================"
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Reverb is running
echo -e "${BLUE}Checking Reverb server status...${NC}"
if lsof -Pi :8080 -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo -e "${GREEN}✓ Reverb server is running on port 8080${NC}"
else
    echo -e "${YELLOW}⚠ Reverb server is NOT running on port 8080${NC}"
    echo "Start it with: php artisan reverb:start"
    echo ""
    read -p "Would you like to start Reverb now? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Starting Reverb server in background..."
        php artisan reverb:start &
        sleep 2
        echo -e "${GREEN}✓ Reverb server started${NC}"
    fi
fi

echo ""
echo -e "${BLUE}Available test options:${NC}"
echo "1. Seed test data and broadcast event"
echo "2. Test with existing order"
echo "3. Open test UI in browser"
echo "4. Show Reverb configuration"
echo "5. Check database status"
echo ""
read -p "Select option (1-5): " option

case $option in
    1)
        echo ""
        echo -e "${BLUE}Running test seeder...${NC}"
        php artisan db:seed --class=ReverbTestSeeder
        ;;
    2)
        echo ""
        echo -e "${BLUE}Testing with existing order...${NC}"
        php artisan test:order-broadcast
        ;;
    3)
        echo ""
        echo -e "${BLUE}Opening test UI...${NC}"
        echo "Please login first, then navigate to: http://localhost:8000/test-broadcast"
        if command -v open &> /dev/null; then
            open http://localhost:8000/test-broadcast
        elif command -v xdg-open &> /dev/null; then
            xdg-open http://localhost:8000/test-broadcast
        else
            echo "Please open: http://localhost:8000/test-broadcast"
        fi
        ;;
    4)
        echo ""
        echo -e "${BLUE}Reverb Configuration:${NC}"
        php artisan tinker --execute="
            echo 'Broadcast Driver: ' . config('broadcasting.default') . PHP_EOL;
            echo 'Reverb App ID: ' . config('broadcasting.connections.reverb.app_id') . PHP_EOL;
            echo 'Reverb Host: ' . config('broadcasting.connections.reverb.options.host') . PHP_EOL;
            echo 'Reverb Port: ' . config('broadcasting.connections.reverb.options.port') . PHP_EOL;
            echo 'Reverb Scheme: ' . config('broadcasting.connections.reverb.options.scheme') . PHP_EOL;
        "
        ;;
    5)
        echo ""
        echo -e "${BLUE}Database Status:${NC}"
        php artisan db:show --counts
        ;;
    *)
        echo "Invalid option"
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}======================================"
echo "Test complete!"
echo "======================================${NC}"
