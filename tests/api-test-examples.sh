#!/bin/bash

# API Testing Script for Hospitality System
# This script demonstrates how to test the API endpoints using curl

# Configuration
BASE_URL="http://localhost:8000/api"
TOKEN=""

echo "=========================================="
echo "Hospitality System API Test Script"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Step 1: Login
echo -e "${BLUE}Step 1: Login${NC}"
echo "POST $BASE_URL/auth/login"
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password",
    "device_name": "Test Device"
  }')

echo "$LOGIN_RESPONSE" | jq '.'
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.token')

if [ "$TOKEN" != "null" ] && [ -n "$TOKEN" ]; then
  echo -e "${GREEN}✓ Login successful${NC}"
  echo "Token: $TOKEN"
else
  echo -e "${RED}✗ Login failed${NC}"
  exit 1
fi

echo ""
echo "=========================================="

# Step 2: Get Current User
echo -e "${BLUE}Step 2: Get Current User${NC}"
echo "GET $BASE_URL/auth/me"
curl -s -X GET "$BASE_URL/auth/me" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'

echo ""
echo "=========================================="

# Step 3: Get Menu Items
echo -e "${BLUE}Step 3: Get Menu Items${NC}"
echo "GET $BASE_URL/menu"
curl -s -X GET "$BASE_URL/menu" \
  -H "Accept: application/json" | jq '.'

echo ""
echo "=========================================="

# Step 4: Get Menu by Categories
echo -e "${BLUE}Step 4: Get Menu by Categories${NC}"
echo "GET $BASE_URL/menu/categories"
curl -s -X GET "$BASE_URL/menu/categories" \
  -H "Accept: application/json" | jq '.'

echo ""
echo "=========================================="

# Step 5: Get Tables
echo -e "${BLUE}Step 5: Get All Tables${NC}"
echo "GET $BASE_URL/tables"
curl -s -X GET "$BASE_URL/tables" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'

echo ""
echo "=========================================="

# Step 6: Get Orders
echo -e "${BLUE}Step 6: Get All Orders${NC}"
echo "GET $BASE_URL/orders"
curl -s -X GET "$BASE_URL/orders" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'

echo ""
echo "=========================================="

# Step 7: Create Order (Example - may need valid IDs)
echo -e "${BLUE}Step 7: Create Order (Example)${NC}"
echo "POST $BASE_URL/orders"
echo "Note: This requires valid guest_id, table_id, waiter_id, and menu_item_id"
echo "Example payload:"
cat <<EOF
{
  "guest_id": 1,
  "table_id": 1,
  "waiter_id": 1,
  "order_source": "pos",
  "notes": "Test order from API",
  "items": [
    {
      "menu_item_id": 1,
      "quantity": 2,
      "special_instructions": "Extra spicy"
    }
  ]
}
EOF

echo ""
echo "=========================================="

# Step 8: Update Order Status (Example)
echo -e "${BLUE}Step 8: Update Order Status (Example)${NC}"
echo "PATCH $BASE_URL/orders/{id}/status"
echo "Example payload:"
cat <<EOF
{
  "status": "confirmed"
}
EOF

echo ""
echo "=========================================="

# Step 9: Process Payment (Example)
echo -e "${BLUE}Step 9: Process Payment (Example)${NC}"
echo "POST $BASE_URL/payments"
echo "Example payload (Cash):"
cat <<EOF
{
  "order_id": 1,
  "amount": 50000.00,
  "payment_method": "cash",
  "tendered": 60000.00
}
EOF

echo ""
echo "=========================================="

# Step 10: Logout
echo -e "${BLUE}Step 10: Logout${NC}"
echo "POST $BASE_URL/auth/logout"
curl -s -X POST "$BASE_URL/auth/logout" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq '.'

echo ""
echo -e "${GREEN}✓ API Test Script Completed${NC}"
echo ""
echo "Notes:"
echo "1. Make sure the Laravel application is running on http://localhost:8000"
echo "2. Ensure database is seeded with test data (users, menu items, tables, etc.)"
echo "3. Update the email/password in Step 1 if needed"
echo "4. Install jq for pretty JSON output: brew install jq (Mac) or apt-get install jq (Linux)"
