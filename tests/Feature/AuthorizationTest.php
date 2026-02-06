<?php

namespace Tests\Feature;

use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin can access all routes.
     */
    public function test_admin_can_access_all_routes(): void
    {
        $admin = Staff::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);

        $this->actingAs($admin, 'web');

        // Admin can access dashboard
        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Admin can access users management
        $response = $this->get('/users');
        $response->assertStatus(200);

        // Admin can access reports
        $response = $this->get('/reports');
        $response->assertStatus(200);

        // Admin can access menu management
        $response = $this->get('/menu');
        $response->assertStatus(200);

        // Admin can access manager dashboard
        $response = $this->get('/manager/dashboard');
        $response->assertStatus(200);
    }

    /**
     * Test manager can access management pages.
     */
    public function test_manager_can_access_management_pages(): void
    {
        $manager = Staff::factory()->create([
            'role' => 'manager',
            'email' => 'manager@test.com',
        ]);

        $this->actingAs($manager, 'web');

        // Manager can access dashboard
        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Manager can access users management
        $response = $this->get('/users');
        $response->assertStatus(200);

        // Manager can access reports
        $response = $this->get('/reports');
        $response->assertStatus(200);

        // Manager can access menu management
        $response = $this->get('/menu');
        $response->assertStatus(200);

        // Manager can access manager dashboard
        $response = $this->get('/manager/dashboard');
        $response->assertStatus(200);
    }

    /**
     * Test waiter cannot access management pages.
     */
    public function test_waiter_cannot_access_management_pages(): void
    {
        $waiter = Staff::factory()->create([
            'role' => 'waiter',
            'email' => 'waiter@test.com',
        ]);

        $this->actingAs($waiter, 'web');

        // Waiter can access dashboard
        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Waiter cannot access users management
        $response = $this->get('/users');
        $response->assertStatus(403);

        // Waiter cannot access reports
        $response = $this->get('/reports');
        $response->assertStatus(403);

        // Waiter cannot access menu management
        $response = $this->get('/menu');
        $response->assertStatus(403);

        // Waiter cannot access manager dashboard
        $response = $this->get('/manager/dashboard');
        $response->assertStatus(403);
    }

    /**
     * Test chef can access kitchen routes.
     */
    public function test_chef_can_access_kitchen_routes(): void
    {
        $chef = Staff::factory()->create([
            'role' => 'chef',
            'email' => 'chef@test.com',
        ]);

        $this->actingAs($chef, 'web');

        // Chef can access kitchen display
        $response = $this->get('/kitchen/display');
        $response->assertStatus(200);

        // Chef cannot access management pages
        $response = $this->get('/users');
        $response->assertStatus(403);

        // Chef cannot access bar
        $response = $this->get('/bar/display');
        $response->assertStatus(403);
    }

    /**
     * Test bartender can access bar routes.
     */
    public function test_bartender_can_access_bar_routes(): void
    {
        $bartender = Staff::factory()->create([
            'role' => 'bartender',
            'email' => 'bartender@test.com',
        ]);

        $this->actingAs($bartender, 'web');

        // Bartender can access bar display
        $response = $this->get('/bar/display');
        $response->assertStatus(200);

        // Bartender cannot access management pages
        $response = $this->get('/users');
        $response->assertStatus(403);

        // Bartender cannot access kitchen
        $response = $this->get('/kitchen/display');
        $response->assertStatus(403);
    }

    /**
     * Test staff helper methods.
     */
    public function test_staff_helper_methods(): void
    {
        $admin = Staff::factory()->create(['role' => 'admin']);
        $manager = Staff::factory()->create(['role' => 'manager']);
        $waiter = Staff::factory()->create(['role' => 'waiter']);
        $chef = Staff::factory()->create(['role' => 'chef']);
        $bartender = Staff::factory()->create(['role' => 'bartender']);

        // Test isAdmin()
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($manager->isAdmin());

        // Test isManager()
        $this->assertTrue($manager->isManager());
        $this->assertFalse($admin->isManager());

        // Test hasRole()
        $this->assertTrue($waiter->hasRole('waiter'));
        $this->assertTrue($chef->hasRole('chef'));
        $this->assertTrue($bartender->hasRole('bartender'));
        $this->assertFalse($waiter->hasRole('admin'));
    }

    /**
     * Test StaffPolicy authorization.
     */
    public function test_staff_policy_authorization(): void
    {
        $admin = Staff::factory()->create(['role' => 'admin']);
        $manager = Staff::factory()->create(['role' => 'manager']);
        $waiter = Staff::factory()->create(['role' => 'waiter']);

        // Admin can view any staff
        $this->assertTrue($admin->can('viewAny', Staff::class));

        // Manager can view any staff
        $this->assertTrue($manager->can('viewAny', Staff::class));

        // Waiter cannot view any staff
        $this->assertFalse($waiter->can('viewAny', Staff::class));

        // Admin can create staff
        $this->assertTrue($admin->can('create', Staff::class));

        // Manager can create staff
        $this->assertTrue($manager->can('create', Staff::class));

        // Waiter cannot create staff
        $this->assertFalse($waiter->can('create', Staff::class));

        // Admin can update any staff
        $this->assertTrue($admin->can('update', $waiter));
        $this->assertTrue($admin->can('update', $manager));

        // Manager can update waiter but not admin
        $this->assertTrue($manager->can('update', $waiter));
        $this->assertFalse($manager->can('update', $admin));

        // Admin can delete any staff
        $this->assertTrue($admin->can('delete', $waiter));

        // Manager can delete waiter
        $this->assertTrue($manager->can('delete', $waiter));
    }

    /**
     * Test gate definitions.
     */
    public function test_gate_definitions(): void
    {
        $admin = Staff::factory()->create(['role' => 'admin']);
        $manager = Staff::factory()->create(['role' => 'manager']);
        $waiter = Staff::factory()->create(['role' => 'waiter']);

        // Test manage-staff gate
        $this->assertTrue($admin->can('manage-staff'));
        $this->assertTrue($manager->can('manage-staff'));
        $this->assertFalse($waiter->can('manage-staff'));

        // Test access-admin gate
        $this->assertTrue($admin->can('access-admin'));
        $this->assertFalse($manager->can('access-admin'));
        $this->assertFalse($waiter->can('access-admin'));

        // Test view-reports gate
        $this->assertTrue($admin->can('view-reports'));
        $this->assertTrue($manager->can('view-reports'));
        $this->assertFalse($waiter->can('view-reports'));
    }
}
