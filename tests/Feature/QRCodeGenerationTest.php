<?php

namespace Tests\Feature;

use App\Models\GuestSession;
use App\Models\Table;
use App\Models\User;
use App\Services\QRCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QRCodeGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected QRCodeService $qrCodeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qrCodeService = new QRCodeService;
        Storage::fake('public');
    }

    /** @test */
    public function it_can_generate_qr_code_for_a_table()
    {
        // Create a table
        $table = Table::create([
            'name' => 'Table 1',
            'location' => 'Main Dining',
            'capacity' => 4,
            'status' => 'available',
        ]);

        // Generate QR code
        $result = $this->qrCodeService->generateTableQR($table->id);

        // Assert QR code path was returned
        $this->assertIsString($result);
        $this->assertStringContainsString('qrcodes/', $result);
        $this->assertStringContainsString("{$table->id}.png", $result);

        // Assert table was updated with QR code path
        $table->refresh();
        $this->assertNotNull($table->qr_code);
        $this->assertEquals($result, $table->qr_code);

        // Assert a guest session was created
        $guestSession = GuestSession::where('table_id', $table->id)->first();
        $this->assertNotNull($guestSession);
        $this->assertNotNull($guestSession->session_token);
        $this->assertEquals($table->id, $guestSession->table_id);
        $this->assertNotNull($guestSession->started_at);
        $this->assertNull($guestSession->ended_at);
    }

    /** @test */
    public function it_can_regenerate_qr_code_for_a_table()
    {
        // Create a table
        $table = Table::create([
            'name' => 'Table 2',
            'location' => 'Patio',
            'capacity' => 6,
            'status' => 'available',
        ]);

        // Generate initial QR code
        $firstQrPath = $this->qrCodeService->generateTableQR($table->id);
        $firstSession = GuestSession::where('table_id', $table->id)->first();

        // Regenerate QR code
        $secondQrPath = $this->qrCodeService->regenerateTableQR($table->id);
        $sessions = GuestSession::where('table_id', $table->id)->get();

        // Assert two sessions exist
        $this->assertCount(2, $sessions);

        // Assert first session was closed
        $firstSession->refresh();
        $this->assertNotNull($firstSession->ended_at);

        // Assert new session is active
        $activeSession = GuestSession::where('table_id', $table->id)
            ->whereNull('ended_at')
            ->first();
        $this->assertNotNull($activeSession);
        $this->assertNotEquals($firstSession->session_token, $activeSession->session_token);
    }

    /** @test */
    public function guest_order_page_loads_with_valid_token()
    {
        // Create a table and generate QR code
        $table = Table::create([
            'name' => 'Table 3',
            'location' => 'Bar Area',
            'capacity' => 2,
            'status' => 'available',
        ]);

        $this->qrCodeService->generateTableQR($table->id);
        $guestSession = GuestSession::where('table_id', $table->id)->first();

        // Visit the guest order page with the token
        $response = $this->get("/guest/order?token={$guestSession->session_token}");

        $response->assertStatus(200);
        $response->assertSee($table->name);
        $response->assertSee($table->location);
        $response->assertSee($guestSession->session_token);
    }

    /** @test */
    public function guest_order_page_shows_error_with_invalid_token()
    {
        $response = $this->get('/guest/order?token=invalid-token-12345');

        $response->assertStatus(200);
        $response->assertSee('Session not found');
    }

    /** @test */
    public function guest_order_page_shows_error_with_no_token()
    {
        $response = $this->get('/guest/order');

        $response->assertStatus(200);
        $response->assertSee('Invalid QR code');
    }

    /** @test */
    public function guest_order_page_shows_error_with_closed_session()
    {
        // Create a table and generate QR code
        $table = Table::create([
            'name' => 'Table 4',
            'location' => 'VIP',
            'capacity' => 8,
            'status' => 'available',
        ]);

        $this->qrCodeService->generateTableQR($table->id);
        $guestSession = GuestSession::where('table_id', $table->id)->first();

        // Close the session
        $guestSession->close();

        // Try to visit the guest order page
        $response = $this->get("/guest/order?token={$guestSession->session_token}");

        $response->assertStatus(200);
        $response->assertSee('This session has ended');
    }

    /** @test */
    public function manager_can_generate_qr_code_via_livewire()
    {
        // Create a manager user
        $manager = User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@test.com',
            'role' => 'manager',
        ]);

        // Create a table
        $table = Table::create([
            'name' => 'Table 5',
            'location' => 'Terrace',
            'capacity' => 4,
            'status' => 'available',
        ]);

        // Act as manager and trigger QR generation
        $this->actingAs($manager)
            ->get('/tables')
            ->assertStatus(200);

        // Simulate Livewire action
        \Livewire\Livewire::actingAs($manager)
            ->test(\App\Livewire\TableManagement::class)
            ->call('generateQrCode', $table->id)
            ->assertHasNoErrors();

        // Assert QR code was generated
        $table->refresh();
        $this->assertNotNull($table->qr_code);
    }

    /** @test */
    public function manager_can_regenerate_qr_code_via_livewire()
    {
        // Create a manager user
        $manager = User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@test.com',
            'role' => 'manager',
        ]);

        // Create a table with existing QR code
        $table = Table::create([
            'name' => 'Table 6',
            'location' => 'Balcony',
            'capacity' => 2,
            'status' => 'available',
        ]);

        $this->qrCodeService->generateTableQR($table->id);
        $firstSession = GuestSession::where('table_id', $table->id)->first();

        // Regenerate QR code via Livewire
        \Livewire\Livewire::actingAs($manager)
            ->test(\App\Livewire\TableManagement::class)
            ->call('regenerateQrCode', $table->id)
            ->assertHasNoErrors();

        // Assert old session was closed and new one created
        $firstSession->refresh();
        $this->assertNotNull($firstSession->ended_at);

        $activeSessions = GuestSession::where('table_id', $table->id)
            ->whereNull('ended_at')
            ->count();
        $this->assertEquals(1, $activeSessions);
    }
}
