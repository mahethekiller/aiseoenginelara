<?php

namespace Tests\Feature;

use App\Models\AiPreset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AiPresetWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'viewer']);
    }

    public function test_guest_is_redirected_to_login_from_ai_presets(): void
    {
        $response = $this->get('/ai-presets');
        $response->assertRedirect('/login');
    }

    public function test_user_can_view_ai_presets_and_auto_seeds_defaults_if_empty(): void
    {
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $this->assertDatabaseMissing('ai_presets', ['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/ai-presets');

        $response->assertStatus(200);
        $response->assertSee('My AI Presets');
        $response->assertSee('Google E-E-A-T Standard');

        // Auto-seeded 4 standard presets for this user
        $this->assertEquals(4, $user->presets()->count());
    }

    public function test_user_can_create_ai_preset_via_web(): void
    {
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $response = $this->actingAs($user)->post('/ai-presets', [
            'name' => 'Cybersecurity Whitepaper Deep Dive',
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
            'temperature' => 0.6,
            'max_workers' => 4,
            'custom_instructions' => 'Write rigorous, factual cybersecurity insights.',
            'is_active' => true,
        ]);

        $response->assertRedirect('/ai-presets');
        $this->assertDatabaseHas('ai_presets', [
            'user_id' => $user->id,
            'name' => 'Cybersecurity Whitepaper Deep Dive',
            'is_active' => true,
        ]);
    }

    public function test_user_can_update_their_own_preset(): void
    {
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $preset = AiPreset::create([
            'user_id' => $user->id,
            'name' => 'Draft Preset',
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
            'temperature' => 0.7,
            'max_workers' => 3,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->put("/ai-presets/{$preset->id}", [
            'name' => 'Refined Expert Preset',
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'temperature' => 0.5,
            'max_workers' => 2,
            'custom_instructions' => 'Updated instructions.',
            'is_active' => true,
        ]);

        $response->assertRedirect('/ai-presets');
        $this->assertDatabaseHas('ai_presets', [
            'id' => $preset->id,
            'name' => 'Refined Expert Preset',
            'model' => 'gpt-4o',
            'is_active' => true,
        ]);
    }

    public function test_user_can_activate_their_preset(): void
    {
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $preset1 = AiPreset::create([
            'user_id' => $user->id,
            'name' => 'Preset 1',
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
            'is_active' => true,
        ]);

        $preset2 = AiPreset::create([
            'user_id' => $user->id,
            'name' => 'Preset 2',
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->post("/ai-presets/{$preset2->id}/activate");

        $response->assertRedirect('/ai-presets');
        $this->assertTrue($preset2->fresh()->is_active);
        $this->assertFalse($preset1->fresh()->is_active);
    }

    public function test_user_can_clone_preset(): void
    {
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $preset = AiPreset::create([
            'user_id' => $user->id,
            'name' => 'Source Preset',
            'provider' => 'anthropic',
            'model' => 'claude-3-5-sonnet',
            'temperature' => 0.4,
            'max_workers' => 2,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post("/ai-presets/{$preset->id}/clone");

        $response->assertRedirect('/ai-presets');
        $this->assertDatabaseHas('ai_presets', [
            'user_id' => $user->id,
            'name' => 'Source Preset (Copy)',
            'model' => 'claude-3-5-sonnet',
            'is_active' => false,
        ]);
    }

    public function test_user_can_delete_preset(): void
    {
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $preset = AiPreset::create([
            'user_id' => $user->id,
            'name' => 'Preset to Delete',
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->delete("/ai-presets/{$preset->id}");

        $response->assertRedirect('/ai-presets');
        $this->assertDatabaseMissing('ai_presets', ['id' => $preset->id]);
    }

    public function test_user_cannot_update_or_delete_another_users_preset(): void
    {
        $userA = User::factory()->create();
        $userA->assignRole('viewer');

        $userB = User::factory()->create();
        $userB->assignRole('viewer');

        $presetA = AiPreset::create([
            'user_id' => $userA->id,
            'name' => 'User A Secret Preset',
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
            'is_active' => true,
        ]);

        // User B tries to update User A's preset
        $updateResponse = $this->actingAs($userB)->put("/ai-presets/{$presetA->id}", [
            'name' => 'Hacked Preset Name',
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
        ]);
        $updateResponse->assertStatus(404);

        // User B tries to delete User A's preset
        $deleteResponse = $this->actingAs($userB)->delete("/ai-presets/{$presetA->id}");
        $deleteResponse->assertStatus(404);

        // Ensure User A's preset remains intact
        $this->assertDatabaseHas('ai_presets', [
            'id' => $presetA->id,
            'name' => 'User A Secret Preset',
        ]);
    }
}
