# SeaCliff POS - API Documentation

## Table of Contents
1. [Introduction](#introduction)
2. [Authentication](#authentication)
3. [Base URL](#base-url)
4. [Request/Response Format](#requestresponse-format)
5. [Error Handling](#error-handling)
6. [Rate Limiting](#rate-limiting)
7. [API Endpoints](#api-endpoints)
   - [Authentication](#authentication-endpoints)
   - [Menu](#menu-endpoints)
   - [Orders](#orders-endpoints)
   - [Order Items](#order-items-endpoints)
   - [Tables](#tables-endpoints)
   - [Payments](#payments-endpoints)
   - [Tips](#tips-endpoints)
   - [Guests](#guests-endpoints)
   - [QR Codes](#qr-codes-endpoints)
   - [Webhooks](#webhooks-endpoints)
8. [Role-Based Access Control](#role-based-access-control)
9. [Examples](#examples)
10. [Testing](#testing)

---

## Introduction

The SeaCliff POS API is a RESTful API that provides programmatic access to all POS functionality. It uses JSON for request and response bodies, and Laravel Sanctum for authentication.

### Key Features
- RESTful architecture
- JSON request/response format
- Token-based authentication (Laravel Sanctum)
- Role-based access control
- Real-time updates via WebSockets
- Comprehensive error handling
- Rate limiting for security

### API Version
- **Current Version**: v1
- **Base Path**: `/api`
- **Protocol**: HTTPS (production) / HTTP (development)

---

## Authentication

### Overview
The API uses **Laravel Sanctum** for token-based authentication. Tokens are issued upon successful login and must be included in subsequent requests.

### Authentication Flow
1. **Login**: POST credentials to `/api/auth/login`
2. **Receive Token**: API returns access token
3. **Use Token**: Include token in `Authorization` header
4. **Refresh** (optional): Refresh token before expiration
5. **Logout**: Revoke token via `/api/auth/logout`

### Token Usage
Include the token in the Authorization header of all authenticated requests:

```
Authorization: Bearer {your-token-here}
```

### Token Expiration
- **Default Expiration**: 24 hours
- **Refresh Window**: Last 2 hours before expiration
- **Action on Expiry**: Client receives 401 Unauthenticated

### Security Best Practices
- Store tokens securely (never in localStorage for web apps)
- Use HTTPS in production
- Implement token refresh logic
- Handle 401 errors by redirecting to login
- Revoke tokens on logout

---

## Base URL

### Development
```
http://localhost:8000/api
```

### Production
```
https://your-domain.com/api
```

### API Versioning
Currently, all endpoints are under the base path `/api`. Future versions may use `/api/v2`, etc.

---

## Request/Response Format

### Request Format
All requests should use JSON format with appropriate headers:

```http
POST /api/orders HTTP/1.1
Host: localhost:8000
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}

{
  "table_id": 5,
  "guest_id": 12,
  "items": [...]
}
```

### Required Headers
- `Content-Type: application/json`
- `Accept: application/json`
- `Authorization: Bearer {token}` (for authenticated endpoints)

### Response Format
All responses are in JSON format:

**Success Response (200/201)**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "status": "pending",
    ...
  },
  "message": "Order created successfully"
}
```

**Error Response (4xx/5xx)**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "table_id": ["The table id field is required."]
  }
}
```

### HTTP Status Codes
- **200 OK**: Request successful
- **201 Created**: Resource created successfully
- **204 No Content**: Request successful, no content to return
- **400 Bad Request**: Invalid request format
- **401 Unauthorized**: Authentication required or failed
- **403 Forbidden**: Insufficient permissions
- **404 Not Found**: Resource not found
- **422 Unprocessable Entity**: Validation failed
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server error

---

## Error Handling

### Error Response Structure
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message"]
  },
  "code": "ERROR_CODE"
}
```

### Common Error Codes
- `VALIDATION_ERROR`: Input validation failed
- `AUTHENTICATION_FAILED`: Invalid credentials
- `UNAUTHORIZED`: Insufficient permissions
- `NOT_FOUND`: Resource not found
- `ORDER_WORKFLOW_ERROR`: Invalid order state transition
- `PAYMENT_ERROR`: Payment processing failed
- `INVENTORY_ERROR`: Insufficient inventory

### Validation Errors
Validation errors return HTTP 422 with field-specific messages:

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Custom Exceptions
The API uses custom exception handlers for domain-specific errors:
- `OrderWorkflowException`: Invalid order state transitions
- `PaymentException`: Payment processing failures
- `InventoryException`: Stock-related errors

---

## Rate Limiting

### Limits
- **Authenticated Requests**: 60 requests per minute
- **Unauthenticated Requests**: 30 requests per minute
- **Login Endpoint**: 5 requests per minute

### Rate Limit Headers
Responses include rate limit information:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1612345678
```

### Exceeding Limits
When rate limit is exceeded, API returns HTTP 429:
```json
{
  "success": false,
  "message": "Too Many Requests",
  "retry_after": 60
}
```

---

## API Endpoints

### Authentication Endpoints

#### POST /api/auth/login
Login and receive authentication token.

**Access**: Public

**Request Body**:
```json
{
  "email": "waiter@seacliff.com",
  "password": "password123"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "token": "1|abc123def456...",
    "user": {
      "id": 1,
      "name": "John Waiter",
      "email": "waiter@seacliff.com",
      "role": "waiter"
    }
  },
  "message": "Login successful"
}
```

**Error Response (401)**:
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

---

#### POST /api/auth/logout
Revoke current authentication token.

**Access**: Authenticated

**Request Headers**:
```
Authorization: Bearer {token}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

#### POST /api/auth/refresh
Refresh authentication token.

**Access**: Authenticated

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "token": "2|new123token456..."
  },
  "message": "Token refreshed successfully"
}
```

---

#### GET /api/auth/me
Get current authenticated user information.

**Access**: Authenticated

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Waiter",
    "email": "waiter@seacliff.com",
    "role": "waiter",
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

---

### Menu Endpoints

#### GET /api/menu
Get all menu items with categories.

**Access**: Public

**Query Parameters**:
- `available` (boolean): Filter by availability
- `category` (string): Filter by category

**Success Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Grilled Salmon",
      "description": "Fresh Atlantic salmon with lemon butter",
      "price": 24.99,
      "category": "main",
      "prep_location": "kitchen",
      "prep_time_minutes": 15,
      "available": true,
      "image_url": "/images/salmon.jpg"
    }
  ]
}
```

---

#### GET /api/menu/{id}
Get single menu item details.

**Access**: Public

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Grilled Salmon",
    "description": "Fresh Atlantic salmon with lemon butter",
    "price": 24.99,
    "category": "main",
    "prep_location": "kitchen",
    "prep_time_minutes": 15,
    "available": true,
    "allergens": ["fish"],
    "ingredients": ["salmon", "butter", "lemon"]
  }
}
```

---

#### GET /api/menu/categories
Get all menu categories.

**Access**: Public

**Success Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "name": "appetizer",
      "display_name": "Appetizers",
      "item_count": 12
    },
    {
      "name": "main",
      "display_name": "Main Courses",
      "item_count": 24
    }
  ]
}
```

---

#### GET /api/menu/popular
Get popular menu items.

**Access**: Public

**Query Parameters**:
- `limit` (integer): Number of items to return (default: 10)

**Success Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "Classic Burger",
      "price": 14.99,
      "order_count": 245,
      "rating": 4.8
    }
  ]
}
```

---

#### GET /api/menu/search
Search menu items.

**Access**: Public

**Query Parameters**:
- `q` (string, required): Search query
- `category` (string): Filter by category

**Success Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Grilled Salmon",
      "price": 24.99,
      "relevance_score": 0.95
    }
  ]
}
```

---

#### PUT /api/menu/{id}/availability
Update menu item availability.

**Access**: Manager, Admin

**Request Body**:
```json
{
  "available": false
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Grilled Salmon",
    "available": false
  },
  "message": "Availability updated"
}
```

---

### Orders Endpoints

#### GET /api/orders
Get all orders (filtered by role).

**Access**: Waiter, Chef, Bartender, Manager, Admin

**Query Parameters**:
- `status` (string): Filter by status (pending, preparing, ready, completed, cancelled)
- `table_id` (integer): Filter by table
- `date` (date): Filter by date (YYYY-MM-DD)
- `page` (integer): Page number for pagination
- `per_page` (integer): Items per page (default: 15)

**Success Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "table_id": 5,
      "table_number": "5",
      "guest_name": "John Doe",
      "status": "preparing",
      "subtotal": 45.50,
      "tax": 4.55,
      "total": 50.05,
      "created_at": "2024-02-06T14:30:00Z",
      "items_count": 3
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 15
  }
}
```

---

#### GET /api/orders/{id}
Get single order details.

**Access**: Waiter, Chef, Bartender, Manager, Admin

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 123,
    "table_id": 5,
    "table_number": "5",
    "guest_id": 45,
    "guest_name": "John Doe",
    "status": "preparing",
    "subtotal": 45.50,
    "tax": 4.55,
    "total": 50.05,
    "items": [
      {
        "id": 1,
        "menu_item_id": 10,
        "menu_item_name": "Grilled Salmon",
        "quantity": 2,
        "price": 24.99,
        "subtotal": 49.98,
        "special_instructions": "No lemon",
        "prep_status": "preparing",
        "prep_location": "kitchen"
      }
    ],
    "created_at": "2024-02-06T14:30:00Z",
    "updated_at": "2024-02-06T14:35:00Z"
  }
}
```

---

#### POST /api/orders
Create a new order.

**Access**: Waiter, Manager, Admin

**Request Body**:
```json
{
  "table_id": 5,
  "guest_id": 45,
  "items": [
    {
      "menu_item_id": 10,
      "quantity": 2,
      "special_instructions": "No lemon"
    },
    {
      "menu_item_id": 15,
      "quantity": 1
    }
  ],
  "notes": "VIP guest"
}
```

**Success Response (201)**:
```json
{
  "success": true,
  "data": {
    "id": 124,
    "table_id": 5,
    "status": "pending",
    "subtotal": 45.50,
    "tax": 4.55,
    "total": 50.05,
    "items": [...]
  },
  "message": "Order created successfully"
}
```

---

#### POST /api/orders/{id}/items
Add items to existing order.

**Access**: Waiter, Manager, Admin

**Request Body**:
```json
{
  "items": [
    {
      "menu_item_id": 20,
      "quantity": 1,
      "special_instructions": "Extra crispy"
    }
  ]
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 124,
    "items": [...]
  },
  "message": "Items added successfully"
}
```

---

#### PATCH /api/orders/{id}/status
Update order status.

**Access**: Manager, Admin

**Request Body**:
```json
{
  "status": "completed"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 124,
    "status": "completed"
  },
  "message": "Order status updated"
}
```

---

#### POST /api/orders/{id}/serve
Mark order as served.

**Access**: Waiter, Manager, Admin

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 124,
    "status": "served"
  },
  "message": "Order marked as served"
}
```

---

#### POST /api/orders/{id}/cancel
Cancel an order.

**Access**: Manager, Admin

**Request Body**:
```json
{
  "reason": "Customer request"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "message": "Order cancelled successfully"
}
```

---

#### GET /api/orders/{id}/receipt
Generate order receipt.

**Access**: Waiter, Manager, Admin

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "order_id": 124,
    "receipt_html": "<html>...</html>",
    "receipt_url": "/receipts/124.pdf"
  }
}
```

---

### Order Items Endpoints

#### GET /api/order-items/pending
Get pending order items for kitchen/bar.

**Access**: Chef (kitchen items), Bartender (bar items), Manager, Admin

**Query Parameters**:
- `prep_location` (string): Filter by location (kitchen/bar)

**Success Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "order_id": 123,
      "table_number": "5",
      "menu_item_name": "Grilled Salmon",
      "quantity": 2,
      "special_instructions": "No lemon",
      "prep_status": "pending",
      "prep_location": "kitchen",
      "priority": "normal",
      "created_at": "2024-02-06T14:30:00Z"
    }
  ]
}
```

---

#### POST /api/order-items/{id}/received
Mark order item as received.

**Access**: Chef (kitchen items), Bartender (bar items), Manager, Admin

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "prep_status": "received"
  },
  "message": "Item marked as received"
}
```

---

#### POST /api/order-items/{id}/done
Mark order item as done/ready.

**Access**: Chef (kitchen items), Bartender (bar items), Manager, Admin

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "prep_status": "done"
  },
  "message": "Item marked as done"
}
```

---

### Tables Endpoints

#### GET /api/tables
Get all tables.

**Access**: Authenticated

**Query Parameters**:
- `status` (string): Filter by status (available, occupied, reserved)

**Success Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "number": "5",
      "capacity": 4,
      "status": "occupied",
      "current_order_id": 123,
      "qr_code_url": "/storage/qr-codes/table-5.png"
    }
  ]
}
```

---

#### GET /api/tables/{id}
Get single table details.

**Access**: Authenticated

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "number": "5",
    "capacity": 4,
    "status": "occupied",
    "current_order": {
      "id": 123,
      "status": "preparing",
      "total": 50.05
    }
  }
}
```

---

#### PATCH /api/tables/{id}/status
Update table status.

**Access**: Waiter, Manager, Admin

**Request Body**:
```json
{
  "status": "available"
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "available"
  },
  "message": "Table status updated"
}
```

---

### Payments Endpoints

#### GET /api/payments
Get all payments.

**Access**: Authenticated

**Query Parameters**:
- `order_id` (integer): Filter by order
- `method` (string): Filter by payment method
- `date` (date): Filter by date

**Success Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "order_id": 123,
      "amount": 50.05,
      "method": "card",
      "status": "completed",
      "created_at": "2024-02-06T15:00:00Z"
    }
  ]
}
```

---

#### POST /api/payments
Create payment for order.

**Access**: Waiter, Manager, Admin

**Request Body**:
```json
{
  "order_id": 123,
  "amount": 50.05,
  "method": "card",
  "tip_amount": 5.00
}
```

**Success Response (201)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_id": 123,
    "amount": 50.05,
    "tip_amount": 5.00,
    "total_amount": 55.05,
    "method": "card",
    "status": "pending"
  },
  "message": "Payment created"
}
```

---

#### POST /api/payments/{id}/confirm
Confirm payment completion.

**Access**: Waiter, Manager, Admin

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "completed"
  },
  "message": "Payment confirmed"
}
```

---

#### GET /api/orders/{orderId}/bill
Get order bill/invoice.

**Access**: Waiter, Manager, Admin

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "order_id": 123,
    "subtotal": 45.50,
    "tax": 4.55,
    "total": 50.05,
    "items": [...],
    "tip_suggestions": [5.00, 7.50, 10.00]
  }
}
```

---

#### POST /api/payments/stripe/create-intent
Create Stripe payment intent.

**Access**: Waiter, Manager, Admin

**Request Body**:
```json
{
  "order_id": 123,
  "amount": 5005
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "client_secret": "pi_xxx_secret_yyy",
    "payment_intent_id": "pi_xxx"
  }
}
```

---

#### POST /api/payments/stripe/confirm
Confirm Stripe payment.

**Access**: Waiter, Manager, Admin

**Request Body**:
```json
{
  "payment_intent_id": "pi_xxx",
  "order_id": 123
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "payment_id": 1,
    "status": "completed"
  },
  "message": "Payment confirmed"
}
```

---

### Tips Endpoints

#### POST /api/tips
Record tip for order.

**Access**: Waiter, Manager, Admin

**Request Body**:
```json
{
  "order_id": 123,
  "amount": 5.00,
  "method": "card"
}
```

**Success Response (201)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_id": 123,
    "amount": 5.00,
    "waiter_id": 10
  },
  "message": "Tip recorded"
}
```

---

#### GET /api/orders/{orderId}/tip-suggestions
Get tip suggestions for order.

**Access**: Waiter, Manager, Admin

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "order_total": 50.05,
    "suggestions": [
      {"percentage": 10, "amount": 5.01},
      {"percentage": 15, "amount": 7.51},
      {"percentage": 20, "amount": 10.01}
    ]
  }
}
```

---

### Guests Endpoints

#### GET /api/guests/phone/{phone}
Find guest by phone number.

**Access**: Waiter, Manager, Admin

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": 45,
    "name": "John Doe",
    "phone": "+1234567890",
    "email": "john@example.com",
    "total_visits": 12,
    "total_spent": 567.89
  }
}
```

---

#### POST /api/guests
Create new guest.

**Access**: Waiter, Manager, Admin

**Request Body**:
```json
{
  "name": "John Doe",
  "phone": "+1234567890",
  "email": "john@example.com"
}
```

**Success Response (201)**:
```json
{
  "success": true,
  "data": {
    "id": 46,
    "name": "John Doe",
    "phone": "+1234567890"
  },
  "message": "Guest created"
}
```

---

### QR Codes Endpoints

#### GET /api/qr-codes/tables/{tableId}
Get QR code for table.

**Access**: Manager, Admin

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "table_id": 5,
    "qr_code_url": "/storage/qr-codes/table-5.png",
    "guest_order_url": "https://seacliff.com/guest/order?table=5"
  }
}
```

---

#### POST /api/qr-codes/tables/{tableId}/generate
Generate QR code for table.

**Access**: Manager, Admin

**Success Response (201)**:
```json
{
  "success": true,
  "data": {
    "qr_code_url": "/storage/qr-codes/table-5.png"
  },
  "message": "QR code generated"
}
```

---

#### POST /api/qr-codes/generate-all
Generate QR codes for all tables.

**Access**: Manager, Admin

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "generated": 20,
    "qr_codes": [...]
  },
  "message": "QR codes generated for all tables"
}
```

---

### Webhooks Endpoints

#### GET /api/webhooks/whatsapp
WhatsApp webhook verification.

**Access**: Public (WhatsApp validation)

**Query Parameters**:
- `hub.mode`: Verification mode
- `hub.verify_token`: Verification token
- `hub.challenge`: Challenge string

---

#### POST /api/webhooks/whatsapp
WhatsApp webhook handler.

**Access**: Public (WhatsApp callbacks)

**Request Body**: WhatsApp webhook payload

---

#### POST /api/webhooks/stripe
Stripe webhook handler.

**Access**: Public (Stripe signature verification)

**Request Headers**:
- `Stripe-Signature`: Webhook signature

**Request Body**: Stripe event payload

---

## Role-Based Access Control

### Roles
The API supports the following roles:
- **Admin**: Full system access
- **Manager**: Management and oversight functions
- **Waiter**: Order creation and payment processing
- **Chef**: Kitchen order management
- **Bartender**: Bar order management

### Permission Matrix

| Endpoint | Admin | Manager | Waiter | Chef | Bartender |
|----------|-------|---------|--------|------|-----------|
| POST /api/orders | ✓ | ✓ | ✓ | ✗ | ✗ |
| GET /api/orders | ✓ | ✓ | ✓ | ✓ | ✓ |
| PATCH /api/orders/{id}/status | ✓ | ✓ | ✗ | ✗ | ✗ |
| POST /api/order-items/{id}/received | ✓ | ✓ | ✗ | ✓* | ✓** |
| POST /api/payments | ✓ | ✓ | ✓ | ✗ | ✗ |
| PUT /api/menu/{id}/availability | ✓ | ✓ | ✗ | ✗ | ✗ |
| POST /api/qr-codes/generate-all | ✓ | ✓ | ✗ | ✗ | ✗ |

*Chef: Kitchen items only
**Bartender: Bar items only

### Checking Permissions
The API uses middleware to check role permissions:
- `api.role:waiter,manager,admin` - Allows specified roles
- Roles are checked from JWT token claims
- 403 Forbidden returned for insufficient permissions

---

## Examples

### Complete Order Flow Example

**1. Login**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "waiter@seacliff.com",
    "password": "password"
  }'
```

**2. Get Menu**
```bash
curl -X GET http://localhost:8000/api/menu \
  -H "Authorization: Bearer {token}"
```

**3. Create Order**
```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "table_id": 5,
    "guest_id": 45,
    "items": [
      {
        "menu_item_id": 10,
        "quantity": 2
      }
    ]
  }'
```

**4. Get Order Bill**
```bash
curl -X GET http://localhost:8000/api/orders/123/bill \
  -H "Authorization: Bearer {token}"
```

**5. Create Payment**
```bash
curl -X POST http://localhost:8000/api/payments \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 123,
    "amount": 50.05,
    "method": "card"
  }'
```

### Kitchen Display Example

**1. Login as Chef**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "chef@seacliff.com",
    "password": "password"
  }'
```

**2. Get Pending Kitchen Items**
```bash
curl -X GET http://localhost:8000/api/order-items/pending?prep_location=kitchen \
  -H "Authorization: Bearer {token}"
```

**3. Mark Item as Received**
```bash
curl -X POST http://localhost:8000/api/order-items/1/received \
  -H "Authorization: Bearer {token}"
```

**4. Mark Item as Done**
```bash
curl -X POST http://localhost:8000/api/order-items/1/done \
  -H "Authorization: Bearer {token}"
```

---

## Testing

### Testing Tools
- **Postman**: Collection available (import from `/docs/postman-collection.json`)
- **cURL**: Command-line examples provided above
- **PHPUnit**: Automated tests in `/tests/Feature/Api`

### Test Environment
```
Base URL: http://localhost:8000/api
Test Users:
  - admin@seacliff.com / password
  - manager@seacliff.com / password
  - waiter@seacliff.com / password
  - chef@seacliff.com / password
  - bartender@seacliff.com / password
```

### Running Tests
```bash
# Run all API tests
php artisan test --filter Api

# Run specific test
php artisan test --filter OrderApiTest

# Run with coverage
php artisan test --coverage
```

### Test Data
Use database seeders to populate test data:
```bash
php artisan db:seed --class=TestDataSeeder
```

---

## Appendices

### Webhook Signatures
Stripe webhooks include a signature for verification:
```php
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = \Stripe\Webhook::constructEvent($payload, $signature, $secret);
```

### Date Formats
All timestamps use ISO 8601 format:
```
2024-02-06T14:30:00Z
```

### Pagination
Paginated endpoints return:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 15,
    "last_page": 4
  },
  "links": {
    "first": "http://api.com/orders?page=1",
    "last": "http://api.com/orders?page=4",
    "prev": null,
    "next": "http://api.com/orders?page=2"
  }
}
```

---

**Last Updated**: February 2026
**Version**: 1.0
**Support**: Contact IT department for API access and issues

---

*For more information, see other guides: [Admin Guide](ADMIN_GUIDE.md), [Manager Guide](MANAGER_GUIDE.md), [Waiter Guide](WAITER_GUIDE.md), [Chef Guide](CHEF_GUIDE.md), [Bartender Guide](BARTENDER_GUIDE.md)*
