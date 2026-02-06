<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\TipController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/login-pin', [AuthController::class, 'loginWithPin']);
Route::get('auth/staff-list', [AuthController::class, 'getStaffForPinLogin']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/set-pin', [AuthController::class, 'setPin']);
});

// Public menu routes
Route::get('menu', [MenuController::class, 'index']);
Route::get('menu/items', [MenuController::class, 'items']);
Route::get('menu/categories', [MenuController::class, 'categories']);
Route::get('menu/popular', [MenuController::class, 'popular']);
Route::get('menu/search', [MenuController::class, 'search']);
Route::get('menu/{id}', [MenuController::class, 'show']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {

    // Waiter routes: can create orders, view own orders, process payments
    Route::middleware(['api.role:waiter,manager,admin'])->group(function () {
        Route::post('orders', [OrderController::class, 'store']);
        Route::post('orders/{id}/items', [OrderController::class, 'addItems']);
        Route::post('orders/{id}/serve', [OrderController::class, 'markAsServed']);
        Route::patch('tables/{id}/status', [TableController::class, 'updateStatus']);

        // Payments (waiter, manager, admin)
        Route::post('payments', [PaymentController::class, 'store']);
        Route::post('payments/{id}/confirm', [PaymentController::class, 'confirm']);
        Route::post('payments/stripe/create-intent', [App\Http\Controllers\StripePaymentController::class, 'createIntent']);
        Route::post('payments/stripe/confirm', [App\Http\Controllers\StripePaymentController::class, 'confirm']);

        // Tips (waiter, manager, admin)
        Route::post('tips', [TipController::class, 'store']);
        Route::get('orders/{orderId}/tip-suggestions', [TipController::class, 'suggestions']);

        // Guests (waiter, manager, admin)
        Route::get('guests/phone/{phone}', [GuestController::class, 'findByPhone']);
        Route::post('guests', [GuestController::class, 'store']);
    });

    // Chef and Bartender routes: can view kitchen/bar orders, update item prep_status
    // (validation for kitchen vs bar items happens in controller)
    Route::middleware(['api.role:chef,bartender,manager,admin'])->group(function () {
        Route::get('order-items/pending', [OrderItemController::class, 'pending']);
        Route::post('order-items/{id}/received', [OrderItemController::class, 'markReceived']);
        Route::post('order-items/{id}/done', [OrderItemController::class, 'markDone']);
    });

    // Orders - accessible by waiters, chefs, bartenders, managers, admins
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::get('orders/{id}/receipt', [OrderController::class, 'generateReceipt']);
    Route::get('orders/{orderId}/bill', [PaymentController::class, 'getBill']);

    // Order status updates - managers and admins only
    Route::middleware(['api.role:manager,admin'])->group(function () {
        Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus']);
        Route::post('orders/{id}/cancel', [OrderController::class, 'cancel']);
    });

    // Tables - accessible by all authenticated staff
    Route::get('tables', [TableController::class, 'index']);
    Route::get('tables/{id}', [TableController::class, 'show']);

    // Payments view - accessible by all authenticated staff
    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{id}', [PaymentController::class, 'show']);

    // Manager and Admin only routes
    Route::middleware(['api.role:manager,admin'])->group(function () {
        // Staff PIN management (managers set PINs for staff)
        Route::post('staff/{staffId}/pin', [AuthController::class, 'setStaffPin']);

        // Menu management
        Route::put('menu/{id}/availability', [MenuController::class, 'updateAvailability']);
        Route::get('menu/stats', [MenuController::class, 'stats']);

        // QR Codes
        Route::get('qr-codes/tables/{tableId}', [App\Http\Controllers\WhatsApp\QRCodeController::class, 'show']);
        Route::post('qr-codes/tables/{tableId}/generate', [App\Http\Controllers\WhatsApp\QRCodeController::class, 'generate']);
        Route::post('qr-codes/generate-all', [App\Http\Controllers\WhatsApp\QRCodeController::class, 'generateAll']);
    });
});

// WhatsApp webhook routes (no authentication required)
Route::prefix('webhooks')->group(function () {
    Route::get('whatsapp', [App\Http\Controllers\WhatsApp\WebhookController::class, 'verify']);
    Route::post('whatsapp', [App\Http\Controllers\WhatsApp\WebhookController::class, 'handle']);

    // Stripe webhook (signature verification handled in controller)
    Route::post('stripe', [App\Http\Controllers\StripeWebhookController::class, 'handle']);
});

// Test error handling routes for API (development only - should be removed in production)
if (config('app.env') !== 'production') {
    Route::prefix('test-errors')->group(function () {
        // Test 404 error
        Route::get('404', function () {
            abort(404, 'Resource not found');
        });

        // Test 500 error
        Route::get('500', function () {
            throw new \Exception('Test 500 error for API');
        });

        // Test validation error (422)
        Route::post('validation', function () {
            request()->validate([
                'email' => 'required|email',
                'name' => 'required|min:3',
                'age' => 'required|integer|min:18',
            ]);
        });

        // Test unauthorized error (403)
        Route::get('unauthorized', function () {
            abort(403, 'Unauthorized API access');
        });

        // Test unauthenticated error (401)
        Route::middleware('auth:sanctum')->get('unauthenticated', function () {
            return response()->json(['message' => 'Authenticated']);
        });

        // Test OrderWorkflowException
        Route::get('order-workflow', function () {
            throw \App\Exceptions\OrderWorkflowException::invalidTransition('pending', 'completed');
        });

        // Test PaymentException
        Route::get('payment', function () {
            throw new \App\Exceptions\PaymentException('Payment processing failed');
        });

        // Test InventoryException
        Route::get('inventory', function () {
            throw new \App\Exceptions\InventoryException('Insufficient stock');
        });
    });
}
