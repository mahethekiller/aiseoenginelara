<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'creator']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->creator = User::factory()->create();
        $this->creator->assignRole('creator');
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_admin_sees_executive_command_center_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Executive Command Center');
        $response->assertSee('Agency Analytics');
        $response->assertSee('LLM Core Providers');
    }

    public function test_creator_sees_creator_studio_dashboard(): void
    {
        $response = $this->actingAs($this->creator)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Creator Studio');
        $response->assertSee('Welcome back');
        $response->assertSee('My Recent Articles');
    }

    public function test_admin_can_toggle_to_creator_view(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard?view=user');

        $response->assertStatus(200);
        $response->assertSee('Creator Studio');
    }
}
