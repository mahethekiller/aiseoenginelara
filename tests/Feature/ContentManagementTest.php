<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $user1;

    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $viewContent = Permission::create(['name' => 'view-content']);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo($viewContent);

        $editorRole = Role::create(['name' => 'editor']);
        $editorRole->givePermissionTo($viewContent);

        $viewerRole = Role::create(['name' => 'viewer']);
        $viewerRole->givePermissionTo($viewContent);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user1 = User::factory()->create();
        $this->user1->assignRole('editor');

        $this->user2 = User::factory()->create();
        $this->user2->assignRole('editor');

        // Seed Article & Rewriter Job for User 1
        $this->user1->articles()->create([
            'title' => 'User 1 SEO Blog',
            'meta_title' => 'User 1 SEO Blog',
            'meta_description' => 'User 1 SEO Description',
            'slug' => 'user-1-seo-blog',
            'html_content' => '<h1>User 1</h1>',
            'markdown_content' => '# User 1',
        ]);
        $this->user1->rewriterJobs()->create([
            'rewriter_mode' => 'semantic-clean',
            'status' => 'completed',
            'source_url' => 'https://example.com/user1',
        ]);

        // Seed Article & Rewriter Job for User 2
        $this->user2->articles()->create([
            'title' => 'User 2 SEO Blog',
            'meta_title' => 'User 2 SEO Blog',
            'meta_description' => 'User 2 SEO Description',
            'slug' => 'user-2-seo-blog',
            'html_content' => '<h1>User 2</h1>',
            'markdown_content' => '# User 2',
        ]);
        $this->user2->rewriterJobs()->create([
            'rewriter_mode' => 'layout-preserving',
            'status' => 'completed',
            'source_url' => 'https://example.com/user2',
        ]);
    }

    public function test_standard_user_only_sees_their_own_articles_and_rewriters()
    {
        $response = $this->actingAs($this->user1)
            ->getJson('/api/articles');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['title' => 'User 1 SEO Blog']);

        $responseJobs = $this->actingAs($this->user1)
            ->getJson('/api/rewriter/jobs');

        $responseJobs->assertStatus(200);
        $responseJobs->assertJsonCount(1);
        $responseJobs->assertJsonFragment(['source_url' => 'https://example.com/user1']);
    }

    public function test_admin_can_see_all_articles_and_rewriters_and_filter_by_user()
    {
        // Admin sees both
        $response = $this->actingAs($this->admin)
            ->getJson('/api/articles');
        $response->assertStatus(200);
        $response->assertJsonCount(2);

        // Admin filters for User 2
        $responseFiltered = $this->actingAs($this->admin)
            ->getJson("/api/articles?user_id={$this->user2->id}");
        $responseFiltered->assertStatus(200);
        $responseFiltered->assertJsonCount(1);
        $responseFiltered->assertJsonFragment(['title' => 'User 2 SEO Blog']);
    }

    public function test_user_cannot_delete_other_user_article()
    {
        $article2 = $this->user2->articles()->first();

        // User 1 tries to delete User 2's article
        $response = $this->actingAs($this->user1)
            ->deleteJson("/api/articles/{$article2->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_any_article()
    {
        $article2 = $this->user2->articles()->first();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/articles/{$article2->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('articles', ['id' => $article2->id]);
    }

    public function test_user_can_preview_prompt_with_compiled_directives()
    {
        $response = $this->actingAs($this->user1)
            ->postJson('/blog-creator/preview-prompt', [
                'topic' => 'Modern Minimalist Interior Design',
                'primary_keyword' => 'minimalist interior design',
                'format' => 'Ultimate Guide',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'model',
            'provider',
            'system_prompt',
            'user_prompt',
            'passes' => [
                'master',
                'outline',
                'sections',
                'metadata',
            ],
        ]);
        $this->assertNotEmpty($response->json('system_prompt'));
        $this->assertNotEmpty($response->json('user_prompt'));
        $this->assertStringContainsString('minimalist interior design', $response->json('user_prompt'));
    }
}
