<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'editor']);
        Role::create(['name' => 'viewer']);
    }

    public function test_admin_can_list_users()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user1 = User::factory()->create(['name' => 'Test User 1']);
        $user1->assignRole('viewer');

        $response = $this->actingAs($admin)
            ->getJson('/api/users');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    public function test_editor_cannot_list_users()
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $response = $this->actingAs($editor)
            ->getJson('/api/users');

        $response->assertStatus(403); // Forbidden
    }

    public function test_admin_can_create_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->postJson('/api/users', [
                'name' => 'New Guy',
                'email' => 'newguy@example.com',
                'password' => 'secret123',
                'role' => 'editor',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'newguy@example.com']);
    }

    public function test_admin_cannot_delete_self()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->deleteJson("/api/users/{$admin->id}");

        $response->assertStatus(400); // Bad Request
    }
}
