<?php

namespace Tests\Feature;

use App\Exceptions\InventoryException;
use App\Exceptions\OrderWorkflowException;
use App\Exceptions\PaymentException;
use App\Models\ErrorLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear error logs before each test
        ErrorLog::truncate();
    }

    /**
     * Test 404 error page rendering.
     */
    public function test_404_error_page_renders_correctly(): void
    {
        $response = $this->get('/non-existent-page');

        $response->assertStatus(404);
        $response->assertSee('404');
        $response->assertSee('Page Not Found');
        $response->assertSee('bg-gray-50');
        $response->assertSee('bg-white');
        $response->assertSee('text-gray-900');
        $response->assertSee('text-gray-600');
    }

    /**
     * Test 500 error page rendering.
     */
    public function test_500_error_page_renders_correctly(): void
    {
        if (config('app.env') === 'production') {
            $this->markTestSkipped('Test routes disabled in production');
        }

        $response = $this->get('/test-errors/500');

        $response->assertStatus(500);
        $response->assertSee('500');
        $response->assertSee('Server Error');
        $response->assertSee('bg-gray-50');
        $response->assertSee('bg-white');
    }

    /**
     * Test API 404 error returns JSON.
     */
    public function test_api_404_returns_json(): void
    {
        if (config('app.env') === 'production') {
            $this->markTestSkipped('Test routes disabled in production');
        }

        $response = $this->getJson('/api/test-errors/404');

        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Resource not found',
        ]);
    }

    /**
     * Test API 500 error returns JSON.
     */
    public function test_api_500_returns_json(): void
    {
        if (config('app.env') === 'production') {
            $this->markTestSkipped('Test routes disabled in production');
        }

        $response = $this->getJson('/api/test-errors/500');

        $response->assertStatus(500);
        $response->assertJson([
            'status' => 'error',
        ]);
        $response->assertJsonStructure([
            'status',
            'message',
        ]);
    }

    /**
     * Test validation error returns 422 with field-specific errors.
     */
    public function test_validation_error_returns_422_with_errors(): void
    {
        if (config('app.env') === 'production') {
            $this->markTestSkipped('Test routes disabled in production');
        }

        $response = $this->postJson('/api/test-errors/validation', []);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
        ]);
        $response->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'email',
                'name',
                'age',
            ],
        ]);
    }

    /**
     * Test unauthorized (403) error returns JSON.
     */
    public function test_api_unauthorized_returns_json(): void
    {
        if (config('app.env') === 'production') {
            $this->markTestSkipped('Test routes disabled in production');
        }

        $response = $this->getJson('/api/test-errors/unauthorized');

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Unauthorized API access',
        ]);
    }

    /**
     * Test OrderWorkflowException is logged to critical channel.
     */
    public function test_order_workflow_exception_logs_to_critical(): void
    {
        if (config('app.env') === 'production') {
            $this->markTestSkipped('Test routes disabled in production');
        }

        Log::spy();

        $response = $this->getJson('/api/test-errors/order-workflow');

        $response->assertStatus(500);

        // Verify error was logged to critical channel
        Log::shouldHaveReceived('channel')
            ->with('critical')
            ->once();
    }

    /**
     * Test PaymentException is logged to critical channel.
     */
    public function test_payment_exception_logs_to_critical(): void
    {
        if (config('app.env') === 'production') {
            $this->markTestSkipped('Test routes disabled in production');
        }

        Log::spy();

        $response = $this->getJson('/api/test-errors/payment');

        $response->assertStatus(500);

        // Verify error was logged to critical channel
        Log::shouldHaveReceived('channel')
            ->with('critical')
            ->once();
    }

    /**
     * Test InventoryException is logged to critical channel.
     */
    public function test_inventory_exception_logs_to_critical(): void
    {
        if (config('app.env') === 'production') {
            $this->markTestSkipped('Test routes disabled in production');
        }

        Log::spy();

        $response = $this->getJson('/api/test-errors/inventory');

        $response->assertStatus(500);

        // Verify error was logged to critical channel
        Log::shouldHaveReceived('channel')
            ->with('critical')
            ->once();
    }

    /**
     * Test errors are logged to database.
     */
    public function test_errors_logged_to_database(): void
    {
        if (config('app.env') === 'production') {
            $this->markTestSkipped('Test routes disabled in production');
        }

        // Clear error logs
        ErrorLog::truncate();

        // Manually create error log to test database logging
        ErrorLog::create([
            'message' => 'Test error message',
            'level' => 'error',
            'context' => json_encode(['test' => 'data']),
        ]);

        // Check database for error log
        $this->assertDatabaseCount('error_logs', 1);

        $errorLog = ErrorLog::first();
        $this->assertNotNull($errorLog);
        $this->assertEquals('Test error message', $errorLog->message);
        $this->assertEquals('error', $errorLog->level);
        $this->assertNotNull($errorLog->created_at);
    }

    /**
     * Test API request logging middleware.
     */
    public function test_api_requests_are_logged(): void
    {
        $user = User::factory()->create([
            'role' => 'waiter',
        ]);

        // Clear main log file
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        // Make an API request
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/menu');

        // Check log file for API request log
        $logContents = file_get_contents($logFile);
        $this->assertStringContainsString('API Request', $logContents);
        $this->assertStringContainsString('/api/menu', $logContents);
        $this->assertStringContainsString('GET', $logContents);
    }

    /**
     * Test log channels configuration.
     */
    public function test_log_channels_configured_correctly(): void
    {
        $this->assertEquals('stack', config('logging.default'));

        $channels = config('logging.channels');

        // Daily channel
        $this->assertArrayHasKey('daily', $channels);
        $this->assertEquals('daily', $channels['daily']['driver']);
        $this->assertEquals(14, $channels['daily']['days']);

        // Critical channel
        $this->assertArrayHasKey('critical', $channels);
        $this->assertEquals('daily', $channels['critical']['driver']);
        $this->assertEquals('critical', $channels['critical']['level']);
        $this->assertEquals(30, $channels['critical']['days']);

        // Database channel
        $this->assertArrayHasKey('database', $channels);
        $this->assertEquals('monolog', $channels['database']['driver']);
        $this->assertEquals(\App\Logging\DatabaseHandler::class, $channels['database']['handler']);

        // Slack channel (optional)
        $this->assertArrayHasKey('slack', $channels);
        $this->assertEquals('slack', $channels['slack']['driver']);
    }

    /**
     * Test ErrorLog model casts and fillable attributes.
     */
    public function test_error_log_model_configuration(): void
    {
        $errorLog = new ErrorLog;

        $this->assertContains('message', $errorLog->getFillable());
        $this->assertContains('level', $errorLog->getFillable());
        $this->assertContains('context', $errorLog->getFillable());

        $casts = $errorLog->getCasts();
        $this->assertEquals('array', $casts['context']);
    }

    /**
     * Test ErrorLog model scopes.
     */
    public function test_error_log_scopes(): void
    {
        // Clear all error logs first
        ErrorLog::truncate();

        // Create test logs
        ErrorLog::create([
            'message' => 'Critical error',
            'level' => 'critical',
            'context' => ['test' => 'data'],
        ]);

        ErrorLog::create([
            'message' => 'Info message',
            'level' => 'info',
            'context' => ['test' => 'data'],
        ]);

        $oldLog = new ErrorLog([
            'message' => 'Old error',
            'level' => 'error',
            'context' => ['test' => 'data'],
        ]);
        $oldLog->created_at = now()->subDays(2);
        $oldLog->save();

        // Test level scope
        $criticalLogs = ErrorLog::level('critical')->get();
        $this->assertCount(1, $criticalLogs);
        $this->assertEquals('critical', $criticalLogs->first()->level);

        // Test recent scope (24 hours)
        $recentLogs = ErrorLog::recent(24)->get();
        $this->assertCount(2, $recentLogs);
    }
}
