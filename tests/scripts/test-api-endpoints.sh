#!/bin/bash

# Hospitality System API Test Script
# This script tests all API endpoints to verify functionality

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="http://localhost:8000/api"
TOKEN=""
ORDER_ID=""
PAYMENT_ID=""

echo "======================================"
echo "Hospitality System API Test Suite"
echo "======================================"
echo ""

# Function to print test results
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ PASS${NC}: $2"
    else
        echo -e "${RED}✗ FAIL${NC}: $2"
    fi
}

# Function to make API request
api_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    local auth=$4

    if [ "$auth" == "true" ]; then
        if [ -z "$data" ]; then
            curl -s -X "$method" \
                -H "Accept: application/json" \
                -H "Content-Type: application/json" \
                -H "Authorization: Bearer $TOKEN" \
                "$BASE_URL$endpoint"
        else
            curl -s -X "$method" \
                -H "Accept: application/json" \
                -H "Content-Type: application/json" \
                -H "Authorization: Bearer $TOKEN" \
                -d "$data" \
                "$BASE_URL$endpoint"
        fi
    else
        if [ -z "$data" ]; then
            curl -s -X "$method" \
                -H "Accept: application/json" \
                -H "Content-Type: application/json" \
                "$BASE_URL$endpoint"
        else
            curl -s -X "$method" \
                -H "Accept: application/json" \
                -H "Content-Type: application/json" \
                -d "$data" \
                "$BASE_URL$endpoint"
        fi
    fi
}

echo "1. Testing Authentication Endpoints"
echo "------------------------------------"

# Test 1: Login
echo -n "Testing POST /auth/login... "
LOGIN_RESPONSE=$(api_request "POST" "/auth/login" '{"email":"admin@example.com","password":"password","device_name":"Test Device"}' "false")
TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ ! -z "$TOKEN" ]; then
    print_result 0 "Login successful, token received"
else
    print_result 1 "Login failed, no token received"
    echo "Response: $LOGIN_RESPONSE"
    echo "Please ensure you have a staff member with email 'admin@example.com' and password 'password'"
    exit 1
fi

# Test 2: Get current user
echo -n "Testing GET /auth/me... "
ME_RESPONSE=$(api_request "GET" "/auth/me" "" "true")
if echo "$ME_RESPONSE" | grep -q "user"; then
    print_result 0 "Get current user successful"
else
    print_result 1 "Get current user failed"
fi

echo ""
echo "2. Testing Menu Endpoints"
echo "------------------------------------"

# Test 3: Get all menu items
echo -n "Testing GET /menu... "
MENU_RESPONSE=$(api_request "GET" "/menu" "" "false")
if echo "$MENU_RESPONSE" | grep -q "items"; then
    print_result 0 "Get menu items successful"
else
    print_result 1 "Get menu items failed"
fi

# Test 4: Get menu by categories
echo -n "Testing GET /menu/categories... "
CATEGORIES_RESPONSE=$(api_request "GET" "/menu/categories" "" "false")
if echo "$CATEGORIES_RESPONSE" | grep -q "categories"; then
    print_result 0 "Get menu categories successful"
else
    print_result 1 "Get menu categories failed"
fi

# Test 5: Get menu items with category filter
echo -n "Testing GET /menu/items?category_id=1... "
ITEMS_RESPONSE=$(api_request "GET" "/menu/items?category_id=1" "" "false")
if echo "$ITEMS_RESPONSE" | grep -q "items"; then
    print_result 0 "Get menu items by category successful"
else
    print_result 1 "Get menu items by category failed"
fi

# Test 6: Get popular items
echo -n "Testing GET /menu/popular... "
POPULAR_RESPONSE=$(api_request "GET" "/menu/popular" "" "false")
if echo "$POPULAR_RESPONSE" | grep -q "items"; then
    print_result 0 "Get popular items successful"
else
    print_result 1 "Get popular items failed"
fi

echo ""
echo "3. Testing Table Endpoints"
echo "------------------------------------"

# Test 7: Get all tables
echo -n "Testing GET /tables... "
TABLES_RESPONSE=$(api_request "GET" "/tables" "" "true")
if echo "$TABLES_RESPONSE" | grep -q "tables"; then
    print_result 0 "Get tables successful"
else
    print_result 1 "Get tables failed"
fi

# Test 8: Get specific table
echo -n "Testing GET /tables/1... "
TABLE_RESPONSE=$(api_request "GET" "/tables/1" "" "true")
if echo "$TABLE_RESPONSE" | grep -q "id"; then
    print_result 0 "Get table details successful"
else
    print_result 1 "Get table details failed"
fi

echo ""
echo "4. Testing Order Endpoints"
echo "------------------------------------"

# Test 9: Get all orders
echo -n "Testing GET /orders... "
ORDERS_RESPONSE=$(api_request "GET" "/orders" "" "true")
if echo "$ORDERS_RESPONSE" | grep -q "data"; then
    print_result 0 "Get orders successful"
else
    print_result 1 "Get orders failed"
fi

# Test 10: Create order (if you have guests and menu items)
echo -n "Testing POST /orders... "
ORDER_DATA='{
  "guest_id": 1,
  "table_id": 1,
  "waiter_id": 1,
  "session_id": 1,
  "order_source": "pos",
  "notes": "Test order",
  "items": [
    {
      "menu_item_id": 1,
      "quantity": 2,
      "special_instructions": "Test instruction"
    }
  ]
}'
CREATE_ORDER_RESPONSE=$(api_request "POST" "/orders" "$ORDER_DATA" "true")
if echo "$CREATE_ORDER_RESPONSE" | grep -q "order"; then
    ORDER_ID=$(echo $CREATE_ORDER_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
    print_result 0 "Create order successful (Order ID: $ORDER_ID)"
else
    print_result 1 "Create order failed (may need to create guest, table, or menu items first)"
    echo "Response: $CREATE_ORDER_RESPONSE"
fi

# Test 11: Update order status (if order was created)
if [ ! -z "$ORDER_ID" ]; then
    echo -n "Testing PATCH /orders/$ORDER_ID/status... "
    UPDATE_STATUS_RESPONSE=$(api_request "PATCH" "/orders/$ORDER_ID/status" '{"status":"confirmed"}' "true")
    if echo "$UPDATE_STATUS_RESPONSE" | grep -q "message"; then
        print_result 0 "Update order status successful"
    else
        print_result 1 "Update order status failed"
    fi
fi

echo ""
echo "5. Testing Payment Endpoints"
echo "------------------------------------"

# Test 12: Get payments
echo -n "Testing GET /payments... "
PAYMENTS_RESPONSE=$(api_request "GET" "/payments" "" "true")
if echo "$PAYMENTS_RESPONSE" | grep -q "payments"; then
    print_result 0 "Get payments successful"
else
    print_result 1 "Get payments failed"
fi

# Test 13: Process payment (if order exists)
if [ ! -z "$ORDER_ID" ]; then
    echo -n "Testing POST /payments... "
    PAYMENT_DATA="{
      \"order_id\": $ORDER_ID,
      \"amount\": 50000,
      \"payment_method\": \"cash\",
      \"tendered\": 60000
    }"
    PROCESS_PAYMENT_RESPONSE=$(api_request "POST" "/payments" "$PAYMENT_DATA" "true")
    if echo "$PROCESS_PAYMENT_RESPONSE" | grep -q "payment"; then
        PAYMENT_ID=$(echo $PROCESS_PAYMENT_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
        print_result 0 "Process payment successful (Payment ID: $PAYMENT_ID)"
    else
        print_result 1 "Process payment failed"
        echo "Response: $PROCESS_PAYMENT_RESPONSE"
    fi

    # Test 14: Get bill for order
    echo -n "Testing GET /orders/$ORDER_ID/bill... "
    BILL_RESPONSE=$(api_request "GET" "/orders/$ORDER_ID/bill" "" "true")
    if echo "$BILL_RESPONSE" | grep -q "order_id"; then
        print_result 0 "Get bill successful"
    else
        print_result 1 "Get bill failed"
    fi
fi

echo ""
echo "6. Testing Rate Limiting"
echo "------------------------------------"
echo -n "Testing rate limiting (60 requests per minute)... "
RATE_LIMIT_TEST=true
for i in {1..65}; do
    RESPONSE=$(api_request "GET" "/menu" "" "false" 2>&1)
    if echo "$RESPONSE" | grep -q "429"; then
        print_result 0 "Rate limiting working correctly (blocked at request $i)"
        RATE_LIMIT_TEST=false
        break
    fi
done

if [ "$RATE_LIMIT_TEST" = true ]; then
    echo -e "${YELLOW}⚠ WARNING${NC}: Rate limiting not triggered (may need more requests or already have cache)"
fi

echo ""
echo "7. Testing Logout"
echo "------------------------------------"

# Final test: Logout
echo -n "Testing POST /auth/logout... "
LOGOUT_RESPONSE=$(api_request "POST" "/auth/logout" "" "true")
if echo "$LOGOUT_RESPONSE" | grep -q "Logged out successfully"; then
    print_result 0 "Logout successful"
else
    print_result 1 "Logout failed"
fi

echo ""
echo "======================================"
echo "API Test Suite Completed"
echo "======================================"
echo ""
echo "Note: Some tests may fail if required data (guests, menu items, etc.) doesn't exist in the database."
echo "Run 'php artisan db:seed' to populate test data if needed."
