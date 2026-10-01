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

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($viewContent);

        $superAdminRole = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdminRole->givePermissionTo($viewContent);

        $editorRole = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $editorRole->givePermissionTo($viewContent);

        $viewerRole = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
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

    public function test_non_admin_cannot_delete_articles_or_rewriters()
    {
        $article1 = $this->user1->articles()->first();
        $job1 = $this->user1->rewriterJobs()->first();

        // Non-admin tries to delete their OWN article via API & Web -> Forbidden
        $responseApi = $this->actingAs($this->user1)
            ->deleteJson("/api/articles/{$article1->id}");
        $responseApi->assertStatus(403);

        $responseWeb = $this->actingAs($this->user1)
            ->delete("/articles/{$article1->id}");
        $responseWeb->assertStatus(403);

        // Non-admin tries to delete their OWN rewriter job via API & Web -> Forbidden
        $responseJobApi = $this->actingAs($this->user1)
            ->deleteJson("/api/rewriter/jobs/{$job1->id}");
        $responseJobApi->assertStatus(403);

        $responseJobWeb = $this->actingAs($this->user1)
            ->delete("/rewriter/jobs/{$job1->id}");
        $responseJobWeb->assertStatus(403);

        // Ensure records still exist
        $this->assertDatabaseHas('articles', ['id' => $article1->id]);
        $this->assertDatabaseHas('rewriter_jobs', ['id' => $job1->id]);
    }

    public function test_admin_and_super_admin_can_delete_articles_and_rewriters()
    {
        $article2 = $this->user2->articles()->first();
        $job2 = $this->user2->rewriterJobs()->first();

        // Admin can delete
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/articles/{$article2->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('articles', ['id' => $article2->id]);

        $responseJob = $this->actingAs($this->admin)
            ->deleteJson("/api/rewriter/jobs/{$job2->id}");
        $responseJob->assertStatus(200);
        $this->assertDatabaseMissing('rewriter_jobs', ['id' => $job2->id]);

        // Super Admin can delete
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $article1 = $this->user1->articles()->first();
        $job1 = $this->user1->rewriterJobs()->first();

        $responseSaArticle = $this->actingAs($superAdmin)
            ->delete("/articles/{$article1->id}");
        $responseSaArticle->assertRedirect('/articles');
        $this->assertDatabaseMissing('articles', ['id' => $article1->id]);

        $responseSaJob = $this->actingAs($superAdmin)
            ->delete("/rewriter/jobs/{$job1->id}");
        $responseSaJob->assertRedirect('/rewriter');
        $this->assertDatabaseMissing('rewriter_jobs', ['id' => $job1->id]);
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

    public function test_user_can_fetch_job_logs_and_article_payload()
    {
        $job = $this->user1->seoJobs()->create([
            'execution_mode' => 'single',
            'status' => 'completed',
            'parameters' => ['topic' => 'Test Article', 'primary_keyword' => 'test'],
            'total_items' => 1,
            'completed_items' => 1,
            'logs' => json_encode(['[00:00:00] Job started', '[00:00:05] Job completed']),
        ]);

        $article = $job->articles()->create([
            'user_id' => $this->user1->id,
            'title' => 'Test Generated Article',
            'meta_title' => 'Test Generated Article Meta',
            'meta_description' => 'Test Generated Article Meta Description',
            'slug' => 'test-generated-article',
            'html_content' => '<p>Generated content body</p>',
            'markdown_content' => '# Generated content body',
            'word_count' => 500,
            'seo_score' => 92,
            'flesch_reading_ease' => 65.5,
        ]);

        // 1. Fetch logs
        $logsResponse = $this->actingAs($this->user1)->getJson("/blog-creator/jobs/{$job->id}/logs");
        $logsResponse->assertStatus(200)
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('article.title', 'Test Generated Article')
            ->assertJsonPath('article_id', $article->id)
            ->assertJsonPath('metrics.words_generated', 500);

        // 2. Fetch job article directly
        $articleResponse = $this->actingAs($this->user1)->getJson("/blog-creator/jobs/{$job->id}/article");
        $articleResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('article.title', 'Test Generated Article')
            ->assertJsonPath('article.html_content', '<p>Generated content body</p>');
    }

    public function test_content_database_web_view_scopes_to_own_articles_for_regular_users()
    {
        $response = $this->actingAs($this->user1)->get('/articles');
        $response->assertStatus(200);
        $response->assertSee('User 1 SEO Blog');
        $response->assertDontSee('User 2 SEO Blog');
    }

    public function test_content_database_web_view_shows_all_articles_for_admin_and_supports_filtering()
    {
        // Admin sees both
        $response = $this->actingAs($this->admin)->get('/articles');
        $response->assertStatus(200);
        $response->assertSee('User 1 SEO Blog');
        $response->assertSee('User 2 SEO Blog');

        // Admin filters for User 2
        $responseFiltered = $this->actingAs($this->admin)->get("/articles?user_id={$this->user2->id}");
        $responseFiltered->assertStatus(200);
        $responseFiltered->assertSee('User 2 SEO Blog');
        $responseFiltered->assertDontSee('User 1 SEO Blog');
    }

    public function test_non_admin_cannot_access_or_download_another_users_article()
    {
        $article2 = $this->user2->articles()->first();

        // User 1 cannot view User 2's article
        $viewResponse = $this->actingAs($this->user1)->get("/articles/{$article2->id}");
        $viewResponse->assertStatus(403);

        // User 1 cannot download User 2's article
        $downloadResponse = $this->actingAs($this->user1)->get("/articles/{$article2->id}/download/html");
        $downloadResponse->assertStatus(403);

        // Admin can view and download User 2's article
        $adminView = $this->actingAs($this->admin)->get("/articles/{$article2->id}");
        $adminView->assertStatus(200);

        $adminDownload = $this->actingAs($this->admin)->get("/articles/{$article2->id}/download/html");
        $adminDownload->assertStatus(200);
    }

    public function test_user_can_inspect_article_prompt_payload()
    {
        $article1 = $this->user1->articles()->first();

        $response = $this->actingAs($this->user1)->getJson("/articles/{$article1->id}/prompt");
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('article_id', $article1->id)
            ->assertJsonStructure([
                'success',
                'article_id',
                'article_title',
                'template_name',
                'is_custom',
                'parameters' => [
                    'topic',
                    'primary_keyword',
                    'format',
                    'tone',
                    'pov',
                ],
                'master_prompt' => ['system', 'user'],
                'outline_prompt' => ['system', 'user'],
                'section_prompts',
                'metadata_prompt',
            ]);

        $this->assertNotEmpty($response->json('master_prompt.system'));
        $this->assertNotEmpty($response->json('master_prompt.user'));
    }

    public function test_non_admin_cannot_inspect_another_users_article_prompt()
    {
        $article2 = $this->user2->articles()->first();

        // User 1 cannot inspect User 2's prompt
        $response = $this->actingAs($this->user1)->getJson("/articles/{$article2->id}/prompt");
        $response->assertStatus(403);

        // Admin can inspect User 2's prompt
        $adminResponse = $this->actingAs($this->admin)->getJson("/articles/{$article2->id}/prompt");
        $adminResponse->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_content_database_filters_by_prompt_template()
    {
        $response = $this->actingAs($this->admin)->get('/articles?prompt_template=master');
        $response->assertStatus(200);
        $response->assertSee('Prompt Blueprint');
    }

    public function test_content_database_displays_client_and_filters_by_client()
    {
        $response = $this->actingAs($this->admin)->get('/articles');
        $response->assertStatus(200);
        $response->assertSee('Client');
        $response->assertDontSee('<th>SEO Score</th>', false);
        $response->assertDontSee('<th>Reading Ease</th>', false);

        $filterResponse = $this->actingAs($this->admin)->get('/articles?client_id=none');
        $filterResponse->assertStatus(200);
    }
}



