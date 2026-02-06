<?php

namespace Tests\Feature;

use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that login with valid credentials returns a token
     */
    public function test_login_returns_token(): void
    {
        // Create an active staff member
        $staff = Staff::factory()->create([
            'email' => 'waiter@example.com',
            'password' => bcrypt('password123'),
            'role' => 'waiter',
            'status' => 'active',
        ]);

        // Attempt login with valid credentials
        $response = $this->postJson('/api/auth/login', [
            'email' => 'waiter@example.com',
            'password' => 'password123',
        ]);

        // Assert response is successful
        $response->assertStatus(200);

        // Assert response contains required fields
        $response->assertJsonStructure([
            'message',
            'token',
            'user' => [
                'id',
                'name',
                'email',
                'role',
                'phone_number',
            ],
        ]);

        // Assert token is a non-empty string
        $this->assertNotEmpty($response->json('token'));
        $this->assertIsString($response->json('token'));

        // Assert user data is correct
        $this->assertEquals($staff->id, $response->json('user.id'));
        $this->assertEquals($staff->email, $response->json('user.email'));
        $this->assertEquals($staff->role, $response->json('user.role'));
    }

    /**
     * Test that login fails with invalid credentials
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        // Create a staff member
        Staff::factory()->create([
            'email' => 'staff@example.com',
            'password' => bcrypt('correctpassword'),
            'status' => 'active',
        ]);

        // Attempt login with wrong password
        $response = $this->postJson('/api/auth/login', [
            'email' => 'staff@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert 401 unauthorized or 422 validation error
        // Laravel's ValidationException returns 422, but semantically it's an auth failure
        $this->assertContains($response->status(), [401, 422]);

        // Assert no token is provided
        $response->assertJsonMissing(['token']);

        // Attempt login with non-existent email
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        // Assert unauthorized response
        $this->assertContains($response->status(), [401, 422]);
        $response->assertJsonMissing(['token']);
    }

    /**
     * Test that login fails for inactive staff members
     */
    public function test_login_fails_for_inactive_staff(): void
    {
        // Create an inactive staff member
        Staff::factory()->create([
            'email' => 'inactive@example.com',
            'password' => bcrypt('password123'),
            'status' => 'inactive',
        ]);

        // Attempt login
        $response = $this->postJson('/api/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        // Assert forbidden response
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Your account is inactive. Please contact the administrator.',
        ]);
    }

    /**
     * Test that logout revokes the token
     */
    public function test_logout_revokes_token(): void
    {
        $staff = Staff::factory()->create([
            'status' => 'active',
        ]);

        // Login to get a real token
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('token');

        // Verify token was created
        $this->assertNotEmpty($token);
        $this->assertEquals(1, $staff->tokens()->count());

        // Logout using the token
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout');

        // Assert successful logout
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Logged out successfully',
        ]);

        // Verify token has been revoked
        $this->assertEquals(0, $staff->fresh()->tokens()->count(),
            'Expected token to be revoked after logout');
    }

    /**
     * Test that the /me endpoint returns authenticated user data
     */
    public function test_me_endpoint_returns_authenticated_user(): void
    {
        $staff = Staff::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'manager',
            'status' => 'active',
        ]);

        // Act as the authenticated staff
        $response = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/auth/me');

        // Assert response contains user data
        $response->assertStatus(200);
        $response->assertJson([
            'user' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => $staff->role,
            ],
        ]);
    }
}
