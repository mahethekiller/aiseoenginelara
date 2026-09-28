<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'viewer']);
    }

    public function test_admin_can_access_settings_config(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/settings/config');

        $response->assertStatus(200);
    }

    public function test_viewer_is_blocked_from_settings_config(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/settings/config');

        $response->assertStatus(403);
    }
}
