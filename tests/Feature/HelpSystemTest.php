<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that help index page loads successfully for authenticated user.
     */
    public function test_help_index_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/help');

        $response->assertStatus(200);
        $response->assertSee('Help & Documentation');
    }

    /**
     * Test that help index redirects for guest users.
     */
    public function test_help_index_redirects_for_guest(): void
    {
        $response = $this->get('/help');

        $response->assertRedirect('/login');
    }

    /**
     * Test that admin guide is accessible.
     */
    public function test_admin_guide_is_accessible(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/help/ADMIN_GUIDE.md');

        $response->assertStatus(200);
        $response->assertSee('Administrator Guide');
    }

    /**
     * Test that manager guide is accessible.
     */
    public function test_manager_guide_is_accessible(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($user)->get('/help/MANAGER_GUIDE.md');

        $response->assertStatus(200);
        $response->assertSee('Manager Guide');
    }

    /**
     * Test that waiter guide is accessible.
     */
    public function test_waiter_guide_is_accessible(): void
    {
        $user = User::factory()->create(['role' => 'waiter']);

        $response = $this->actingAs($user)->get('/help/WAITER_GUIDE.md');

        $response->assertStatus(200);
        $response->assertSee('Waiter Guide');
    }

    /**
     * Test that chef guide is accessible.
     */
    public function test_chef_guide_is_accessible(): void
    {
        $user = User::factory()->create(['role' => 'chef']);

        $response = $this->actingAs($user)->get('/help/CHEF_GUIDE.md');

        $response->assertStatus(200);
        $response->assertSee('Chef Guide');
    }

    /**
     * Test that bartender guide is accessible.
     */
    public function test_bartender_guide_is_accessible(): void
    {
        $user = User::factory()->create(['role' => 'bartender']);

        $response = $this->actingAs($user)->get('/help/BARTENDER_GUIDE.md');

        $response->assertStatus(200);
        $response->assertSee('Bartender Guide');
    }

    /**
     * Test that API documentation is accessible.
     */
    public function test_api_documentation_is_accessible(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/help/API.md');

        $response->assertStatus(200);
        $response->assertSee('API Documentation');
    }

    /**
     * Test that PDF export works for admin guide.
     */
    public function test_pdf_export_works_for_admin_guide(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/help/ADMIN_GUIDE.md/pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Test that invalid documentation file returns 404.
     */
    public function test_invalid_documentation_file_returns_404(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/help/INVALID_FILE.md');

        $response->assertStatus(404);
    }

    /**
     * Test that non-markdown files are rejected.
     */
    public function test_non_markdown_files_are_rejected(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/help/malicious.php');

        $response->assertStatus(404);
    }

    /**
     * Test that help index shows role-specific documentation.
     */
    public function test_help_index_shows_role_specific_documentation(): void
    {
        $user = User::factory()->create(['role' => 'waiter']);

        $response = $this->actingAs($user)->get('/help');

        $response->assertStatus(200);
        $response->assertSee('Waiter Guide');
        $response->assertSee('Creating orders, processing payments, using POS interface');
    }

    /**
     * Test that admin can see all guides.
     */
    public function test_admin_can_see_all_guides(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/help');

        $response->assertStatus(200);
        $response->assertSee('All User Guides');
        $response->assertSee('Admin Guide');
        $response->assertSee('Manager Guide');
        $response->assertSee('Waiter Guide');
        $response->assertSee('Chef Guide');
        $response->assertSee('Bartender Guide');
    }

    /**
     * Test that manager can see all guides.
     */
    public function test_manager_can_see_all_guides(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($user)->get('/help');

        $response->assertStatus(200);
        $response->assertSee('All User Guides');
    }
}
