# Hospitality System API Documentation

This document provides comprehensive information about the RESTful API endpoints for the Android POS application.

## Base URL
```
http://your-domain.com/api
```

## Authentication

The API uses Laravel Sanctum for token-based authentication. Include the token in the Authorization header for protected endpoints.

### Headers
```
Authorization: Bearer {your-token-here}
Accept: application/json
Content-Type: application/json
```

## Rate Limiting

All API endpoints are rate-limited to **60 requests per minute** per user. When the limit is exceeded, you'll receive a `429 Too Many Requests` response.

---

## Authentication Endpoints

### 1. Login

**POST** `/api/auth/login`

Authenticate a staff member and receive an API token.

**Request Body:**
```json
{
  "email": "waiter@example.com",
  "password": "password123",
  "device_name": "Android POS - Device 1"
}
```

**Response (200 OK):**
```json
{
  "message": "Login successful",
  "token": "1|abc123def456...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "waiter@example.com",
    "role": "waiter",
    "phone_number": "+255123456789"
  }
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

**Error Response (403 Forbidden):**
```json
{
  "message": "Your account is inactive. Please contact the administrator."
}
```

---

### 2. Logout

**POST** `/api/auth/logout`

Revoke the current access token.

**Headers:** `Authorization: Bearer {token}`

**Response (200 OK):**
```json
{
  "message": "Logged out successfully"
}
```

---

### 3. Refresh Token

**POST** `/api/auth/refresh`

Generate a new token by revoking the current one.

**Headers:** `Authorization: Bearer {token}`

**Response (200 OK):**
```json
{
  "message": "Token refreshed successfully",
  "token": "2|new-token-here..."
}
```

---

### 4. Get Current User

**GET** `/api/auth/me`

Get the currently authenticated user's information.

**Headers:** `Authorization: Bearer {token}`

**Response (200 OK):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "waiter@example.com",
    "role": "waiter",
    "phone_number": "+255123456789"
  }
}
```

---

## Menu Endpoints

### 1. Get All Menu Items

**GET** `/api/menu`

Get all available menu items with optional filters.

**Query Parameters:**
- `category` (optional): Filter by category ID
- `prep_area` (optional): Filter by preparation area (kitchen, bar, etc.)
- `max_price` (optional): Maximum price filter
- `min_price` (optional): Minimum price filter

**Example:** `/api/menu?category=1&max_price=50000`

**Response (200 OK):**
```json
{
  "items": [
    {
      "id": 1,
      "name": "Chicken Tikka",
      "description": "Grilled chicken with spices",
      "price": 25000.00,
      "category": {
        "id": 1,
        "name": "Main Course"
      },
      "prep_area": "kitchen",
      "prep_time_minutes": 20,
      "image_url": "https://example.com/images/chicken-tikka.jpg",
      "available": true,
      "is_popular": true,
      "dietary_info": "Non-vegetarian",
      "created_at": "2024-01-15T10:00:00.000000Z",
      "updated_at": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "total": 1
}
```

---

### 2. Get Menu by Categories

**GET** `/api/menu/categories`

Get menu items organized by categories.

**Response (200 OK):**
```json
{
  "categories": [
    {
      "id": 1,
      "name": "Appetizers",
      "items": [...]
    },
    {
      "id": 2,
      "name": "Main Course",
      "items": [...]
    }
  ]
}
```

---

### 3. Get Menu Item

**GET** `/api/menu/{id}`

Get details of a specific menu item.

**Response (200 OK):**
```json
{
  "id": 1,
  "name": "Chicken Tikka",
  "description": "Grilled chicken with spices",
  "price": 25000.00,
  "category": {
    "id": 1,
    "name": "Main Course"
  },
  "prep_area": "kitchen",
  "prep_time_minutes": 20,
  "image_url": "https://example.com/images/chicken-tikka.jpg",
  "available": true,
  "is_popular": true,
  "dietary_info": "Non-vegetarian"
}
```

---

### 4. Get Menu Items by Category

**GET** `/api/menu/items?category_id={id}`

Filter menu items by category ID (alternative to `/api/menu?category={id}`).

---

### 5. Search Menu

**GET** `/api/menu/search?query={search_term}`

Search menu items by name or description.

**Query Parameters:**
- `query` (required): Search term (minimum 2 characters)

**Response (200 OK):**
```json
{
  "results": [...],
  "count": 5
}
```

---

### 6. Get Popular Items

**GET** `/api/menu/popular`

Get popular menu items.

**Query Parameters:**
- `limit` (optional, default: 10): Number of items to return

**Response (200 OK):**
```json
{
  "items": [...]
}
```

---

### 7. Update Menu Item Availability

**PUT** `/api/menu/{id}/availability`

Update whether a menu item is available. (Admin/Manager only)

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "available": false
}
```

**Response (200 OK):**
```json
{
  "message": "Menu item availability updated successfully",
  "item": {...}
}
```

---

## Table Endpoints

### 1. Get All Tables

**GET** `/api/tables`

Get all tables with optional filters.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `status` (optional): Filter by status (available, occupied, reserved)
- `location` (optional): Filter by location

**Response (200 OK):**
```json
{
  "tables": [
    {
      "id": 1,
      "name": "Table 1",
      "capacity": 4,
      "location": "Main Dining",
      "status": "available",
      "qr_code": "https://example.com/qr/table1.png",
      "created_at": "2024-01-15T10:00:00.000000Z",
      "updated_at": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "total": 1
}
```

---

### 2. Get Table Details

**GET** `/api/tables/{id}`

Get details of a specific table including current orders.

**Headers:** `Authorization: Bearer {token}`

**Response (200 OK):**
```json
{
  "id": 1,
  "name": "Table 1",
  "capacity": 4,
  "location": "Main Dining",
  "status": "occupied",
  "qr_code": "https://example.com/qr/table1.png",
  "current_orders": [
    {
      "id": 10,
      "order_number": "ORD-001",
      "status": "preparing",
      "total_amount": 50000.00
    }
  ],
  "created_at": "2024-01-15T10:00:00.000000Z",
  "updated_at": "2024-01-15T10:00:00.000000Z"
}
```

---

### 3. Update Table Status

**PATCH** `/api/tables/{id}/status`

Update table status.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "status": "occupied"
}
```

**Response (200 OK):**
```json
{
  "message": "Table status updated successfully",
  "table": {...}
}
```

---

## Order Endpoints

### 1. Get Orders

**GET** `/api/orders`

Get all orders with optional filters.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `status` (optional): Filter by status
- `table_id` (optional): Filter by table
- `waiter_id` (optional): Filter by waiter
- `date` (optional): Filter by date (YYYY-MM-DD)

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "guest": {
        "id": 1,
        "name": "John Smith",
        "phone_number": "+255123456789",
        "loyalty_points": 100
      },
      "table": {
        "id": 1,
        "name": "Table 1",
        "location": "Main Dining"
      },
      "waiter": {
        "id": 1,
        "name": "Jane Doe"
      },
      "status": "preparing",
      "order_source": "pos",
      "items": [
        {
          "id": 1,
          "menu_item": {
            "id": 1,
            "name": "Chicken Tikka",
            "price": 25000.00
          },
          "quantity": 2,
          "subtotal": 50000.00,
          "status": "received",
          "special_instructions": "Extra spicy"
        }
      ],
      "totals": {
        "subtotal": 50000.00,
        "tax": 9000.00,
        "service_charge": 5000.00,
        "total_amount": 64000.00
      },
      "notes": "Guest prefers window seat",
      "created_at": "2024-01-15T10:00:00.000000Z",
      "updated_at": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

### 2. Create Order

**POST** `/api/orders`

Create a new order.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "guest_id": 1,
  "table_id": 1,
  "waiter_id": 1,
  "session_id": 1,
  "order_source": "pos",
  "notes": "Guest prefers window seat",
  "items": [
    {
      "menu_item_id": 1,
      "quantity": 2,
      "special_instructions": "Extra spicy"
    },
    {
      "menu_item_id": 2,
      "quantity": 1,
      "special_instructions": null
    }
  ]
}
```

**Response (201 Created):**
```json
{
  "message": "Order created successfully",
  "order": {
    "id": 1,
    "order_number": "ORD-001",
    "guest": {...},
    "table": {...},
    "waiter": {...},
    "status": "pending",
    "items": [...],
    "totals": {...}
  }
}
```

**Validation Errors (422 Unprocessable Entity):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "guest_id": ["Guest information is required"],
    "items": ["At least one item must be added to the order"]
  }
}
```

---

### 3. Get Order Details

**GET** `/api/orders/{id}`

Get details of a specific order.

**Headers:** `Authorization: Bearer {token}`

**Response (200 OK):**
```json
{
  "id": 1,
  "guest": {...},
  "table": {...},
  "waiter": {...},
  "status": "preparing",
  "items": [...],
  "totals": {...},
  "payments": [...]
}
```

---

### 4. Update Order Status

**PATCH** `/api/orders/{id}/status`

Update the status of an order.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "status": "confirmed"
}
```

**Valid Status Values:**
- `pending`
- `confirmed`
- `preparing`
- `ready`
- `served`
- `completed`
- `cancelled`

**Response (200 OK):**
```json
{
  "message": "Order status updated successfully",
  "order": {...}
}
```

---

### 5. Add Items to Order

**POST** `/api/orders/{id}/items`

Add additional items to an existing order.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "items": [
    {
      "menu_item_id": 3,
      "quantity": 1,
      "special_instructions": "No onions"
    }
  ]
}
```

**Response (200 OK):**
```json
{
  "message": "Items added successfully",
  "order": {...}
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "message": "Cannot add items to an order that is already being prepared"
}
```

---

### 6. Mark Order as Served

**POST** `/api/orders/{id}/serve`

Mark an order as served.

**Headers:** `Authorization: Bearer {token}`

**Response (200 OK):**
```json
{
  "message": "Order marked as served",
  "order": {...}
}
```

---

### 7. Cancel Order

**POST** `/api/orders/{id}/cancel`

Cancel an order.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "reason": "Customer changed their mind"
}
```

**Response (200 OK):**
```json
{
  "message": "Order cancelled successfully"
}
```

---

### 8. Generate Receipt

**GET** `/api/orders/{id}/receipt`

Generate and download a PDF receipt for an order.

**Headers:** `Authorization: Bearer {token}`

**Response:** PDF file download

---

## Payment Endpoints

### 1. Get Payments

**GET** `/api/payments`

Get all payments, optionally filtered by order ID.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `order_id` (optional): Filter payments by order ID

**Example:** `/api/payments?order_id=1`

**Response (200 OK):**
```json
{
  "payments": [
    {
      "id": 1,
      "order_id": 1,
      "amount": 64000.00,
      "payment_method": "cash",
      "status": "completed",
      "transaction_id": "TXN-001",
      "phone_number": null,
      "provider": null,
      "card_last_four": null,
      "card_type": null,
      "tendered": 70000.00,
      "change": 6000.00,
      "processed_at": "2024-01-15T10:30:00.000000Z",
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-15T10:30:00.000000Z"
    }
  ],
  "total": 1
}
```

---

### 2. Process Payment

**POST** `/api/payments`

Process a payment for an order.

**Headers:** `Authorization: Bearer {token}`

**Request Body (Cash):**
```json
{
  "order_id": 1,
  "amount": 64000.00,
  "payment_method": "cash",
  "tendered": 70000.00
}
```

**Request Body (Card):**
```json
{
  "order_id": 1,
  "amount": 64000.00,
  "payment_method": "card",
  "card_last_four": "1234",
  "card_type": "visa"
}
```

**Request Body (Mobile Money):**
```json
{
  "order_id": 1,
  "amount": 64000.00,
  "payment_method": "mobile_money",
  "phone_number": "+255123456789",
  "provider": "mpesa"
}
```

**Valid Payment Methods:**
- `cash`
- `card`
- `mobile_money`

**Valid Mobile Money Providers:**
- `mpesa`
- `tigopesa`
- `airtel`

**Valid Card Types:**
- `visa`
- `mastercard`
- `amex`

**Response (201 Created):**
```json
{
  "message": "Payment processed successfully",
  "payment": {
    "id": 1,
    "order_id": 1,
    "amount": 64000.00,
    "payment_method": "cash",
    "status": "completed",
    "transaction_id": "TXN-001",
    "tendered": 70000.00,
    "change": 6000.00,
    "processed_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

---

### 3. Get Payment Details

**GET** `/api/payments/{id}`

Get details of a specific payment.

**Headers:** `Authorization: Bearer {token}`

**Response (200 OK):**
```json
{
  "id": 1,
  "order_id": 1,
  "amount": 64000.00,
  "payment_method": "cash",
  "status": "completed",
  "transaction_id": "TXN-001",
  "tendered": 70000.00,
  "change": 6000.00,
  "processed_at": "2024-01-15T10:30:00.000000Z"
}
```

---

### 4. Confirm Payment

**POST** `/api/payments/{id}/confirm`

Confirm a pending payment (for card/mobile money).

**Headers:** `Authorization: Bearer {token}`

**Response (200 OK):**
```json
{
  "message": "Payment confirmed successfully",
  "payment": {...}
}
```

---

### 5. Get Bill for Order

**GET** `/api/orders/{orderId}/bill`

Get the bill summary for an order.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `order_id` (required): Order ID

**Example:** `/api/payments?order_id=1`

**Response (200 OK):**
```json
{
  "order_id": 1,
  "order_number": "ORD-001",
  "items": [...],
  "subtotal": 50000.00,
  "tax": 9000.00,
  "service_charge": 5000.00,
  "total_amount": 64000.00,
  "payments": [
    {
      "id": 1,
      "amount": 64000.00,
      "payment_method": "cash",
      "status": "completed"
    }
  ],
  "amount_paid": 64000.00,
  "balance_due": 0.00
}
```

---

## Guest Endpoints

### 1. Find Guest by Phone

**GET** `/api/guests/phone/{phone}`

Find a guest by phone number.

**Headers:** `Authorization: Bearer {token}`

**Response (200 OK):**
```json
{
  "id": 1,
  "name": "John Smith",
  "phone_number": "+255123456789",
  "email": "john@example.com",
  "loyalty_points": 100
}
```

---

### 2. Create Guest

**POST** `/api/guests`

Create a new guest record.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "name": "John Smith",
  "phone_number": "+255123456789",
  "email": "john@example.com"
}
```

**Response (201 Created):**
```json
{
  "message": "Guest created successfully",
  "guest": {...}
}
```

---

## Tips Endpoints

### 1. Add Tip

**POST** `/api/tips`

Add a tip for a waiter.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "order_id": 1,
  "staff_id": 1,
  "amount": 5000.00,
  "payment_method": "cash"
}
```

**Response (201 Created):**
```json
{
  "message": "Tip added successfully",
  "tip": {...}
}
```

---

### 2. Get Tip Suggestions

**GET** `/api/orders/{orderId}/tip-suggestions`

Get suggested tip amounts for an order.

**Headers:** `Authorization: Bearer {token}`

**Response (200 OK):**
```json
{
  "order_total": 64000.00,
  "suggestions": [
    {
      "percentage": 10,
      "amount": 6400.00
    },
    {
      "percentage": 15,
      "amount": 9600.00
    },
    {
      "percentage": 20,
      "amount": 12800.00
    }
  ]
}
```

---

## Error Responses

### Common HTTP Status Codes

- **200 OK**: Request succeeded
- **201 Created**: Resource created successfully
- **401 Unauthorized**: Authentication required or token invalid
- **403 Forbidden**: Authenticated but not authorized
- **404 Not Found**: Resource not found
- **422 Unprocessable Entity**: Validation errors
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server error

### Error Response Format

```json
{
  "message": "Error message here",
  "errors": {
    "field_name": ["Error for this field"]
  }
}
```

---

## Testing with Postman/Insomnia

### Step 1: Login
1. Send POST request to `/api/auth/login` with email and password
2. Copy the `token` from the response

### Step 2: Set Authorization Header
For all subsequent requests, add:
```
Authorization: Bearer {your-token}
```

### Step 3: Test Endpoints
Try the following workflow:
1. Get menu items: `GET /api/menu`
2. Get tables: `GET /api/tables`
3. Create order: `POST /api/orders`
4. Update order status: `PATCH /api/orders/{id}/status`
5. Process payment: `POST /api/payments`

### Sample Postman Collection

Import this JSON to get started:
```json
{
  "info": {
    "name": "Hospitality System API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "auth": {
    "type": "bearer",
    "bearer": [
      {
        "key": "token",
        "value": "{{token}}",
        "type": "string"
      }
    ]
  }
}
```

---

## Notes

1. **Token Expiration**: Tokens do not expire by default. Use the refresh endpoint to generate new tokens periodically.

2. **Role-Based Access**: Different staff roles have different permissions:
   - **Admin/Manager**: Full access to all endpoints
   - **Waiter**: Can create orders, view tables, process payments
   - **Chef/Bartender**: Can view and update order items

3. **Order Status Flow**:
   ```
   pending → confirmed → preparing → ready → served → completed
   ```

4. **Payment Status**:
   - Cash payments are marked as `completed` immediately
   - Card and mobile money payments are marked as `pending` and need confirmation

5. **Webhook Support**: Stripe webhooks are available at `/api/webhooks/stripe`

---

## Support

For issues or questions, contact the development team or refer to the Laravel Sanctum documentation at https://laravel.com/docs/sanctum
