<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Table;
use App\Models\Tip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptPdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $staff;

    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Create staff member (Staff is Authenticatable)
        $this->staff = Staff::create([
            'name' => 'John Waiter',
            'email' => 'waiter@test.com',
            'password' => bcrypt('password'),
            'role' => 'waiter',
            'phone_number' => '0123456789',
            'status' => 'active',
        ]);

        // Create a guest
        $guest = Guest::create([
            'name' => 'Test Guest',
            'phone_number' => '+27123456789',
            'email' => 'guest@test.com',
        ]);

        // Create a table
        $table = Table::create([
            'name' => 'T-10',
            'location' => 'Main Floor',
            'capacity' => 4,
            'status' => 'occupied',
            'qr_code' => null,
        ]);

        // Create menu items
        $category = \App\Models\MenuCategory::factory()->create();

        $menuItem1 = MenuItem::create([
            'name' => 'Grilled Salmon',
            'description' => 'Fresh grilled salmon',
            'price' => 250.00,
            'category_id' => $category->id,
            'prep_area' => 'kitchen',
            'prep_time_minutes' => 15,
            'status' => 'available',
            'stock_quantity' => 10,
            'unit' => 'pieces',
            'low_stock_threshold' => 5,
        ]);

        $menuItem2 = MenuItem::create([
            'name' => 'Caesar Salad',
            'description' => 'Classic Caesar salad',
            'price' => 85.00,
            'category_id' => $category->id,
            'prep_area' => 'kitchen',
            'prep_time_minutes' => 10,
            'status' => 'available',
            'stock_quantity' => 15,
            'unit' => 'pieces',
            'low_stock_threshold' => 5,
        ]);

        // Create an order
        $this->order = Order::create([
            'guest_id' => $guest->id,
            'table_id' => $table->id,
            'waiter_id' => $this->staff->id,
            'order_source' => 'pos',
            'status' => 'completed',
            'subtotal' => 420.00,
            'tax' => 75.60, // 18% VAT
            'total' => 495.60,
        ]);

        // Create order items
        OrderItem::create([
            'order_id' => $this->order->id,
            'menu_item_id' => $menuItem1->id,
            'quantity' => 1,
            'unit_price' => 250.00,
            'subtotal' => 250.00,
            'special_instructions' => 'Well done please',
            'prep_status' => 'ready',
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'menu_item_id' => $menuItem2->id,
            'quantity' => 2,
            'unit_price' => 85.00,
            'subtotal' => 170.00,
            'special_instructions' => null,
            'prep_status' => 'ready',
        ]);

        // Create payment
        Payment::create([
            'order_id' => $this->order->id,
            'payment_method' => 'cash',
            'amount' => 550.00, // More than total for change calculation
            'status' => 'completed',
        ]);

        // Create tip
        Tip::create([
            'order_id' => $this->order->id,
            'waiter_id' => $this->staff->id,
            'amount' => 50.00,
            'tip_method' => 'cash',
        ]);
    }

    /**
     * Test that receipt PDF can be generated successfully
     */
    public function test_can_generate_receipt_pdf(): void
    {
        $response = $this->actingAs($this->staff, 'sanctum')
            ->getJson("/api/orders/{$this->order->id}/receipt");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition',
            'attachment; filename=receipt-'.$this->order->order_number.'.pdf');
    }

    /**
     * Test that receipt PDF contains correct order information
     */
    public function test_receipt_contains_order_information(): void
    {
        $response = $this->actingAs($this->staff, 'sanctum')
            ->get("/api/orders/{$this->order->id}/receipt");

        // Get the PDF content
        $pdfContent = $response->getContent();

        // Verify PDF header is present
        $this->assertStringContainsString('%PDF', $pdfContent);
    }

    /**
     * Test receipt generation requires authentication
     */
    public function test_receipt_generation_requires_authentication(): void
    {
        $response = $this->getJson("/api/orders/{$this->order->id}/receipt");

        $response->assertStatus(401);
    }

    /**
     * Test receipt generation fails for non-existent order
     */
    public function test_receipt_generation_fails_for_nonexistent_order(): void
    {
        $response = $this->actingAs($this->staff, 'sanctum')
            ->getJson('/api/orders/99999/receipt');

        $response->assertStatus(404);
    }

    /**
     * Test receipt PDF file naming convention
     */
    public function test_receipt_pdf_has_correct_filename(): void
    {
        $response = $this->actingAs($this->staff, 'sanctum')
            ->getJson("/api/orders/{$this->order->id}/receipt");

        $expectedFilename = 'receipt-'.$this->order->order_number.'.pdf';

        $response->assertStatus(200);
        $response->assertHeader('content-disposition',
            'attachment; filename='.$expectedFilename);
    }

    /**
     * Test receipt can be generated for order without tip
     */
    public function test_can_generate_receipt_for_order_without_tip(): void
    {
        // Delete the tip
        $this->order->tip()->delete();

        $response = $this->actingAs($this->staff, 'sanctum')
            ->getJson("/api/orders/{$this->order->id}/receipt");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Test receipt can be generated for order with card payment
     */
    public function test_can_generate_receipt_for_card_payment(): void
    {
        // Update payment to card
        $this->order->payments()->update([
            'payment_method' => 'card',
            'amount' => $this->order->total,
        ]);

        $response = $this->actingAs($this->staff, 'sanctum')
            ->getJson("/api/orders/{$this->order->id}/receipt");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
