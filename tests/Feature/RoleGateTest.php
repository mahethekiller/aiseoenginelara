<?php

namespace Tests\Feature;

use App\Models\AiPreset;
use App\Models\AiPromptTemplate;
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

    public function test_admin_can_access_web_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->get('/settings');

        $response->assertStatus(200);
    }

    public function test_settings_displays_only_user_scoped_presets(): void
    {
        $admin1 = User::factory()->create();
        $admin1->assignRole('admin');
        $preset1 = AiPreset::create([
            'user_id' => $admin1->id,
            'name' => 'Unique Admin1 Exclusive Preset',
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
            'max_workers' => 3,
            'temperature' => 0.7,
            'is_active' => true,
        ]);

        $admin2 = User::factory()->create();
        $admin2->assignRole('admin');
        $preset2 = AiPreset::create([
            'user_id' => $admin2->id,
            'name' => 'Unique Admin2 Secret Preset',
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'max_workers' => 4,
            'temperature' => 0.8,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin1)->get('/settings');
        $response->assertStatus(200);
        $response->assertSee('Unique Admin1 Exclusive Preset');
        $response->assertDontSee('Unique Admin2 Secret Preset');
    }

    public function test_viewer_is_forbidden_from_web_settings(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        $response = $this->actingAs($viewer)
            ->get('/settings');

        $response->assertStatus(403);
    }

    public function test_admin_can_save_preset_via_web(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->post('/settings/presets', [
                'name' => 'Admin Custom Preset',
                'provider' => 'gemini',
                'model' => 'gemini-2.0-flash',
                'temperature' => 0.7,
                'max_workers' => 3,
                'custom_instructions' => 'Strict quality checks',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('ai_presets', [
            'name' => 'Admin Custom Preset',
            'provider' => 'gemini',
        ]);
    }

    public function test_viewer_is_forbidden_from_saving_preset_via_web(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        $response = $this->actingAs($viewer)
            ->post('/settings/presets', [
                'name' => 'Hacker Preset',
                'provider' => 'gemini',
                'model' => 'gemini-2.0-flash',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_and_activate_preset_via_api(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $createResponse = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/presets', [
                'name' => 'API Admin Preset',
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'max_workers' => 4,
                'temperature' => 0.8,
                'custom_instructions' => 'High quality copy',
            ]);

        $createResponse->assertStatus(201);
        $presetId = $createResponse->json('id');

        $activateResponse = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/presets/{$presetId}/activate");

        $activateResponse->assertStatus(200);
        $this->assertDatabaseHas('ai_presets', [
            'id' => $presetId,
            'is_active' => true,
        ]);
    }

    public function test_viewer_is_forbidden_from_creating_or_activating_preset_via_api(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $preset = AiPreset::create([
            'user_id' => $admin->id,
            'name' => 'Admin Seed Preset',
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
            'max_workers' => 3,
            'temperature' => 0.7,
            'is_active' => false,
        ]);

        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        // Blocked from creating
        $createResponse = $this->actingAs($viewer, 'sanctum')
            ->postJson('/api/presets', [
                'name' => 'Unauthorized Preset',
                'provider' => 'gemini',
                'model' => 'gemini-2.0-flash',
                'max_workers' => 3,
                'temperature' => 0.7,
            ]);
        $createResponse->assertStatus(403);

        // Blocked from activating
        $activateResponse = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/presets/{$preset->id}/activate");
        $activateResponse->assertStatus(403);

        // Blocked from deleting
        $deleteResponse = $this->actingAs($viewer, 'sanctum')
            ->deleteJson("/api/presets/{$preset->id}");
        $deleteResponse->assertStatus(403);

        // But allowed to view presets list
        $viewResponse = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/presets');
        $viewResponse->assertStatus(200);
    }

    public function test_user_can_view_prompt_template_web_and_json(): void
    {
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $template = AiPromptTemplate::create([
            'user_id' => $user->id,
            'archetype_key' => 'test_prompt_blueprint',
            'archetype_name' => 'SEO Comparison Guide',
            'description' => 'Detailed product comparisons',
            'system_prompt_template' => 'Write a comparison article for {{brand_name}} covering {{audience}}.',
            'available_placeholders' => ['brand_name', 'audience'],
            'is_active' => true,
            'is_system' => false,
        ]);

        // Index listing page test
        $indexResponse = $this->actingAs($user)
            ->get('/prompt-templates');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('SEO Comparison Guide');
        $indexResponse->assertSee('view_template_modal');

        // Web detail view test
        $webResponse = $this->actingAs($user)
            ->get("/prompt-templates/{$template->id}");
        $webResponse->assertStatus(200);
        $webResponse->assertSee('SEO Comparison Guide');
        $webResponse->assertSee('Write a comparison article');

        // JSON API test
        $jsonResponse = $this->actingAs($user, 'sanctum')
            ->getJson("/api/prompt-templates/{$template->id}");
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonPath('template.archetype_name', 'SEO Comparison Guide');
    }

    public function test_user_can_switch_ai_preset_from_header(): void
    {
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $preset1 = AiPreset::create([
            'user_id' => $user->id,
            'name' => 'Fast Gemini Draft',
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
            'max_workers' => 3,
            'temperature' => 0.7,
            'is_active' => true,
        ]);

        $preset2 = AiPreset::create([
            'user_id' => $user->id,
            'name' => 'Deep Claude Research',
            'provider' => 'anthropic',
            'model' => 'claude-3-5-sonnet',
            'max_workers' => 2,
            'temperature' => 0.5,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/preset/switch', [
                'preset_id' => $preset2->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('preset.id', $preset2->id)
            ->assertJsonPath('preset.model', 'claude-3-5-sonnet');

        $this->assertTrue($preset2->fresh()->is_active);
        $this->assertFalse($preset1->fresh()->is_active);
    }

    public function test_different_users_maintain_isolated_active_presets(): void
    {
        $userA = User::factory()->create();
        $userA->assignRole('viewer');
        $presetA1 = AiPreset::create([
            'user_id' => $userA->id,
            'name' => 'User A Preset 1',
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
            'max_workers' => 3,
            'temperature' => 0.7,
            'is_active' => true,
        ]);
        $presetA2 = AiPreset::create([
            'user_id' => $userA->id,
            'name' => 'User A Preset 2',
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'max_workers' => 2,
            'temperature' => 0.5,
            'is_active' => false,
        ]);

        $userB = User::factory()->create();
        $userB->assignRole('viewer');
        $presetB1 = AiPreset::create([
            'user_id' => $userB->id,
            'name' => 'User B Preset 1',
            'provider' => 'anthropic',
            'model' => 'claude-3-5-sonnet',
            'max_workers' => 3,
            'temperature' => 0.6,
            'is_active' => true,
        ]);
        $presetB2 = AiPreset::create([
            'user_id' => $userB->id,
            'name' => 'User B Preset 2',
            'provider' => 'gemini',
            'model' => 'gemini-1.5-pro',
            'max_workers' => 2,
            'temperature' => 0.4,
            'is_active' => false,
        ]);

        // User A switches to Preset A2
        $responseA = $this->actingAs($userA)->postJson('/preset/switch', [
            'preset_id' => $presetA2->id,
        ]);
        $responseA->assertStatus(200);

        // Verify User A switched
        $this->assertTrue($presetA2->fresh()->is_active);
        $this->assertFalse($presetA1->fresh()->is_active);

        // Crucial verification: User B's active preset was completely unaffected!
        $this->assertTrue($presetB1->fresh()->is_active);
        $this->assertFalse($presetB2->fresh()->is_active);

        // Now User B switches to Preset B2
        $responseB = $this->actingAs($userB)->postJson('/preset/switch', [
            'preset_id' => $presetB2->id,
        ]);
        $responseB->assertStatus(200);

        // Verify User B switched and User A remains on Preset A2
        $this->assertTrue($presetB2->fresh()->is_active);
        $this->assertFalse($presetB1->fresh()->is_active);
        $this->assertTrue($presetA2->fresh()->is_active);
    }
}
