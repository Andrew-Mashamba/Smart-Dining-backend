<?php

use App\Http\Controllers\HelpController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\BarController;
use App\Http\Controllers\Web\KitchenController;
use App\Http\Controllers\Web\ManagerController;
use App\Http\Controllers\WhatsAppController;
use App\Livewire\BarDisplay;
use App\Livewire\CreateOrder;
use App\Livewire\Dashboard;
use App\Livewire\GuestManagement;
use App\Livewire\GuestOrder;
use App\Livewire\InventoryManagement;
use App\Livewire\InventoryReports;
use App\Livewire\KitchenDisplay;
use App\Livewire\MenuManagement;
use App\Livewire\OrderDetails;
use App\Livewire\OrdersList;
use App\Livewire\ProcessPayment;
use App\Livewire\Reports;
use App\Livewire\SalesReports;
use App\Livewire\SettingsManagement;
use App\Livewire\StaffManagement;
use App\Livewire\StaffReports;
use App\Livewire\TableManagement;
use App\Livewire\Users;
use Illuminate\Support\Facades\Route;

// Guest ordering route (public access via QR code)
Route::get('/guest/order', GuestOrder::class)->name('guest.order');

// WhatsApp webhook routes (public access for WhatsApp API)
Route::get('/webhooks/whatsapp', [WhatsAppController::class, 'verify'])->name('whatsapp.verify');
Route::post('/webhooks/whatsapp', [WhatsAppController::class, 'webhook'])->name('whatsapp.webhook');

// Stripe webhook route (public access for Stripe API, signature verification in controller)
Route::post('/webhooks/stripe', [App\Http\Controllers\StripeWebhookController::class, 'handle'])->name('stripe.webhook');

// Test broadcast route (development only)
Route::get('/test-broadcast', function () {
    return view('test-broadcasting');
})->middleware(['auth:web'])->name('test.broadcast');

// Test error handling routes (development only - should be removed in production)
if (config('app.env') !== 'production') {
    Route::prefix('test-errors')->group(function () {
        // Test 404 error
        Route::get('/404', function () {
            abort(404);
        });

        // Test 500 error
        Route::get('/500', function () {
            throw new \Exception('Test 500 error');
        });

        // Test validation error
        Route::post('/validation', function () {
            request()->validate([
                'email' => 'required|email',
                'name' => 'required|min:3',
            ]);
        });

        // Test unauthorized error
        Route::get('/unauthorized', function () {
            abort(403, 'Unauthorized access');
        });

        // Test OrderWorkflowException
        Route::get('/order-workflow', function () {
            throw \App\Exceptions\OrderWorkflowException::invalidTransition('pending', 'completed');
        });

        // Test PaymentException
        Route::get('/payment', function () {
            throw new \App\Exceptions\PaymentException('Payment processing failed');
        });

        // Test InventoryException
        Route::get('/inventory', function () {
            throw new \App\Exceptions\InventoryException('Insufficient stock');
        });

        // View error logs
        Route::get('/logs', function () {
            $logs = \App\Models\ErrorLog::orderBy('created_at', 'desc')->limit(50)->get();

            return response()->json($logs);
        });
    });
}

// Root route: Redirect guests to login, authenticated users to dashboard
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }

    return redirect()->route('login');
})->name('home');

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(['auth:web'])->group(function () {

    // Dashboard Livewire component route
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // Help and Documentation routes
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');
    Route::get('/help/{filename}', [HelpController::class, 'show'])->name('help.show');
    Route::get('/help/{filename}/pdf', [HelpController::class, 'exportPdf'])->name('help.pdf');

    // Users Livewire component route (admin and manager only)
    Route::get('/users', Users::class)->middleware(['role:admin,manager'])->name('users');

    // Staff Management Livewire component route (admin and manager only)
    Route::get('/staff', StaffManagement::class)->middleware(['auth', 'role:admin,manager'])->name('staff');

    // Reports Livewire component route (admin and manager only)
    Route::get('/reports', Reports::class)->middleware(['role:admin,manager'])->name('reports');

    // Sales Reports Livewire component route (admin and manager only)
    Route::get('/reports/sales', SalesReports::class)->middleware(['auth', 'role:manager,admin'])->name('reports.sales');

    // Staff Reports Livewire component route (admin and manager only)
    Route::get('/reports/staff', StaffReports::class)->middleware(['auth', 'role:manager,admin'])->name('reports.staff');

    // Inventory Reports Livewire component route (admin and manager only)
    Route::get('/reports/inventory', InventoryReports::class)->middleware(['auth', 'role:manager,admin'])->name('reports.inventory');

    // Menu Management Livewire component route
    Route::get('/menu', MenuManagement::class)->middleware(['auth', 'role:admin,manager'])->name('menu');

    // Inventory Management Livewire component route (admin and manager only)
    Route::get('/inventory', InventoryManagement::class)->middleware(['auth', 'role:manager,admin'])->name('inventory');

    // Table Management Livewire component route (admin and manager only)
    Route::get('/tables', TableManagement::class)->middleware(['auth', 'role:admin,manager'])->name('tables');

    // Guest Management Livewire component route (admin and manager only)
    Route::get('/guests', GuestManagement::class)->middleware(['auth', 'role:manager,admin'])->name('guests');

    // Settings Management Livewire component route (admin and manager only)
    Route::get('/settings', SettingsManagement::class)->middleware(['auth', 'role:admin,manager'])->name('settings');

    // Orders List Livewire component route (authenticated users)
    Route::get('/orders', OrdersList::class)->middleware(['auth'])->name('orders');

    // Create Order Livewire component route (waiter, manager, and admin access)
    Route::get('/orders/create', CreateOrder::class)->middleware(['auth', 'role:waiter,manager,admin'])->name('orders.create');

    // Order Details Livewire component route (authenticated users)
    Route::get('/orders/{order}', OrderDetails::class)->middleware(['auth'])->name('orders.show');

    // Process Payment Livewire component route (authenticated users)
    Route::get('/orders/{order}/payment', ProcessPayment::class)->middleware(['auth'])->name('orders.payment');

    // Stripe payment routes (authenticated users)
    Route::get('/payments/stripe/{order}', [App\Http\Controllers\Web\StripePaymentWebController::class, 'show'])->name('payments.stripe.form');
    Route::get('/payments/stripe/success', [App\Http\Controllers\Web\StripePaymentWebController::class, 'success'])->name('payments.stripe.success');

    // Kitchen Display System Livewire component route (chef, manager, and admin access)
    Route::get('/kitchen', KitchenDisplay::class)->middleware(['auth', 'role:chef,manager,admin'])->name('kitchen');

    // Bar Display System Livewire component route (bartender, manager, and admin access)
    Route::get('/bar', BarDisplay::class)->middleware(['auth', 'role:bartender,manager,admin'])->name('bar');

    // Manager Portal (admin and manager access)
    Route::middleware(['role:admin,manager'])->prefix('manager')->name('manager.')->group(function () {
        Route::get('/dashboard', [ManagerController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders/{orderId}/receipt', [ManagerController::class, 'generateReceipt'])->name('orders.receipt');
    });

    // Kitchen Display System (chef access)
    Route::middleware(['role:chef'])->prefix('kitchen')->name('kitchen.')->group(function () {
        Route::get('/display', [KitchenController::class, 'display'])->name('display');
        Route::post('/items/{id}/received', [KitchenController::class, 'markReceived'])->name('mark-received');
        Route::post('/items/{id}/done', [KitchenController::class, 'markDone'])->name('mark-done');
    });

    // Bar Display System (bartender access)
    Route::middleware(['role:bartender'])->prefix('bar')->name('bar.')->group(function () {
        Route::get('/display', [BarController::class, 'display'])->name('display');
        Route::post('/items/{id}/received', [BarController::class, 'markReceived'])->name('mark-received');
        Route::post('/items/{id}/done', [BarController::class, 'markDone'])->name('mark-done');
    });
});
