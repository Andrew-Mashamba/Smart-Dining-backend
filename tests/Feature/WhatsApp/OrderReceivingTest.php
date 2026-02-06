<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Guest;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReceivingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test menu data
        $category = MenuCategory::create([
            'name' => 'Main Course',
            'description' => 'Delicious main dishes',
            'display_order' => 1,
            'status' => 'active',
        ]);

        MenuItem::create([
            'category_id' => $category->id,
            'name' => 'Pizza',
            'description' => 'Margherita pizza',
            'price' => 85.00,
            'prep_area' => 'kitchen',
            'prep_time_minutes' => 20,
            'status' => 'available',
        ]);

        MenuItem::create([
            'category_id' => $category->id,
            'name' => 'Burger',
            'description' => 'Beef burger with fries',
            'price' => 65.00,
            'prep_area' => 'kitchen',
            'prep_time_minutes' => 15,
            'status' => 'available',
        ]);
    }

    /**
     * Test webhook verification with valid token
     */
    public function test_webhook_verification_with_valid_token(): void
    {
        config(['services.whatsapp.verify_token' => 'test_verify_token']);

        $response = $this->get('/webhooks/whatsapp?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'test_verify_token',
            'hub_challenge' => 'challenge_string_123',
        ]));

        $response->assertStatus(200);
        $response->assertSee('challenge_string_123');
    }

    /**
     * Test webhook verification with invalid token
     */
    public function test_webhook_verification_with_invalid_token(): void
    {
        config(['services.whatsapp.verify_token' => 'correct_token']);

        $response = $this->get('/webhooks/whatsapp?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong_token',
            'hub_challenge' => 'challenge_string_123',
        ]));

        $response->assertStatus(403);
    }

    /**
     * Test receiving WhatsApp message and creating order
     */
    public function test_receive_order_message_creates_order(): void
    {
        // Mock WhatsApp webhook payload
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '26771234567',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'order Pizza x 2, Burger x 1',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Partial mock to allow actual order processing but prevent API calls
        $mockService = \Mockery::mock(WhatsAppService::class)->makePartial();
        $mockService->shouldReceive('sendMessage')->andReturn(null);
        $mockService->shouldReceive('sendOrderConfirmation')->andReturn(null);
        $this->app->instance(WhatsAppService::class, $mockService);

        $response = $this->postJson('/webhooks/whatsapp', $payload);

        $response->assertStatus(200);

        // Verify guest was created
        $this->assertDatabaseHas('guests', [
            'phone_number' => '26771234567',
        ]);

        // Verify order was created
        $guest = Guest::where('phone_number', '26771234567')->first();
        $this->assertNotNull($guest);

        $order = Order::where('guest_id', $guest->id)
            ->where('order_source', 'whatsapp')
            ->first();

        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals('whatsapp', $order->order_source);

        // Verify order items were created
        $this->assertEquals(2, $order->orderItems->count());
    }

    /**
     * Test menu command
     */
    public function test_menu_command(): void
    {
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '26771234567',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'menu',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Mock the WhatsApp service
        $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('sendMenu')
                ->once()
                ->with('26771234567');
        });

        $response = $this->postJson('/webhooks/whatsapp', $payload);

        $response->assertStatus(200);
    }

    /**
     * Test help command
     */
    public function test_help_command(): void
    {
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '26771234567',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'help',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Mock the WhatsApp service
        $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('sendHelpMessage')
                ->once()
                ->with('26771234567');
        });

        $response = $this->postJson('/webhooks/whatsapp', $payload);

        $response->assertStatus(200);
    }

    /**
     * Test status command
     */
    public function test_status_command(): void
    {
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '26771234567',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'status',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Mock the WhatsApp service
        $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('sendRecentOrderStatus')
                ->once()
                ->with('26771234567');
        });

        $response = $this->postJson('/webhooks/whatsapp', $payload);

        $response->assertStatus(200);
    }

    /**
     * Test unknown command sends help message
     */
    public function test_unknown_command_sends_help(): void
    {
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '26771234567',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'invalid command',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Mock the WhatsApp service
        $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('sendHelpMessage')
                ->once()
                ->with('26771234567');
        });

        $response = $this->postJson('/webhooks/whatsapp', $payload);

        $response->assertStatus(200);
    }

    /**
     * Test order status update sends WhatsApp notification
     */
    public function test_order_status_update_sends_notification(): void
    {
        // Create a guest and order
        $guest = Guest::create([
            'phone_number' => '26771234567',
            'name' => 'Test Guest',
        ]);

        $order = Order::create([
            'guest_id' => $guest->id,
            'order_source' => 'whatsapp',
            'status' => 'pending',
            'subtotal' => 100.00,
            'tax' => 18.00,
            'total' => 118.00,
        ]);

        // Mock the WhatsApp service
        $this->mock(WhatsAppService::class, function ($mock) use ($order) {
            $mock->shouldReceive('sendOrderStatusUpdate')
                ->once()
                ->with(\Mockery::on(function ($arg) use ($order) {
                    return $arg->id === $order->id;
                }), 'preparing');
        });

        // Update order status
        $order->update(['status' => 'preparing']);
    }

    /**
     * Test non-text messages are ignored
     */
    public function test_non_text_messages_are_ignored(): void
    {
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '26771234567',
                                        'type' => 'image',
                                        'image' => [
                                            'id' => 'image_id',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/webhooks/whatsapp', $payload);

        $response->assertStatus(200);

        // No order should be created
        $this->assertDatabaseCount('orders', 0);
    }
}
