<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'editor']);
        Role::create(['name' => 'viewer']);

        Permission::create(['name' => 'manage-presets']);
        Permission::create(['name' => 'generate-content']);
        Permission::create(['name' => 'view-content']);
    }

    public function test_admin_can_fetch_roles_and_permissions()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->getJson('/api/roles-permissions');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'roles',
            'permissions',
        ]);
    }

    public function test_editor_cannot_fetch_roles_and_permissions()
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $response = $this->actingAs($editor)
            ->getJson('/api/roles-permissions');

        $response->assertStatus(403);
    }

    public function test_admin_can_sync_role_permissions()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $viewer = Role::where('name', 'viewer')->first();

        // Assign manage-presets to viewer
        $response = $this->actingAs($admin)
            ->postJson('/api/roles-permissions/sync', [
                'role_id' => $viewer->id,
                'permissions' => ['manage-presets', 'view-content'],
            ]);

        $response->assertStatus(200);

        $this->assertTrue($viewer->hasPermissionTo('manage-presets'));
        $this->assertTrue($viewer->hasPermissionTo('view-content'));
        $this->assertFalse($viewer->hasPermissionTo('generate-content'));
    }
}
