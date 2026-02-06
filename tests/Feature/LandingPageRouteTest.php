<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageRouteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that unauthenticated users visiting '/' are redirected to /login
     */
    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    /**
     * Test that authenticated users visiting '/' are redirected to /dashboard
     */
    public function test_authenticated_user_redirected_to_dashboard(): void
    {
        // Create a user with manager role
        $user = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect('/dashboard');
    }

    /**
     * Test that /dashboard redirects to appropriate role-based dashboard
     */
    public function test_dashboard_redirects_based_on_role(): void
    {
        // Test manager redirect
        $manager = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($manager)->get('/dashboard');
        $response->assertRedirect(route('manager.dashboard'));

        // Test chef redirect
        $chef = User::factory()->create(['role' => 'chef']);

        $response = $this->actingAs($chef)->get('/dashboard');
        $response->assertRedirect(route('kitchen.display'));

        // Test bartender redirect
        $bartender = User::factory()->create(['role' => 'bartender']);

        $response = $this->actingAs($bartender)->get('/dashboard');
        $response->assertRedirect(route('bar.display'));
    }
}
