# Smart Dining POS - Laravel Backend Implementation Plan

## Table of Contents
1. [Executive Summary](#1-executive-summary)
2. [Current System Overview](#2-current-system-overview)
3. [Architecture Design](#3-architecture-design)
4. [Database Schema](#4-database-schema)
5. [API Implementation](#5-api-implementation)
6. [Chef Display System (KDS)](#6-chef-display-system-kds)
7. [Bartender Display System](#7-bartender-display-system)
8. [Manager Dashboard](#8-manager-dashboard)
9. [Real-Time Features](#9-real-time-features)
10. [Authentication & Authorization](#10-authentication--authorization)
11. [Integration with Android POS](#11-integration-with-android-pos)
12. [Deployment & DevOps](#12-deployment--devops)
13. [Testing Strategy](#13-testing-strategy)
14. [Implementation Phases](#14-implementation-phases)

---

## 1. Executive Summary

### Project Overview
The Smart Dining POS Laravel Backend serves as the central nervous system for a comprehensive restaurant management platform. It provides:

- **REST API** for Android POS application
- **Web Portals** for Chef, Bartender, and Manager roles
- **Real-time Order Tracking** via WebSockets/Pusher
- **Payment Processing** integration
- **WhatsApp Integration** for customer ordering
- **Reporting & Analytics** for business intelligence

### Key Stakeholders & Their Views

| Role | Primary Interface | Key Functions |
|------|------------------|---------------|
| Waiter | Android POS App | Take orders, process payments, receive tips |
| Chef | Kitchen Display System (Web) | View orders, mark items ready, manage prep queue |
| Bartender | Bar Display System (Web) | View drink orders, mark items ready |
| Manager | Admin Dashboard (Web) | Reports, staff management, menu control, oversight |

### Technology Stack
- **Framework**: Laravel 12.x (PHP 8.2+)
- **Database**: MySQL 8.0 / PostgreSQL 15
- **Cache**: Redis
- **Queue**: Redis/Database
- **Real-time**: Laravel Reverb / Pusher
- **Frontend**: Livewire 4.x + Alpine.js + Tailwind CSS
- **API Auth**: Laravel Sanctum
- **PDF Generation**: DomPDF / Snappy

---



  "CRITICAL_DIRECTIVES": {
    "feature_accessibility": {
      "MANDATORY": "ALL implemented features MUST be accessible through the UI",
      "rules": [
        "Every feature implementation MUST include a navigation link or clear access path",
        "No feature should require URL typing - users must discover it through UI",
        "Advanced features MUST be grouped in a Settings or Advanced section",
        "Feature discovery panel MUST showcase new/hidden features on dashboard",
        "Every feature MUST have a help text or description explaining its purpose"
      ]
    },

    

## 2. Current System Overview

### Existing Infrastructure

```
laravel-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/           # REST API (9 controllers)
│   │   │   ├── Web/           # Web portals (4 controllers)
│   │   │   └── WhatsApp/      # WhatsApp integration
│   │   ├── Resources/         # JSON transformers
│   │   ├── Requests/          # Form validation
│   │   └── Middleware/        # Role-based auth
│   ├── Models/                # 10 Eloquent models
│   ├── Services/              # 11 service classes
│   ├── Events/                # 4 event classes
│   ├── Listeners/             # 3 listeners
│   └── Jobs/                  # 4 background jobs
├── database/
│   ├── migrations/            # 13 migrations
│   └── seeders/               # 5 seeders
└── routes/
    ├── api.php                # REST API routes
    └── web.php                # Web portal routes
```

### Existing Models
1. **User** - Laravel default authentication
2. **Staff** - Restaurant staff (waiter, chef, bartender, manager, admin)
3. **Guest** - Customer profiles with loyalty tracking
4. **Table** - Restaurant tables with location/status
5. **MenuItem** - Menu catalog with categories and prep areas
6. **Order** - Customer orders with status workflow
7. **OrderItem** - Individual order line items
8. **Payment** - Payment transactions
9. **Tip** - Waiter tips
10. **GuestSession** - QR-based table sessions

### Existing Services
- OrderService, PaymentService, TipService, MenuService
- SessionService, OrderDistributionService
- WhatsApp services (5 total)

---

## 3. Architecture Design

### System Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           LOAD BALANCER                                  │
│                         (Nginx / CloudFlare)                            │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┼───────────────┐
                    ▼               ▼               ▼
            ┌───────────┐   ┌───────────┐   ┌───────────┐
            │  Web App  │   │  API App  │   │ WebSocket │
            │  (Blade)  │   │  (JSON)   │   │  Server   │
            └───────────┘   └───────────┘   └───────────┘
                    │               │               │
                    └───────────────┼───────────────┘
                                    ▼
                    ┌───────────────────────────────┐
                    │       Laravel Application      │
                    │                               │
                    │  ┌─────────┐ ┌─────────────┐ │
                    │  │ Routes  │ │ Middleware  │ │
                    │  └────┬────┘ └──────┬──────┘ │
                    │       ▼             ▼        │
                    │  ┌─────────────────────────┐ │
                    │  │      Controllers        │ │
                    │  └───────────┬─────────────┘ │
                    │              ▼               │
                    │  ┌─────────────────────────┐ │
                    │  │       Services          │ │
                    │  └───────────┬─────────────┘ │
                    │              ▼               │
                    │  ┌─────────────────────────┐ │
                    │  │    Models/Repository    │ │
                    │  └───────────┬─────────────┘ │
                    └──────────────┼───────────────┘
                                   ▼
        ┌──────────────────────────┼──────────────────────────┐
        ▼                          ▼                          ▼
┌───────────────┐         ┌───────────────┐         ┌───────────────┐
│    MySQL      │         │     Redis     │         │    Storage    │
│   Database    │         │  Cache/Queue  │         │   (S3/Local)  │
└───────────────┘         └───────────────┘         └───────────────┘
```

### Request Flow

```
Client Request → Nginx → Laravel Router → Middleware → Controller
                                                           │
                                                           ▼
                                                       Service
                                                           │
                                                           ▼
                                                   Model/Repository
                                                           │
                                                           ▼
                                                       Database
                                                           │
                                                           ▼
                                              Response (JSON/HTML)
```

---

## 4. Database Schema

### Entity Relationship Diagram

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   guests    │────<│   orders    │>────│   tables    │
│             │     │             │     │             │
│ id          │     │ id          │     │ id          │
│ phone_number│     │ guest_id    │     │ name        │
│ name        │     │ table_id    │     │ location    │
│ loyalty_pts │     │ waiter_id   │     │ capacity    │
│ preferences │     │ status      │     │ status      │
└─────────────┘     │ subtotal    │     └─────────────┘
                    │ tax         │
                    │ service_chg │
                    │ total       │
                    │ notes       │
                    └──────┬──────┘
                           │
          ┌────────────────┼────────────────┐
          ▼                ▼                ▼
┌─────────────────┐ ┌─────────────┐ ┌─────────────┐
│  order_items    │ │  payments   │ │    tips     │
│                 │ │             │ │             │
│ id              │ │ id          │ │ id          │
│ order_id        │ │ order_id    │ │ order_id    │
│ menu_item_id    │ │ method      │ │ payment_id  │
│ quantity        │ │ amount      │ │ waiter_id   │
│ unit_price      │ │ status      │ │ amount      │
│ subtotal        │ │ transaction │ │ method      │
│ special_instr   │ │ gateway_ref │ │             │
│ status          │ └─────────────┘ └─────────────┘
│ received_at     │
│ done_at         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   menu_items    │
│                 │
│ id              │
│ name            │
│ description     │
│ category        │
│ price           │
│ prep_area       │◄── 'kitchen' or 'bar'
│ is_available    │
│ prep_time       │
└─────────────────┘

┌─────────────────┐
│     staff       │
│                 │
│ id              │
│ name            │
│ email           │
│ role            │◄── waiter, chef, bartender, manager, admin
│ phone_number    │
│ status          │
│ password_hash   │
└─────────────────┘
```

### New Tables Required

#### 1. `inventory` - Stock Management
```sql
CREATE TABLE inventory (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    menu_item_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit VARCHAR(20) NOT NULL, -- 'pieces', 'kg', 'liters'
    low_stock_threshold DECIMAL(10,2) DEFAULT 10,
    last_restocked_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
);
```

#### 2. `shifts` - Staff Shift Management
```sql
CREATE TABLE shifts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    staff_id BIGINT UNSIGNED NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    total_orders INT DEFAULT 0,
    total_sales DECIMAL(12,2) DEFAULT 0,
    total_tips DECIMAL(10,2) DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
);
```

#### 3. `daily_reports` - End of Day Reports
```sql
CREATE TABLE daily_reports (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    report_date DATE NOT NULL UNIQUE,
    total_orders INT DEFAULT 0,
    total_revenue DECIMAL(12,2) DEFAULT 0,
    total_tax DECIMAL(10,2) DEFAULT 0,
    total_tips DECIMAL(10,2) DEFAULT 0,
    cash_collected DECIMAL(12,2) DEFAULT 0,
    card_collected DECIMAL(12,2) DEFAULT 0,
    mobile_collected DECIMAL(12,2) DEFAULT 0,
    cancelled_orders INT DEFAULT 0,
    average_order_value DECIMAL(10,2) DEFAULT 0,
    peak_hour TINYINT NULL,
    most_sold_item_id BIGINT UNSIGNED NULL,
    generated_by BIGINT UNSIGNED NULL,
    generated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (most_sold_item_id) REFERENCES menu_items(id) ON DELETE SET NULL,
    FOREIGN KEY (generated_by) REFERENCES staff(id) ON DELETE SET NULL
);
```

#### 4. `audit_logs` - System Audit Trail
```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    staff_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_staff (staff_id),
    INDEX idx_audit_entity (entity_type, entity_id),
    INDEX idx_audit_created (created_at)
);
```

#### 5. `notifications` - In-App Notifications
```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    staff_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL, -- 'order_ready', 'new_order', 'tip_received', etc.
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    INDEX idx_notifications_staff_read (staff_id, read_at)
);
```

---

## 5. API Implementation

### API Structure Overview

```
/api/v1/
├── auth/
│   ├── POST   /login              # Staff login
│   ├── POST   /logout             # Logout
│   ├── POST   /refresh            # Token refresh
│   └── GET    /me                 # Current user
│
├── menu/
│   ├── GET    /                   # List menu items
│   ├── GET    /categories         # Group by category
│   ├── GET    /search             # Search menu
│   ├── GET    /{id}               # Item details
│   ├── POST   /                   # Create item (manager)
│   ├── PUT    /{id}               # Update item (manager)
│   ├── PUT    /{id}/availability  # Toggle availability
│   └── DELETE /{id}               # Delete item (manager)
│
├── tables/
│   ├── GET    /                   # List tables
│   ├── GET    /{id}               # Table details
│   ├── PUT    /{id}/status        # Update status
│   └── GET    /{id}/orders        # Table's active orders
│
├── orders/
│   ├── GET    /                   # List orders (filterable)
│   ├── POST   /                   # Create order
│   ├── GET    /{id}               # Order details
│   ├── PUT    /{id}               # Update order
│   ├── PUT    /{id}/status        # Update status
│   ├── POST   /{id}/items         # Add items
│   ├── PUT    /{id}/items/{itemId}# Update item
│   ├── DELETE /{id}/items/{itemId}# Remove item
│   ├── POST   /{id}/serve         # Mark served
│   ├── POST   /{id}/cancel        # Cancel order
│   └── GET    /{id}/bill          # Generate bill
│
├── order-items/
│   ├── GET    /pending            # Pending items (KDS)
│   ├── GET    /pending/kitchen    # Kitchen items
│   ├── GET    /pending/bar        # Bar items
│   ├── POST   /{id}/received      # Mark received
│   ├── POST   /{id}/done          # Mark done
│   └── POST   /{id}/reject        # Reject item
│
├── payments/
│   ├── POST   /                   # Create payment
│   ├── GET    /{id}               # Payment details
│   ├── POST   /{id}/confirm       # Confirm payment
│   ├── POST   /{id}/refund        # Refund payment
│   └── GET    /methods            # Available methods
│
├── tips/
│   ├── POST   /                   # Record tip
│   ├── GET    /my-tips            # Waiter's tips
│   ├── GET    /summary            # Tips summary
│   └── GET    /{orderId}/suggestions # Tip suggestions
│
├── guests/
│   ├── GET    /phone/{phone}      # Find by phone
│   ├── POST   /                   # Create guest
│   └── PUT    /{id}               # Update guest
│
├── staff/
│   ├── GET    /                   # List staff (manager)
│   ├── POST   /                   # Create staff (manager)
│   ├── PUT    /{id}               # Update staff (manager)
│   ├── DELETE /{id}               # Delete staff (manager)
│   └── GET    /{id}/performance   # Staff performance
│
├── reports/
│   ├── GET    /daily              # Daily report
│   ├── GET    /weekly             # Weekly report
│   ├── GET    /monthly            # Monthly report
│   ├── GET    /sales              # Sales analytics
│   ├── GET    /menu-performance   # Menu item stats
│   └── POST   /generate           # Generate report
│
├── shifts/
│   ├── POST   /start              # Start shift
│   ├── POST   /end                # End shift
│   ├── GET    /current            # Current shift
│   └── GET    /history            # Shift history
│
└── sync/
    ├── POST   /orders             # Sync offline orders
    ├── GET    /status             # Sync status
    └── POST   /bulk               # Bulk sync
```

### API Request/Response Examples

#### Create Order
```http
POST /api/v1/orders
Authorization: Bearer {token}
Content-Type: application/json

{
    "guest_id": 1,
    "table_id": 5,
    "items": [
        {
            "menu_item_id": 12,
            "quantity": 2,
            "special_instructions": "No onions"
        },
        {
            "menu_item_id": 8,
            "quantity": 1,
            "special_instructions": null
        }
    ],
    "notes": "Birthday celebration"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 156,
        "order_number": "ORD-20250206-156",
        "guest": {
            "id": 1,
            "name": "John Doe",
            "phone_number": "+255123456789"
        },
        "table": {
            "id": 5,
            "name": "Table 5",
            "location": "indoor"
        },
        "waiter": {
            "id": 3,
            "name": "Mike Waiter"
        },
        "items": [
            {
                "id": 1,
                "menu_item": {
                    "id": 12,
                    "name": "Grilled Salmon",
                    "price": 24.99
                },
                "quantity": 2,
                "unit_price": 24.99,
                "subtotal": 49.98,
                "special_instructions": "No onions",
                "status": "pending",
                "prep_area": "kitchen"
            },
            {
                "id": 2,
                "menu_item": {
                    "id": 8,
                    "name": "House Wine",
                    "price": 8.99
                },
                "quantity": 1,
                "unit_price": 8.99,
                "subtotal": 8.99,
                "special_instructions": null,
                "status": "pending",
                "prep_area": "bar"
            }
        ],
        "subtotal": 58.97,
        "tax": 10.61,
        "service_charge": 2.95,
        "total": 72.53,
        "status": "pending",
        "notes": "Birthday celebration",
        "created_at": "2025-02-06T10:30:00Z"
    }
}
```

#### Generate Bill
```http
GET /api/v1/orders/156/bill
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "order_id": 156,
        "order_number": "ORD-20250206-156",
        "table_name": "Table 5",
        "waiter_name": "Mike Waiter",
        "guest_name": "John Doe",
        "items": [
            {
                "name": "Grilled Salmon",
                "quantity": 2,
                "unit_price": 24.99,
                "subtotal": 49.98
            },
            {
                "name": "House Wine",
                "quantity": 1,
                "unit_price": 8.99,
                "subtotal": 8.99
            }
        ],
        "subtotal": 58.97,
        "tax_rate": 18,
        "tax_amount": 10.61,
        "service_charge_rate": 5,
        "service_charge_amount": 2.95,
        "total": 72.53,
        "tip_suggestions": {
            "10_percent": 7.25,
            "15_percent": 10.88,
            "20_percent": 14.51
        },
        "payment_status": "unpaid",
        "generated_at": "2025-02-06T11:00:00Z"
    }
}
```

---

## 6. Chef Display System (KDS)

### Overview
The Kitchen Display System provides real-time order visibility for kitchen staff, enabling efficient order preparation and tracking.

### Route: `/kitchen/display`

### Features

#### 6.1 Order Queue Display
```
┌────────────────────────────────────────────────────────────────────────────┐
│  KITCHEN DISPLAY SYSTEM                          🟢 Live    👨‍🍳 Chef: John  │
├────────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐        │
│  │ 🆕 NEW ORDERS    │  │ 🔄 IN PROGRESS   │  │ ✅ READY         │        │
│  │                  │  │                  │  │                  │        │
│  │ ┌──────────────┐ │  │ ┌──────────────┐ │  │ ┌──────────────┐ │        │
│  │ │ #156 Table 5 │ │  │ │ #154 Table 3 │ │  │ │ #152 Table 1 │ │        │
│  │ │ ⏱️ 0:45      │ │  │ │ ⏱️ 8:30      │ │  │ │ ⏱️ 12:15     │ │        │
│  │ │              │ │  │ │              │ │  │ │              │ │        │
│  │ │ 2x Salmon    │ │  │ │ 1x Ribeye    │ │  │ │ 3x Fish&Chip│ │        │
│  │ │ 1x Chicken   │ │  │ │ 2x Alfredo   │ │  │ │ 1x Salad    │ │        │
│  │ │              │ │  │ │              │ │  │ │              │ │        │
│  │ │ [RECEIVED]   │ │  │ │ [DONE]       │ │  │ │ [BUMP]      │ │        │
│  │ └──────────────┘ │  │ └──────────────┘ │  │ └──────────────┘ │        │
│  │                  │  │                  │  │                  │        │
│  │ ┌──────────────┐ │  │ ┌──────────────┐ │  │                  │        │
│  │ │ #157 Patio 2 │ │  │ │ #155 Bar 1   │ │  │                  │        │
│  │ │ ⏱️ 0:15      │ │  │ │ ⏱️ 5:20      │ │  │                  │        │
│  │ │              │ │  │ │              │ │  │                  │        │
│  │ │ 1x Burger    │ │  │ │ 1x Sandwich  │ │  │                  │        │
│  │ │              │ │  │ │              │ │  │                  │        │
│  │ │ [RECEIVED]   │ │  │ │ [DONE]       │ │  │                  │        │
│  │ └──────────────┘ │  │ └──────────────┘ │  │                  │        │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘        │
│                                                                            │
│  📊 Stats: 5 Active Orders | Avg Prep: 8.5 min | Longest Wait: 12:15     │
└────────────────────────────────────────────────────────────────────────────┘
```

#### 6.2 Order Card Component
```php
// resources/views/livewire/kitchen/order-card.blade.php

<div class="order-card {{ $this->getUrgencyClass() }}"
     wire:poll.5s="refreshOrder">

    <!-- Header -->
    <div class="card-header">
        <span class="order-number">#{{ $order->id }}</span>
        <span class="table-name">{{ $order->table->name }}</span>
        <span class="timer {{ $this->isOverdue() ? 'text-red-500' : '' }}">
            ⏱️ {{ $this->getElapsedTime() }}
        </span>
    </div>

    <!-- Items -->
    <div class="card-body">
        @foreach($order->kitchenItems as $item)
            <div class="order-item {{ $item->status }}">
                <span class="quantity">{{ $item->quantity }}x</span>
                <span class="name">{{ $item->menuItem->name }}</span>
                @if($item->special_instructions)
                    <span class="instructions">⚠️ {{ $item->special_instructions }}</span>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Actions -->
    <div class="card-footer">
        @if($order->status === 'pending')
            <button wire:click="markReceived" class="btn-received">
                RECEIVED
            </button>
        @elseif($order->status === 'preparing')
            <button wire:click="markDone" class="btn-done">
                DONE
            </button>
        @else
            <button wire:click="bump" class="btn-bump">
                BUMP
            </button>
        @endif
    </div>
</div>
```

#### 6.3 KDS Livewire Component
```php
<?php
// app/Livewire/Kitchen/KitchenDisplay.php

namespace App\Livewire\Kitchen;

use App\Models\OrderItem;
use App\Events\OrderItemReady;
use Livewire\Component;
use Livewire\Attributes\On;

class KitchenDisplay extends Component
{
    public $newOrders = [];
    public $inProgressOrders = [];
    public $readyOrders = [];
    public $stats = [];

    protected $listeners = [
        'echo:kitchen,OrderCreated' => 'handleNewOrder',
        'echo:kitchen,OrderItemUpdated' => 'refreshOrders',
    ];

    public function mount()
    {
        $this->loadOrders();
        $this->calculateStats();
    }

    public function loadOrders()
    {
        // New orders (pending kitchen items)
        $this->newOrders = OrderItem::with(['order.table', 'menuItem'])
            ->where('status', 'pending')
            ->whereHas('menuItem', fn($q) => $q->where('prep_area', 'kitchen'))
            ->orderBy('created_at')
            ->get()
            ->groupBy('order_id');

        // In progress (received but not done)
        $this->inProgressOrders = OrderItem::with(['order.table', 'menuItem'])
            ->where('status', 'preparing')
            ->whereHas('menuItem', fn($q) => $q->where('prep_area', 'kitchen'))
            ->orderBy('received_at')
            ->get()
            ->groupBy('order_id');

        // Ready (done, waiting to be served)
        $this->readyOrders = OrderItem::with(['order.table', 'menuItem'])
            ->where('status', 'ready')
            ->whereHas('menuItem', fn($q) => $q->where('prep_area', 'kitchen'))
            ->orderBy('done_at')
            ->get()
            ->groupBy('order_id');
    }

    public function markReceived($itemId)
    {
        $item = OrderItem::findOrFail($itemId);
        $item->update([
            'status' => 'preparing',
            'received_at' => now(),
        ]);

        $this->loadOrders();
        $this->dispatch('item-received', itemId: $itemId);
    }

    public function markDone($itemId)
    {
        $item = OrderItem::findOrFail($itemId);
        $item->update([
            'status' => 'ready',
            'done_at' => now(),
        ]);

        // Fire event for waiter notification
        event(new OrderItemReady($item));

        // Check if all items are ready
        $this->checkOrderComplete($item->order_id);

        $this->loadOrders();
    }

    public function bump($orderId)
    {
        OrderItem::where('order_id', $orderId)
            ->where('status', 'ready')
            ->whereHas('menuItem', fn($q) => $q->where('prep_area', 'kitchen'))
            ->update(['status' => 'served']);

        $this->loadOrders();
    }

    private function checkOrderComplete($orderId)
    {
        $pendingItems = OrderItem::where('order_id', $orderId)
            ->whereIn('status', ['pending', 'preparing'])
            ->count();

        if ($pendingItems === 0) {
            $order = Order::find($orderId);
            $order->update(['status' => 'ready']);

            // Notify waiter
            event(new OrderReady($order));
        }
    }

    public function calculateStats()
    {
        $this->stats = [
            'active_orders' => $this->newOrders->count() + $this->inProgressOrders->count(),
            'avg_prep_time' => $this->calculateAveragePrepTime(),
            'longest_wait' => $this->getLongestWait(),
        ];
    }

    #[On('echo:kitchen,OrderCreated')]
    public function handleNewOrder($event)
    {
        $this->loadOrders();
        $this->dispatch('play-alert');
    }

    public function render()
    {
        return view('livewire.kitchen.kitchen-display')
            ->layout('layouts.kds');
    }
}
```

#### 6.4 KDS Features List

| Feature | Description | Priority |
|---------|-------------|----------|
| Real-time Updates | WebSocket-based order streaming | HIGH |
| Order Timer | Visual countdown with urgency colors | HIGH |
| One-Touch Actions | Single tap to change item status | HIGH |
| Audio Alerts | Sound notification for new orders | HIGH |
| Order Grouping | Group items by order for efficiency | HIGH |
| Special Instructions | Highlight modifications/allergies | HIGH |
| Bump Screen | Archive completed orders | MEDIUM |
| Performance Stats | Average prep time, order counts | MEDIUM |
| Recall Function | Bring back bumped orders | MEDIUM |
| Dark Mode | Reduce eye strain | LOW |

---

## 7. Bartender Display System

### Overview
Similar to KDS but focused on bar items (drinks, beverages).

### Route: `/bar/display`

### Features

#### 7.1 Bar Display Layout
```
┌────────────────────────────────────────────────────────────────────────────┐
│  BAR DISPLAY SYSTEM                              🟢 Live    🍸 Bar: Sarah  │
├────────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │  DRINK QUEUE                                                         │ │
│  │                                                                      │ │
│  │  ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌────────────┐       │ │
│  │  │ #156       │ │ #157       │ │ #158       │ │ #159       │       │ │
│  │  │ Table 5    │ │ Patio 2    │ │ Bar 1      │ │ VIP Room   │       │ │
│  │  │ ⏱️ 2:30    │ │ ⏱️ 1:45    │ │ ⏱️ 0:30    │ │ ⏱️ 0:15    │       │ │
│  │  │            │ │            │ │            │ │            │       │ │
│  │  │ 2x Wine    │ │ 3x Beer    │ │ 1x Martini │ │ 4x Juice   │       │ │
│  │  │ 1x Coffee  │ │ 1x Juice   │ │ 2x Whiskey │ │ 2x Coffee  │       │ │
│  │  │            │ │            │ │            │ │            │       │ │
│  │  │ [MAKING]   │ │ [START]    │ │ [START]    │ │ [START]    │       │ │
│  │  └────────────┘ └────────────┘ └────────────┘ └────────────┘       │ │
│  │                                                                      │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
│                                                                            │
│  ┌─────────────────────────────────┐  ┌─────────────────────────────────┐ │
│  │  📊 QUICK STATS                 │  │  🍺 TOP DRINKS TODAY            │ │
│  │  ─────────────────────────────  │  │  ─────────────────────────────  │ │
│  │  Drinks Made: 45                │  │  1. House Wine (12)             │ │
│  │  Avg Time: 1.5 min              │  │  2. Local Beer (10)             │ │
│  │  Pending: 8                     │  │  3. Coffee (8)                  │ │
│  └─────────────────────────────────┘  └─────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────────────────┘
```

#### 7.2 Bar Display Livewire Component
```php
<?php
// app/Livewire/Bar/BarDisplay.php

namespace App\Livewire\Bar;

use App\Models\OrderItem;
use App\Events\OrderItemReady;
use Livewire\Component;

class BarDisplay extends Component
{
    public $pendingDrinks = [];
    public $inProgressDrinks = [];
    public $topDrinks = [];
    public $stats = [];

    protected $listeners = [
        'echo:bar,OrderCreated' => 'refreshDrinks',
        'echo:bar,OrderItemUpdated' => 'refreshDrinks',
    ];

    public function mount()
    {
        $this->loadDrinks();
        $this->loadStats();
    }

    public function loadDrinks()
    {
        $barItems = OrderItem::with(['order.table', 'menuItem'])
            ->whereIn('status', ['pending', 'preparing'])
            ->whereHas('menuItem', fn($q) => $q->where('prep_area', 'bar'))
            ->orderBy('created_at')
            ->get();

        $this->pendingDrinks = $barItems->where('status', 'pending')->groupBy('order_id');
        $this->inProgressDrinks = $barItems->where('status', 'preparing')->groupBy('order_id');
    }

    public function startMaking($itemId)
    {
        $item = OrderItem::findOrFail($itemId);
        $item->update([
            'status' => 'preparing',
            'received_at' => now(),
        ]);

        $this->loadDrinks();
    }

    public function markReady($itemId)
    {
        $item = OrderItem::findOrFail($itemId);
        $item->update([
            'status' => 'ready',
            'done_at' => now(),
        ]);

        event(new OrderItemReady($item));
        $this->loadDrinks();
    }

    public function loadStats()
    {
        $today = now()->startOfDay();

        $this->stats = [
            'drinks_made' => OrderItem::whereHas('menuItem', fn($q) => $q->where('prep_area', 'bar'))
                ->where('status', 'served')
                ->where('done_at', '>=', $today)
                ->count(),
            'avg_time' => $this->calculateAvgPrepTime(),
            'pending' => $this->pendingDrinks->flatten()->count(),
        ];

        $this->topDrinks = OrderItem::with('menuItem')
            ->whereHas('menuItem', fn($q) => $q->where('prep_area', 'bar'))
            ->where('created_at', '>=', $today)
            ->selectRaw('menu_item_id, COUNT(*) as count')
            ->groupBy('menu_item_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.bar.bar-display')
            ->layout('layouts.bar');
    }
}
```

---

## 8. Manager Dashboard

### Overview
Comprehensive admin dashboard for restaurant managers with real-time analytics, staff management, and operational controls.

### Route: `/manager/dashboard`

### 8.1 Dashboard Layout
```
┌────────────────────────────────────────────────────────────────────────────────┐
│  ☰  SEACLIFF POS - Manager Dashboard                    👤 Jane Manager  [🔔] │
├────────────────────────────────────────────────────────────────────────────────┤
│                                                                                │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐              │
│  │ 💰 REVENUE  │ │ 📦 ORDERS   │ │ 👥 GUESTS   │ │ ⭐ RATING   │              │
│  │             │ │             │ │             │ │             │              │
│  │  $4,250.00  │ │     47      │ │     62      │ │    4.8/5    │              │
│  │  ↑ 12.5%    │ │  ↑ 8 today  │ │  ↑ 15%     │ │  ★★★★★     │              │
│  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘              │
│                                                                                │
│  ┌──────────────────────────────────────┐ ┌──────────────────────────────────┐│
│  │  📈 SALES OVERVIEW                   │ │  🍽️ ACTIVE ORDERS                ││
│  │                                      │ │                                  ││
│  │     $                                │ │  Table 3  │ #154 │ Preparing    ││
│  │  800 │    ╭──╮                       │ │  Table 5  │ #156 │ Pending      ││
│  │  600 │   ╭╯  ╰╮    ╭─╮              │ │  Patio 2  │ #157 │ Ready        ││
│  │  400 │──╯     ╰──╮╭╯ ╰╮             │ │  Bar 1    │ #158 │ Pending      ││
│  │  200 │           ╰╯   ╰──           │ │  VIP Room │ #159 │ Preparing    ││
│  │    0 └─────────────────────          │ │                                  ││
│  │      9AM 11AM 1PM  3PM  5PM          │ │  [View All Orders →]             ││
│  └──────────────────────────────────────┘ └──────────────────────────────────┘│
│                                                                                │
│  ┌──────────────────────────────────────┐ ┌──────────────────────────────────┐│
│  │  👨‍🍳 STAFF ON DUTY                   │ │  🏆 TOP SELLING ITEMS            ││
│  │                                      │ │                                  ││
│  │  Mike W.  │ Waiter  │ 12 orders     │ │  1. Grilled Salmon    │ 15 sold ││
│  │  Sarah B. │ Bartender│ 28 drinks    │ │  2. Ribeye Steak      │ 12 sold ││
│  │  John C.  │ Chef    │ Active        │ │  3. Chicken Alfredo   │ 10 sold ││
│  │  Lisa W.  │ Waiter  │ 8 orders      │ │  4. House Wine        │ 25 sold ││
│  │                                      │ │  5. Fish and Chips    │ 8 sold  ││
│  │  [Manage Staff →]                    │ │  [Menu Analytics →]              ││
│  └──────────────────────────────────────┘ └──────────────────────────────────┘│
│                                                                                │
│  ┌──────────────────────────────────────┐ ┌──────────────────────────────────┐│
│  │  🪑 TABLE STATUS                     │ │  💳 RECENT PAYMENTS              ││
│  │                                      │ │                                  ││
│  │  [1]🟢 [2]🟢 [3]🔴 [4]🟢            │ │  #156 │ $125.50 │ Card │ ✓      ││
│  │  [5]🟡 [6]🟢 [7]🔴 [8]🟢            │ │  #155 │ $78.00  │ Cash │ ✓      ││
│  │  [B1]🟢 [B2]🔴 [B3]🟢              │ │  #154 │ $245.00 │ Mobile│ ✓     ││
│  │  [VIP]🟢                            │ │  #153 │ $92.50  │ Card │ ✓      ││
│  │                                      │ │                                  ││
│  │  🟢 Available  🔴 Occupied  🟡 Reserved│ │  [View All Payments →]           ││
│  └──────────────────────────────────────┘ └──────────────────────────────────┘│
│                                                                                │
└────────────────────────────────────────────────────────────────────────────────┘
```

### 8.2 Manager Dashboard Features

#### Navigation Menu
```
┌─────────────────────────┐
│  📊 Dashboard           │ ← Current
│  📦 Orders              │
│  🍽️ Menu Management     │
│  🪑 Tables              │
│  👥 Staff               │
│  💳 Payments            │
│  💰 Tips                │
│  📈 Reports             │
│  ⚙️ Settings            │
│  📤 Export Data         │
└─────────────────────────┘
```

### 8.3 Sub-Pages

#### 8.3.1 Orders Management (`/manager/orders`)
```php
<?php
// Features:
// - List all orders with filters (status, date, table, waiter)
// - View order details
// - Cancel/modify orders
// - Assign waiter to order
// - View order history
```

#### 8.3.2 Menu Management (`/manager/menu`)
```php
<?php
// Features:
// - CRUD menu items
// - Category management
// - Price updates
// - Toggle availability (86'd items)
// - Image upload
// - Preparation time settings
// - Assign prep area (kitchen/bar)
```

#### 8.3.3 Staff Management (`/manager/staff`)
```php
<?php
// Features:
// - Add/edit/delete staff
// - Role assignment
// - View performance metrics
// - Shift management
// - Access control
// - Tips summary by staff
```

#### 8.3.4 Reports (`/manager/reports`)
```php
<?php
// Features:
// - Daily/Weekly/Monthly sales reports
// - Revenue by category
// - Staff performance reports
// - Table turnover analysis
// - Peak hours analysis
// - Menu item performance
// - Tips distribution
// - Export to PDF/Excel
```

### 8.4 Manager Livewire Components

```php
<?php
// app/Livewire/Manager/Dashboard.php

namespace App\Livewire\Manager;

use Livewire\Component;
use App\Models\{Order, Payment, Staff, Table, MenuItem, OrderItem};
use Carbon\Carbon;

class Dashboard extends Component
{
    public $dateRange = 'today';
    public $stats = [];
    public $recentOrders = [];
    public $staffOnDuty = [];
    public $topItems = [];
    public $tableStatus = [];
    public $recentPayments = [];
    public $salesData = [];

    public function mount()
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $startDate = $this->getStartDate();

        // Key metrics
        $this->stats = [
            'revenue' => Payment::where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
            'revenue_change' => $this->calculateRevenueChange(),
            'orders' => Order::where('created_at', '>=', $startDate)->count(),
            'guests' => Order::where('created_at', '>=', $startDate)
                ->distinct('guest_id')->count('guest_id'),
            'avg_rating' => 4.8, // From feedback system
        ];

        // Active orders
        $this->recentOrders = Order::with(['table', 'waiter'])
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Staff on duty
        $this->staffOnDuty = Staff::where('status', 'active')
            ->whereHas('shifts', fn($q) => $q->where('status', 'active'))
            ->with(['shifts' => fn($q) => $q->where('status', 'active')])
            ->get();

        // Top selling items
        $this->topItems = OrderItem::with('menuItem')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('menu_item_id, SUM(quantity) as total_sold')
            ->groupBy('menu_item_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        // Table status
        $this->tableStatus = Table::all()->groupBy('status');

        // Recent payments
        $this->recentPayments = Payment::with('order')
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Sales chart data
        $this->loadSalesChart();
    }

    public function loadSalesChart()
    {
        $hours = collect(range(9, 22)); // 9 AM to 10 PM

        $this->salesData = $hours->map(function ($hour) {
            return [
                'hour' => $hour . ':00',
                'sales' => Payment::where('status', 'completed')
                    ->whereDate('created_at', today())
                    ->whereTime('created_at', '>=', sprintf('%02d:00:00', $hour))
                    ->whereTime('created_at', '<', sprintf('%02d:00:00', $hour + 1))
                    ->sum('amount'),
            ];
        });
    }

    public function updatedDateRange($value)
    {
        $this->loadDashboardData();
    }

    public function render()
    {
        return view('livewire.manager.dashboard')
            ->layout('layouts.manager');
    }
}
```

### 8.5 Reports Generator

```php
<?php
// app/Services/ReportService.php

namespace App\Services;

use App\Models\{Order, Payment, OrderItem, Staff, Tip};
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportService
{
    public function generateDailyReport(Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $orders = Order::whereBetween('created_at', [$startOfDay, $endOfDay])->get();
        $payments = Payment::whereBetween('created_at', [$startOfDay, $endOfDay])
            ->where('status', 'completed')->get();

        return [
            'date' => $date->toDateString(),
            'summary' => [
                'total_orders' => $orders->count(),
                'completed_orders' => $orders->where('status', 'completed')->count(),
                'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
                'total_revenue' => $payments->sum('amount'),
                'total_tax' => $orders->sum('tax'),
                'total_tips' => Tip::whereBetween('created_at', [$startOfDay, $endOfDay])->sum('amount'),
            ],
            'payment_breakdown' => [
                'cash' => $payments->where('method', 'cash')->sum('amount'),
                'card' => $payments->where('method', 'card')->sum('amount'),
                'mobile' => $payments->where('method', 'mpesa')->sum('amount'),
            ],
            'hourly_sales' => $this->getHourlySales($startOfDay, $endOfDay),
            'top_items' => $this->getTopItems($startOfDay, $endOfDay),
            'staff_performance' => $this->getStaffPerformance($startOfDay, $endOfDay),
            'table_turnover' => $this->getTableTurnover($startOfDay, $endOfDay),
        ];
    }

    public function exportToPdf(array $reportData): string
    {
        $pdf = Pdf::loadView('reports.daily', $reportData);

        $filename = 'daily-report-' . $reportData['date'] . '.pdf';
        $path = storage_path('app/reports/' . $filename);

        $pdf->save($path);

        return $path;
    }

    public function exportToExcel(array $reportData): string
    {
        // Implementation using Laravel Excel
        return Excel::download(new DailyReportExport($reportData),
            'daily-report-' . $reportData['date'] . '.xlsx');
    }

    private function getHourlySales($start, $end): array
    {
        return Payment::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->selectRaw('HOUR(created_at) as hour, SUM(amount) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total', 'hour')
            ->toArray();
    }

    private function getTopItems($start, $end): array
    {
        return OrderItem::with('menuItem')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('menu_item_id, SUM(quantity) as qty, SUM(subtotal) as revenue')
            ->groupBy('menu_item_id')
            ->orderByDesc('qty')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getStaffPerformance($start, $end): array
    {
        return Staff::where('role', 'waiter')
            ->withCount(['orders' => fn($q) => $q->whereBetween('created_at', [$start, $end])])
            ->withSum(['tips' => fn($q) => $q->whereBetween('created_at', [$start, $end])], 'amount')
            ->get()
            ->toArray();
    }

    private function getTableTurnover($start, $end): array
    {
        return Order::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->selectRaw('table_id, COUNT(*) as turns')
            ->groupBy('table_id')
            ->with('table:id,name')
            ->get()
            ->toArray();
    }
}
```

---

## 9. Real-Time Features

### 9.1 WebSocket Implementation (Laravel Reverb)

```php
<?php
// config/reverb.php

return [
    'default' => env('REVERB_SERVER', 'reverb'),

    'servers' => [
        'reverb' => [
            'host' => env('REVERB_HOST', '0.0.0.0'),
            'port' => env('REVERB_PORT', 8080),
            'app_id' => env('REVERB_APP_ID'),
            'app_key' => env('REVERB_APP_KEY'),
            'app_secret' => env('REVERB_APP_SECRET'),
        ],
    ],
];
```

### 9.2 Event Broadcasting

```php
<?php
// app/Events/OrderCreated.php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('orders'),
        ];

        // Route to kitchen or bar based on items
        if ($this->order->hasKitchenItems()) {
            $channels[] = new Channel('kitchen');
        }

        if ($this->order->hasBarItems()) {
            $channels[] = new Channel('bar');
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'table' => $this->order->table->name,
                'items' => $this->order->items->map(fn($item) => [
                    'name' => $item->menuItem->name,
                    'quantity' => $item->quantity,
                    'prep_area' => $item->menuItem->prep_area,
                    'special_instructions' => $item->special_instructions,
                ]),
                'created_at' => $this->order->created_at->toIso8601String(),
            ],
        ];
    }
}
```

### 9.3 Frontend JavaScript Integration

```javascript
// resources/js/echo-setup.js

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

// Kitchen Display Subscriptions
if (document.getElementById('kitchen-display')) {
    Echo.channel('kitchen')
        .listen('OrderCreated', (e) => {
            playAlertSound();
            Livewire.dispatch('new-kitchen-order', { order: e.order });
        })
        .listen('OrderItemUpdated', (e) => {
            Livewire.dispatch('item-updated', { item: e.item });
        });
}

// Bar Display Subscriptions
if (document.getElementById('bar-display')) {
    Echo.channel('bar')
        .listen('OrderCreated', (e) => {
            playAlertSound();
            Livewire.dispatch('new-bar-order', { order: e.order });
        });
}

// Manager Dashboard Subscriptions
if (document.getElementById('manager-dashboard')) {
    Echo.channel('orders')
        .listen('OrderCreated', (e) => {
            Livewire.dispatch('order-created', { order: e.order });
        })
        .listen('PaymentReceived', (e) => {
            Livewire.dispatch('payment-received', { payment: e.payment });
        });
}

function playAlertSound() {
    const audio = new Audio('/sounds/new-order.mp3');
    audio.play().catch(e => console.log('Audio play failed:', e));
}
```

---

## 10. Authentication & Authorization

### 10.1 Role-Based Access Control

```php
<?php
// app/Http/Middleware/CheckRole.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): mixed
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
```

### 10.2 Route Protection

```php
<?php
// routes/web.php

use App\Http\Controllers\Web\{AuthController, ManagerController, KitchenController, BarController};

// Public routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:staff')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Kitchen Display - Chef only
    Route::middleware('role:chef,admin')->prefix('kitchen')->group(function () {
        Route::get('/display', [KitchenController::class, 'display'])->name('kitchen.display');
        Route::post('/items/{id}/received', [KitchenController::class, 'markReceived']);
        Route::post('/items/{id}/done', [KitchenController::class, 'markDone']);
    });

    // Bar Display - Bartender only
    Route::middleware('role:bartender,admin')->prefix('bar')->group(function () {
        Route::get('/display', [BarController::class, 'display'])->name('bar.display');
        Route::post('/items/{id}/received', [BarController::class, 'markReceived']);
        Route::post('/items/{id}/done', [BarController::class, 'markDone']);
    });

    // Manager Dashboard - Manager/Admin only
    Route::middleware('role:manager,admin')->prefix('manager')->group(function () {
        Route::get('/dashboard', [ManagerController::class, 'dashboard'])->name('manager.dashboard');
        Route::get('/orders', [ManagerController::class, 'orders'])->name('manager.orders');
        Route::get('/menu', [ManagerController::class, 'menu'])->name('manager.menu');
        Route::get('/staff', [ManagerController::class, 'staff'])->name('manager.staff');
        Route::get('/reports', [ManagerController::class, 'reports'])->name('manager.reports');
        Route::get('/tables', [ManagerController::class, 'tables'])->name('manager.tables');
        Route::get('/payments', [ManagerController::class, 'payments'])->name('manager.payments');
        Route::get('/settings', [ManagerController::class, 'settings'])->name('manager.settings');
    });
});
```

### 10.3 API Token Authentication

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\*;

// Public API routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected API routes (Waiter POS App)
Route::middleware('auth:sanctum')->group(function () {

    // All authenticated staff
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/menu', [MenuController::class, 'index']);
    Route::get('/tables', [TableController::class, 'index']);

    // Waiter routes
    Route::middleware('ability:waiter,manager,admin')->group(function () {
        Route::apiResource('orders', OrderController::class);
        Route::post('/orders/{id}/items', [OrderController::class, 'addItems']);
        Route::post('/orders/{id}/serve', [OrderController::class, 'markServed']);
        Route::get('/orders/{id}/bill', [PaymentController::class, 'generateBill']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::post('/tips', [TipController::class, 'store']);
        Route::get('/tips/my-tips', [TipController::class, 'myTips']);
    });

    // Kitchen routes
    Route::middleware('ability:chef,manager,admin')->group(function () {
        Route::get('/order-items/pending/kitchen', [OrderItemController::class, 'pendingKitchen']);
        Route::post('/order-items/{id}/received', [OrderItemController::class, 'markReceived']);
        Route::post('/order-items/{id}/done', [OrderItemController::class, 'markDone']);
    });

    // Bar routes
    Route::middleware('ability:bartender,manager,admin')->group(function () {
        Route::get('/order-items/pending/bar', [OrderItemController::class, 'pendingBar']);
    });

    // Manager routes
    Route::middleware('ability:manager,admin')->group(function () {
        Route::apiResource('staff', StaffController::class);
        Route::apiResource('menu', MenuController::class)->except(['index', 'show']);
        Route::get('/reports/daily', [ReportController::class, 'daily']);
        Route::get('/reports/sales', [ReportController::class, 'sales']);
    });
});
```

---

## 11. Integration with Android POS

### 11.1 Sync Endpoint for Offline Support

```php
<?php
// app/Http/Controllers/Api/SyncController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncOfflineOrders;
use App\Models\{Order, MenuItem, Table, Staff};
use Illuminate\Http\Request;

class SyncController extends Controller
{
    /**
     * Sync offline orders from Android app
     */
    public function syncOrders(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.local_id' => 'required|string',
            'orders.*.guest_id' => 'required|integer',
            'orders.*.table_id' => 'required|integer',
            'orders.*.items' => 'required|array',
            'orders.*.created_at' => 'required|date',
        ]);

        $results = [];

        foreach ($validated['orders'] as $orderData) {
            try {
                $order = Order::create([
                    'guest_id' => $orderData['guest_id'],
                    'table_id' => $orderData['table_id'],
                    'waiter_id' => $request->user()->id,
                    'status' => 'pending',
                    'notes' => $orderData['notes'] ?? null,
                    'created_at' => $orderData['created_at'],
                ]);

                foreach ($orderData['items'] as $item) {
                    $menuItem = MenuItem::find($item['menu_item_id']);
                    $order->items()->create([
                        'menu_item_id' => $item['menu_item_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $menuItem->price,
                        'subtotal' => $menuItem->price * $item['quantity'],
                        'special_instructions' => $item['special_instructions'] ?? null,
                    ]);
                }

                $order->calculateTotals();

                $results[] = [
                    'local_id' => $orderData['local_id'],
                    'server_id' => $order->id,
                    'status' => 'synced',
                ];

            } catch (\Exception $e) {
                $results[] = [
                    'local_id' => $orderData['local_id'],
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Get initial data for Android app
     */
    public function getInitialData(Request $request)
    {
        return response()->json([
            'menu' => MenuItem::where('is_available', true)->get(),
            'tables' => Table::all(),
            'categories' => MenuItem::distinct('category')->pluck('category'),
            'payment_methods' => ['cash', 'card', 'mpesa', 'pesapal'],
            'tax_rate' => 18,
            'service_charge_rate' => 5,
            'currency' => 'TZS',
        ]);
    }

    /**
     * Get sync status
     */
    public function status(Request $request)
    {
        $lastSync = $request->user()->last_sync_at;

        return response()->json([
            'last_sync' => $lastSync,
            'pending_changes' => [
                'menu_updated' => MenuItem::where('updated_at', '>', $lastSync)->exists(),
                'tables_updated' => Table::where('updated_at', '>', $lastSync)->exists(),
            ],
        ]);
    }
}
```

### 11.2 FCM Push Notification Integration

```php
<?php
// app/Services/FCMService.php

namespace App\Services;

use Google\Client;
use Google\Service\FirebaseCloudMessaging;

class FCMService
{
    private $client;
    private $fcm;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(storage_path('firebase-service-account.json'));
        $this->client->addScope(FirebaseCloudMessaging::CLOUD_PLATFORM);

        $this->fcm = new FirebaseCloudMessaging($this->client);
    }

    public function sendToDevice(string $token, string $title, string $body, array $data = []): bool
    {
        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => array_map('strval', $data),
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'order_updates',
                        'sound' => 'default',
                    ],
                ],
            ],
        ];

        try {
            $projectId = config('services.firebase.project_id');
            $this->fcm->projects_messages->send(
                "projects/{$projectId}",
                new FirebaseCloudMessaging\SendMessageRequest($message)
            );
            return true;
        } catch (\Exception $e) {
            \Log::error('FCM Send Failed: ' . $e->getMessage());
            return false;
        }
    }

    public function notifyOrderReady(Order $order): void
    {
        $waiter = $order->waiter;

        if ($waiter && $waiter->fcm_token) {
            $this->sendToDevice(
                $waiter->fcm_token,
                'Order Ready!',
                "Order for {$order->table->name} is ready to serve",
                [
                    'type' => 'order_ready',
                    'order_id' => $order->id,
                    'table_name' => $order->table->name,
                ]
            );
        }
    }

    public function notifyTipReceived(Tip $tip): void
    {
        $waiter = $tip->waiter;

        if ($waiter && $waiter->fcm_token) {
            $this->sendToDevice(
                $waiter->fcm_token,
                'Tip Received!',
                "You received TZS " . number_format($tip->amount) . " tip",
                [
                    'type' => 'tip_received',
                    'order_id' => $tip->order_id,
                    'amount' => $tip->amount,
                ]
            );
        }
    }
}
```

---

## 12. Deployment & DevOps

### 12.1 Environment Configuration

```bash
# .env.production

APP_NAME="Smart Dining POS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pos.smartdining.co.tz

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_dining_pos
DB_USERNAME=pos_user
DB_PASSWORD=secure_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Reverb WebSocket
REVERB_APP_ID=smart-dining-pos
REVERB_APP_KEY=your-reverb-key
REVERB_APP_SECRET=your-reverb-secret
REVERB_HOST=ws.smartdining.co.tz
REVERB_PORT=443

# Firebase
FIREBASE_PROJECT_ID=smart-dining-pos
FIREBASE_CREDENTIALS=storage/firebase-service-account.json

# Payment Gateways
MPESA_CONSUMER_KEY=xxx
MPESA_CONSUMER_SECRET=xxx
MPESA_SHORTCODE=xxx
MPESA_PASSKEY=xxx

PESAPAL_CONSUMER_KEY=xxx
PESAPAL_CONSUMER_SECRET=xxx
```

### 12.2 Docker Compose

```yaml
# docker-compose.yml

version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: smart-dining-pos-app
    restart: unless-stopped
    volumes:
      - .:/var/www/html
      - ./storage:/var/www/html/storage
    networks:
      - smart-dining-network
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:alpine
    container_name: smart-dining-pos-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/conf.d:/etc/nginx/conf.d
      - ./docker/nginx/ssl:/etc/nginx/ssl
    networks:
      - smart-dining-network
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: smart-dining-pos-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: smart_dining_pos
      MYSQL_USER: pos_user
      MYSQL_PASSWORD: secure_password
      MYSQL_ROOT_PASSWORD: root_password
    volumes:
      - mysql-data:/var/lib/mysql
    networks:
      - smart-dining-network

  redis:
    image: redis:alpine
    container_name: smart-dining-pos-redis
    restart: unless-stopped
    volumes:
      - redis-data:/data
    networks:
      - smart-dining-network

  reverb:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: smart-dining-pos-reverb
    restart: unless-stopped
    command: php artisan reverb:start
    volumes:
      - .:/var/www/html
    networks:
      - smart-dining-network
    depends_on:
      - redis

  queue:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: smart-dining-pos-queue
    restart: unless-stopped
    command: php artisan queue:work --tries=3
    volumes:
      - .:/var/www/html
    networks:
      - smart-dining-network
    depends_on:
      - mysql
      - redis

  scheduler:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: smart-dining-pos-scheduler
    restart: unless-stopped
    command: php artisan schedule:work
    volumes:
      - .:/var/www/html
    networks:
      - smart-dining-network
    depends_on:
      - mysql
      - redis

networks:
  smart-dining-network:
    driver: bridge

volumes:
  mysql-data:
  redis-data:
```

---

## 13. Testing Strategy

### 13.1 Feature Tests

```php
<?php
// tests/Feature/OrderTest.php

namespace Tests\Feature;

use App\Models\{Staff, Guest, Table, MenuItem, Order};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private Staff $waiter;
    private Guest $guest;
    private Table $table;
    private MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->waiter = Staff::factory()->waiter()->create();
        $this->guest = Guest::factory()->create();
        $this->table = Table::factory()->create();
        $this->menuItem = MenuItem::factory()->create(['price' => 25.00]);
    }

    public function test_waiter_can_create_order(): void
    {
        $response = $this->actingAs($this->waiter, 'sanctum')
            ->postJson('/api/v1/orders', [
                'guest_id' => $this->guest->id,
                'table_id' => $this->table->id,
                'items' => [
                    [
                        'menu_item_id' => $this->menuItem->id,
                        'quantity' => 2,
                        'special_instructions' => 'No onions',
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.table.id', $this->table->id)
            ->assertJsonPath('data.items.0.quantity', 2);

        $this->assertDatabaseHas('orders', [
            'guest_id' => $this->guest->id,
            'table_id' => $this->table->id,
            'waiter_id' => $this->waiter->id,
        ]);
    }

    public function test_order_calculates_totals_correctly(): void
    {
        $order = Order::factory()
            ->for($this->guest)
            ->for($this->table)
            ->for($this->waiter, 'waiter')
            ->create();

        $order->items()->create([
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 2,
            'unit_price' => 25.00,
            'subtotal' => 50.00,
        ]);

        $order->calculateTotals();

        $this->assertEquals(50.00, $order->subtotal);
        $this->assertEquals(9.00, $order->tax); // 18%
        $this->assertEquals(2.50, $order->service_charge); // 5%
        $this->assertEquals(61.50, $order->total);
    }

    public function test_chef_can_mark_item_as_done(): void
    {
        $chef = Staff::factory()->chef()->create();
        $order = Order::factory()->create();
        $orderItem = $order->items()->create([
            'menu_item_id' => $this->menuItem->id,
            'quantity' => 1,
            'unit_price' => 25.00,
            'subtotal' => 25.00,
            'status' => 'preparing',
        ]);

        $response = $this->actingAs($chef, 'sanctum')
            ->postJson("/api/v1/order-items/{$orderItem->id}/done");

        $response->assertStatus(200);

        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'status' => 'ready',
        ]);
    }
}
```

### 13.2 Unit Tests

```php
<?php
// tests/Unit/Services/PaymentServiceTest.php

namespace Tests\Unit\Services;

use App\Models\{Order, Payment};
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentService = new PaymentService();
        $this->order = Order::factory()->create([
            'subtotal' => 100.00,
            'tax' => 18.00,
            'service_charge' => 5.00,
            'total' => 123.00,
        ]);
    }

    public function test_generate_bill_returns_correct_structure(): void
    {
        $bill = $this->paymentService->generateBill($this->order);

        $this->assertArrayHasKey('subtotal', $bill);
        $this->assertArrayHasKey('tax_amount', $bill);
        $this->assertArrayHasKey('total', $bill);
        $this->assertArrayHasKey('tip_suggestions', $bill);

        $this->assertEquals(100.00, $bill['subtotal']);
        $this->assertEquals(123.00, $bill['total']);
    }

    public function test_tip_suggestions_are_calculated_correctly(): void
    {
        $bill = $this->paymentService->generateBill($this->order);

        $this->assertEquals(12.30, $bill['tip_suggestions']['10_percent']);
        $this->assertEquals(18.45, $bill['tip_suggestions']['15_percent']);
        $this->assertEquals(24.60, $bill['tip_suggestions']['20_percent']);
    }

    public function test_cash_payment_calculates_change(): void
    {
        $result = $this->paymentService->processCashPayment(
            $this->order,
            150.00 // Cash received
        );

        $this->assertEquals(27.00, $result['change']);
        $this->assertEquals('completed', $result['payment']->status);
    }
}
```

---

## 14. Implementation Phases

### Phase 1: Core Backend Enhancement (Week 1-2)

#### Tasks:
- [ ] Add new database migrations (inventory, shifts, daily_reports, audit_logs, notifications)
- [ ] Create factory classes for all models
- [ ] Implement comprehensive seeders
- [ ] Add missing model relationships
- [ ] Implement ReportService
- [ ] Add audit logging middleware
- [ ] Set up Redis cache configuration
- [ ] Configure queue workers

#### Deliverables:
- Database migrations ready
- Seeders with realistic test data
- Basic reporting functionality

---

### Phase 2: API Completion (Week 2-3)

#### Tasks:
- [ ] Complete all API endpoints per specification
- [ ] Add request validation classes
- [ ] Implement API rate limiting
- [ ] Add API documentation (OpenAPI/Swagger)
- [ ] Implement offline sync endpoints
- [ ] Add FCM push notification service
- [ ] Write API feature tests

#### Deliverables:
- Complete REST API
- API documentation
- Test coverage > 80%

---

### Phase 3: Kitchen Display System (Week 3-4)

#### Tasks:
- [ ] Create KDS Blade layouts
- [ ] Implement KitchenDisplay Livewire component
- [ ] Add order timer functionality
- [ ] Implement bump screen
- [ ] Add audio alerts
- [ ] Style with Tailwind CSS
- [ ] Add keyboard shortcuts
- [ ] Test real-time updates

#### Deliverables:
- Functional Kitchen Display System
- Real-time order streaming
- Touch-friendly interface

---

### Phase 4: Bar Display System (Week 4)

#### Tasks:
- [ ] Create Bar Display Blade layouts
- [ ] Implement BarDisplay Livewire component
- [ ] Add drink queue management
- [ ] Implement quick stats panel
- [ ] Style consistent with KDS

#### Deliverables:
- Functional Bar Display System
- Real-time drink orders

---

### Phase 5: Manager Dashboard (Week 5-6)

#### Tasks:
- [ ] Create dashboard layout
- [ ] Implement Dashboard Livewire component
- [ ] Create Orders management page
- [ ] Create Menu management page
- [ ] Create Staff management page
- [ ] Create Tables management page
- [ ] Create Payments view
- [ ] Implement Reports with PDF export
- [ ] Add settings page
- [ ] Style with Tailwind CSS

#### Deliverables:
- Complete Manager Dashboard
- All CRUD operations
- Report generation

---

### Phase 6: Real-Time Integration (Week 6-7)

#### Tasks:
- [ ] Set up Laravel Reverb
- [ ] Configure WebSocket channels
- [ ] Implement all event broadcasts
- [ ] Add frontend Echo integration
- [ ] Test real-time updates across all views
- [ ] Implement notification system

#### Deliverables:
- WebSocket server running
- Real-time updates working
- Push notifications functional

---

### Phase 7: Testing & Documentation (Week 7-8)

#### Tasks:
- [ ] Write remaining unit tests
- [ ] Write feature tests
- [ ] Write integration tests
- [ ] Performance testing
- [ ] Security audit
- [ ] API documentation update
- [ ] User documentation
- [ ] Deployment documentation

#### Deliverables:
- Test coverage > 85%
- Complete documentation
- Security sign-off

---

### Phase 8: Deployment & Launch (Week 8)

#### Tasks:
- [ ] Set up production environment
- [ ] Configure SSL certificates
- [ ] Set up monitoring (Laravel Telescope, Sentry)
- [ ] Configure backups
- [ ] Deploy to production
- [ ] Performance optimization
- [ ] Staff training
- [ ] Go live

#### Deliverables:
- Production system live
- Monitoring in place
- Staff trained

---

## Appendix A: File Structure After Implementation

```
laravel-app/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── GenerateDailyReport.php
│   │       └── CleanOldOrders.php
│   ├── Events/
│   │   ├── OrderCreated.php
│   │   ├── OrderStatusChanged.php
│   │   ├── OrderItemReady.php
│   │   ├── PaymentReceived.php
│   │   └── TipReceived.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── OrderItemController.php
│   │   │   │   ├── MenuController.php
│   │   │   │   ├── TableController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   ├── TipController.php
│   │   │   │   ├── GuestController.php
│   │   │   │   ├── StaffController.php
│   │   │   │   ├── ReportController.php
│   │   │   │   ├── ShiftController.php
│   │   │   │   └── SyncController.php
│   │   │   └── Web/
│   │   │       ├── AuthController.php
│   │   │       ├── ManagerController.php
│   │   │       ├── KitchenController.php
│   │   │       └── BarController.php
│   │   ├── Middleware/
│   │   │   ├── CheckRole.php
│   │   │   └── AuditLog.php
│   │   ├── Requests/
│   │   │   ├── CreateOrderRequest.php
│   │   │   ├── CreatePaymentRequest.php
│   │   │   └── ... (validation requests)
│   │   └── Resources/
│   │       ├── OrderResource.php
│   │       ├── OrderCollection.php
│   │       └── ... (API resources)
│   ├── Jobs/
│   │   ├── ProcessPayment.php
│   │   ├── SendPushNotification.php
│   │   ├── SyncOfflineOrders.php
│   │   └── GenerateDailyReport.php
│   ├── Listeners/
│   │   ├── SendOrderNotification.php
│   │   ├── NotifyWaiter.php
│   │   ├── UpdateKitchenDisplay.php
│   │   └── LogAuditEvent.php
│   ├── Livewire/
│   │   ├── Kitchen/
│   │   │   ├── KitchenDisplay.php
│   │   │   └── OrderCard.php
│   │   ├── Bar/
│   │   │   ├── BarDisplay.php
│   │   │   └── DrinkCard.php
│   │   └── Manager/
│   │       ├── Dashboard.php
│   │       ├── OrdersTable.php
│   │       ├── MenuManager.php
│   │       ├── StaffManager.php
│   │       ├── TablesGrid.php
│   │       └── ReportsPanel.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Staff.php
│   │   ├── Guest.php
│   │   ├── Table.php
│   │   ├── MenuItem.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Payment.php
│   │   ├── Tip.php
│   │   ├── GuestSession.php
│   │   ├── Shift.php
│   │   ├── DailyReport.php
│   │   ├── Inventory.php
│   │   ├── AuditLog.php
│   │   └── Notification.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── EventServiceProvider.php
│   │   └── AuthServiceProvider.php
│   └── Services/
│       ├── OrderService.php
│       ├── PaymentService.php
│       ├── TipService.php
│       ├── MenuService.php
│       ├── ReportService.php
│       ├── ShiftService.php
│       ├── FCMService.php
│       ├── AuditService.php
│       └── SyncService.php
├── config/
│   ├── reverb.php
│   ├── firebase.php
│   └── pos.php
├── database/
│   ├── factories/
│   │   ├── StaffFactory.php
│   │   ├── GuestFactory.php
│   │   ├── TableFactory.php
│   │   ├── MenuItemFactory.php
│   │   ├── OrderFactory.php
│   │   └── PaymentFactory.php
│   ├── migrations/
│   │   └── ... (all migrations)
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── StaffSeeder.php
│       ├── MenuSeeder.php
│       ├── TableSeeder.php
│       └── GuestSeeder.php
├── resources/
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   ├── app.js
│   │   └── echo-setup.js
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php
│       │   ├── kds.blade.php
│       │   ├── bar.blade.php
│       │   └── manager.blade.php
│       ├── livewire/
│       │   ├── kitchen/
│       │   │   ├── kitchen-display.blade.php
│       │   │   └── order-card.blade.php
│       │   ├── bar/
│       │   │   ├── bar-display.blade.php
│       │   │   └── drink-card.blade.php
│       │   └── manager/
│       │       ├── dashboard.blade.php
│       │       ├── orders-table.blade.php
│       │       ├── menu-manager.blade.php
│       │       ├── staff-manager.blade.php
│       │       └── reports-panel.blade.php
│       ├── reports/
│       │   ├── daily.blade.php
│       │   └── sales.blade.php
│       ├── auth/
│       │   └── login.blade.php
│       └── components/
│           ├── alert.blade.php
│           ├── modal.blade.php
│           └── stats-card.blade.php
├── routes/
│   ├── api.php
│   ├── web.php
│   └── channels.php
├── storage/
│   └── firebase-service-account.json
├── tests/
│   ├── Feature/
│   │   ├── OrderTest.php
│   │   ├── PaymentTest.php
│   │   ├── KitchenDisplayTest.php
│   │   └── ManagerDashboardTest.php
│   └── Unit/
│       ├── Services/
│       │   ├── OrderServiceTest.php
│       │   └── PaymentServiceTest.php
│       └── Models/
│           └── OrderTest.php
├── .env.example
├── docker-compose.yml
├── Dockerfile
└── README.md
```

---

## Appendix B: API Response Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 200 | OK | Successful GET, PUT |
| 201 | Created | Successful POST |
| 204 | No Content | Successful DELETE |
| 400 | Bad Request | Invalid input |
| 401 | Unauthorized | Missing/invalid token |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Server Error | Internal error |

---

## Appendix C: WebSocket Channels

| Channel | Events | Subscribers |
|---------|--------|-------------|
| `orders` | OrderCreated, OrderStatusChanged | Manager, All Staff |
| `kitchen` | OrderCreated, OrderItemUpdated | Chef |
| `bar` | OrderCreated, OrderItemUpdated | Bartender |
| `waiter.{id}` | OrderItemReady, TipReceived | Individual Waiter |
| `table.{id}` | OrderUpdated | Table-specific updates |

---

*Document Version: 1.0*
*Last Updated: February 2025*
*Author: Smart Dining POS Development Team*