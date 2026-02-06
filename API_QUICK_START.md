# API Quick Start Guide for Android POS App

## Quick Reference

**Base URL:** `http://your-domain.com/api`
**Authentication:** Bearer Token (Sanctum)
**Rate Limit:** 60 requests per minute
**Content-Type:** `application/json`

---

## 1. Getting Started (5 Minutes)

### Step 1: Login and Get Token
```bash
POST /api/auth/login
{
  "email": "waiter@example.com",
  "password": "password123",
  "device_name": "Android POS - Device 1"
}

Response:
{
  "token": "1|abc123...",
  "user": { "id": 1, "name": "John Doe", "role": "waiter" }
}
```

### Step 2: Set Authorization Header
For all subsequent requests:
```
Authorization: Bearer {token-from-login}
```

### Step 3: Make Your First Request
```bash
GET /api/menu
# Returns all menu items
```

---

## 2. Essential Endpoints

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | Login and get token |
| POST | `/api/auth/logout` | Revoke token |
| GET | `/api/auth/me` | Get current user |

### Menu (Public)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/menu` | All menu items |
| GET | `/api/menu/items?category_id=X` | Items by category |
| GET | `/api/menu/popular` | Popular items |
| GET | `/api/menu/search?query=chicken` | Search menu |

### Tables (Auth Required)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/tables` | All tables |
| GET | `/api/tables/{id}` | Table details |
| PATCH | `/api/tables/{id}/status` | Update table status |

### Orders (Auth Required)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/orders` | List orders |
| POST | `/api/orders` | Create order |
| GET | `/api/orders/{id}` | Order details |
| PATCH | `/api/orders/{id}/status` | Update status |

### Payments (Auth Required)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/payments?order_id=X` | Get payments |
| POST | `/api/payments` | Process payment |
| GET | `/api/orders/{id}/bill` | Get bill |

---

## 3. Common Workflows

### Workflow 1: Create an Order
```bash
# 1. Get available tables
GET /api/tables?status=available

# 2. Get menu items
GET /api/menu

# 3. Create the order
POST /api/orders
{
  "guest_id": 1,
  "table_id": 5,
  "waiter_id": 1,
  "session_id": 1,
  "order_source": "pos",
  "items": [
    {
      "menu_item_id": 1,
      "quantity": 2,
      "special_instructions": "Extra spicy"
    }
  ]
}

# 4. Confirm the order
PATCH /api/orders/{order_id}/status
{
  "status": "confirmed"
}
```

### Workflow 2: Process Payment
```bash
# 1. Get order bill
GET /api/orders/{order_id}/bill

# 2. Process cash payment
POST /api/payments
{
  "order_id": 1,
  "amount": 50000,
  "payment_method": "cash",
  "tendered": 60000
}
# Returns change: 10000

# 3. Process card payment
POST /api/payments
{
  "order_id": 1,
  "amount": 50000,
  "payment_method": "card",
  "card_last_four": "1234",
  "card_type": "visa"
}

# 4. Process mobile money
POST /api/payments
{
  "order_id": 1,
  "amount": 50000,
  "payment_method": "mobile_money",
  "phone_number": "+255123456789",
  "provider": "mpesa"
}
```

---

## 4. Request/Response Examples

### Create Order Request
```json
{
  "guest_id": 1,
  "table_id": 5,
  "waiter_id": 1,
  "session_id": 1,
  "order_source": "pos",
  "notes": "Customer prefers window seat",
  "items": [
    {
      "menu_item_id": 1,
      "quantity": 2,
      "special_instructions": "Extra spicy"
    },
    {
      "menu_item_id": 5,
      "quantity": 1
    }
  ]
}
```

### Order Response
```json
{
  "message": "Order created successfully",
  "order": {
    "id": 10,
    "order_number": "ORD-00010",
    "status": "pending",
    "totals": {
      "subtotal": 50000.00,
      "tax": 9000.00,
      "service_charge": 5000.00,
      "total_amount": 64000.00
    }
  }
}
```

---

## 5. Error Handling

### HTTP Status Codes
- `200` - Success
- `201` - Created
- `401` - Unauthorized (invalid/missing token)
- `403` - Forbidden (inactive account or insufficient permissions)
- `404` - Not Found
- `422` - Validation Error
- `429` - Rate Limit Exceeded
- `500` - Server Error

### Error Response Format
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "items": ["At least one item is required."]
  }
}
```

---

## 6. Order Status Flow

```
pending → confirmed → preparing → ready → served → completed
                                          ↓
                                      cancelled
```

### Valid Status Transitions
- `pending` → `confirmed` or `cancelled`
- `confirmed` → `preparing` or `cancelled`
- `preparing` → `ready` or `cancelled`
- `ready` → `served`
- `served` → `completed`

---

## 7. Payment Methods

### Cash Payment
```json
{
  "payment_method": "cash",
  "tendered": 70000
}
```

### Card Payment
```json
{
  "payment_method": "card",
  "card_last_four": "1234",
  "card_type": "visa"  // visa, mastercard, amex
}
```

### Mobile Money Payment
```json
{
  "payment_method": "mobile_money",
  "phone_number": "+255123456789",
  "provider": "mpesa"  // mpesa, tigopesa, airtel
}
```

---

## 8. Role-Based Permissions

### Waiter
- Create orders
- View orders and tables
- Process payments
- Add tips

### Chef/Bartender
- View orders
- Update order item status

### Manager/Admin
- Full access to all endpoints

---

## 9. Testing Tools

### cURL Example
```bash
# Store token in variable
TOKEN="your-token-here"

# Make authenticated request
curl -X GET "http://localhost:8000/api/orders" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

### Automated Test Script
```bash
cd /path/to/laravel-app
./test-api-endpoints.sh
```

### Postman Setup
1. Create new collection
2. Set Authorization Type: Bearer Token
3. Add token variable: `{{token}}`
4. Import endpoints from documentation

---

## 10. Common Issues & Solutions

### Issue: 401 Unauthorized
**Solution:**
- Check token is included in Authorization header
- Verify token format: `Bearer {token}`
- Token may have been revoked - login again

### Issue: 429 Too Many Requests
**Solution:**
- Rate limit is 60 requests/minute
- Implement request throttling in app
- Add retry logic with exponential backoff

### Issue: 422 Validation Error
**Solution:**
- Check request body matches required format
- Ensure all required fields are present
- Verify data types (e.g., IDs as integers)

### Issue: 403 Forbidden
**Solution:**
- Staff account may be inactive
- Check user role has required permissions
- Contact system administrator

---

## 11. Security Best Practices

1. **Store tokens securely**
   - Use Android Keystore for token storage
   - Never log tokens to console
   - Clear token on logout

2. **Handle token expiration**
   - Implement token refresh logic
   - Detect 401 responses and re-authenticate

3. **Validate server certificates**
   - Use HTTPS in production
   - Implement certificate pinning

4. **Sanitize user input**
   - Validate input before sending to API
   - Use appropriate data types

---

## 12. Additional Resources

- **Full Documentation:** `routes/api-docs.md`
- **Test Script:** `test-api-endpoints.sh`
- **Laravel Sanctum Docs:** https://laravel.com/docs/sanctum

---

## Support

For issues or questions:
- Check full documentation in `routes/api-docs.md`
- Run test script to verify endpoint functionality
- Contact development team

---

**Last Updated:** 2026-02-06
**API Version:** 1.0
**Laravel Version:** 11.48.0
