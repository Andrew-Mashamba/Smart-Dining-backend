<?php

namespace App\Console\Commands;

use App\Models\Staff;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class PosTestCredentials extends Command
{
    protected $signature = 'pos:test-credentials';

    protected $description = 'Create or show test credentials and a long-lived API token for Android POS / curl (GET /api/tables, etc.)';

    /** Waiter abilities so token can access tables, orders, payments, etc. */
    private const WAITER_ABILITIES = [
        'waiter:access',
        'orders:create',
        'orders:view',
        'orders:view-own',
        'orders:update',
        'tables:view',
        'tables:update',
        'payments:create',
        'payments:process',
        'tips:create',
        'menu:view',
        'guests:manage',
    ];

    public function handle(): int
    {
        $email = 'pos-test@seacliff.com';
        $password = 'password';

        $staff = Staff::where('email', $email)->first();

        if (! $staff) {
            $staff = Staff::create([
                'name' => 'POS Test Waiter',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'waiter',
                'phone_number' => null,
                'status' => 'active',
            ]);
            $this->info("Created test staff: {$staff->email}");
        }

        // Revoke existing long-lived tokens so we only have one; create fresh token to display (plain text cannot be retrieved later)
        $staff->tokens()->where('name', 'pos-test-long-lived')->delete();
        $token = $staff->createToken('pos-test-long-lived', self::WAITER_ABILITIES);
        $plainToken = $token->plainTextToken;

        $baseUrl = config('app.url', 'http://localhost');
        $apiUrl = rtrim($baseUrl, '/') . '/api';

        $this->newLine();
        $this->info('--- POS Test Credentials (use for Android POS or curl) ---');
        $this->newLine();
        $this->line('Email:    ' . $email);
        $this->line('Password: ' . $password);
        $this->newLine();
        $this->line('Long-lived API token (use in Header):');
        $this->line($plainToken);
        $this->newLine();
        $this->line('Usage:');
        $this->line('  Header:  Authorization: Bearer ' . substr($plainToken, 0, 20) . '...');
        $this->newLine();
        $this->line('curl example (GET /api/tables):');
        $this->line('  curl -s -H "Accept: application/json" -H "Authorization: Bearer ' . $plainToken . '" "' . $apiUrl . '/tables"');
        $this->newLine();
        $this->line('Login via API (get a new token):');
        $this->line('  curl -s -X POST -H "Accept: application/json" -H "Content-Type: application/json" -d \'{"email":"' . $email . '","password":"' . $password . '","device_name":"pos"}\' "' . $apiUrl . '/auth/login"');
        $this->newLine();

        return self::SUCCESS;
    }
}
