<?php

namespace Tests\Feature;

use App\Models\KeywordRankCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RankTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin User']);
        $this->admin->assignRole('admin');

        $this->regularUser = User::factory()->create(['name' => 'Regular User']);
        $this->regularUser->assignRole('writer');
    }

    public function test_guest_is_redirected_to_login_from_rank_tracker(): void
    {
        $response = $this->get('/rank-tracker');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_rank_tracker_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/rank-tracker');
        $response->assertStatus(200);
        $response->assertSee('Live SERP Rank Scanner');
        $response->assertSee('Target Domain');
        $response->assertSee('Device Platform');
        $response->assertSee('City / Canonical Location');
    }

    public function test_admin_sees_all_records_while_regular_user_sees_only_own_records(): void
    {
        // Admin record
        $adminRecord = KeywordRankCheck::create([
            'user_id' => $this->admin->id,
            'keyword' => 'admin keyword check',
            'target_domain' => 'admin-site.com',
            'country' => 'in',
            'device' => 'desktop',
            'position' => 2,
            'is_ranked' => true,
        ]);

        // Regular user record
        $userRecord = KeywordRankCheck::create([
            'user_id' => $this->regularUser->id,
            'keyword' => 'user keyword check',
            'target_domain' => 'user-site.com',
            'country' => 'in',
            'device' => 'desktop',
            'position' => 5,
            'is_ranked' => true,
        ]);

        // 1. Regular user should only see their own record
        $userResponse = $this->actingAs($this->regularUser)->get('/rank-tracker');
        $userResponse->assertStatus(200);
        $userResponse->assertSee('user keyword check');
        $userResponse->assertDontSee('admin keyword check');

        // 2. Admin should see both records
        $adminResponse = $this->actingAs($this->admin)->get('/rank-tracker');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('admin keyword check');
        $adminResponse->assertSee('user keyword check');
    }

    public function test_single_record_csv_export(): void
    {
        $record = KeywordRankCheck::create([
            'user_id' => $this->admin->id,
            'keyword' => 'best cardiac surgeon in ahmedabad',
            'target_domain' => 'geimshospital.com',
            'country' => 'in',
            'device' => 'desktop',
            'position' => 3,
            'is_ranked' => true,
            'top_competitors' => [
                [
                    'position' => 1,
                    'domain' => 'apollohospitals.com',
                    'title' => 'Apollo Hospitals',
                    'link' => 'https://apollohospitals.com',
                    'is_target' => false,
                ]
            ],
        ]);

        $response = $this->actingAs($this->admin)->get('/rank-tracker/' . $record->id . '/export-csv');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_regular_user_cannot_export_or_delete_other_users_records(): void
    {
        $adminRecord = KeywordRankCheck::create([
            'user_id' => $this->admin->id,
            'keyword' => 'secret admin query',
            'target_domain' => 'admin-site.com',
            'country' => 'in',
            'device' => 'desktop',
        ]);

        // Attempt single CSV export by regular user -> 403 Forbidden
        $exportResponse = $this->actingAs($this->regularUser)->get('/rank-tracker/' . $adminRecord->id . '/export-csv');
        $exportResponse->assertStatus(403);

        // Attempt delete by regular user -> 403 Forbidden
        $deleteResponse = $this->actingAs($this->regularUser)->delete('/rank-tracker/' . $adminRecord->id);
        $deleteResponse->assertStatus(403);
    }

    public function test_rank_tracker_locations_endpoint(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/rank-tracker/locations?q=Ahmedabad&country=in');
        $response->assertStatus(200);
    }

    public function test_rank_tracker_credits_endpoint(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/rank-tracker/credits');
        $response->assertStatus(200);
    }

    public function test_csv_export_format_matches_user_specification(): void
    {
        $record = KeywordRankCheck::create([
            'user_id' => $this->admin->id,
            'keyword' => 'best cardiac surgeon in Ahmedabad',
            'target_domain' => 'sterlinghospitals.com',
            'country' => 'in',
            'location' => 'Ahmedabad',
            'device' => 'desktop',
            'position' => null,
            'is_ranked' => false,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/rank-tracker/' . $record->id . '/export-csv');
        $response->assertStatus(200);

        $content = $response->streamedContent();
        $this->assertStringContainsString('ID,Keyword,Domain,"Target Location",Device,"Search Engine","Organic Rank","Actual SERP Position","Ranking Landing Page URL",Status,"Crawled Date"', $content);
        $this->assertStringContainsString('best cardiac surgeon in Ahmedabad', $content);
        $this->assertStringContainsString('https://sterlinghospitals.com/', $content);
        $this->assertStringContainsString('Ahmedabad', $content);
        $this->assertStringContainsString('Desktop', $content);
        $this->assertStringContainsString('Google', $content);
        $this->assertStringContainsString('Not in Top 50', $content);
        $this->assertStringContainsString('Completed', $content);
    }

    public function test_generic_runs_with_null_client_are_visible_in_table_by_default(): void
    {
        // Create generic check with client_id = null
        KeywordRankCheck::create([
            'user_id' => $this->regularUser->id,
            'client_id' => null,
            'keyword' => 'generic bulk keyword test query',
            'target_domain' => 'sterlinghospitals.com',
            'country' => 'in',
            'device' => 'desktop',
            'position' => 15,
            'is_ranked' => true,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->regularUser)->get('/rank-tracker');
        $response->assertStatus(200);
        $response->assertSee('generic bulk keyword test query');
        $response->assertSee('Generic Run');
    }

    public function test_rank_database_page_loads_and_scopes_by_user(): void
    {
        KeywordRankCheck::create([
            'user_id' => $this->regularUser->id,
            'client_id' => null,
            'keyword' => 'writer private search query',
            'target_domain' => 'mywriterblog.com',
            'country' => 'in',
            'device' => 'desktop',
            'position' => 2,
            'is_ranked' => true,
            'checked_at' => now(),
        ]);

        KeywordRankCheck::create([
            'user_id' => $this->admin->id,
            'client_id' => null,
            'keyword' => 'admin secret search query',
            'target_domain' => 'admincorp.com',
            'country' => 'in',
            'device' => 'desktop',
            'position' => 8,
            'is_ranked' => true,
            'checked_at' => now(),
        ]);

        // Regular user sees only own
        $response = $this->actingAs($this->regularUser)->get('/rank-database');
        $response->assertStatus(200);
        $response->assertSee('writer private search query');
        $response->assertDontSee('admin secret search query');

        // Admin sees both
        $adminResponse = $this->actingAs($this->admin)->get('/rank-database');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('writer private search query');
        $adminResponse->assertSee('admin secret search query');
        $adminResponse->assertSee('Rank Database & Search Audits', false);
    }

    public function test_batch_csv_export_streams_all_keywords_in_batch(): void
    {
        $batchId = 'B-TEST-BATCH-001';

        KeywordRankCheck::create([
            'user_id' => $this->admin->id,
            'batch_id' => $batchId,
            'keyword' => 'batch keyword one',
            'target_domain' => 'mybatchsite.com',
            'country' => 'in',
            'device' => 'desktop',
            'position' => 1,
            'is_ranked' => true,
            'ranking_url' => 'https://mybatchsite.com/page-one',
            'checked_at' => now(),
        ]);

        KeywordRankCheck::create([
            'user_id' => $this->admin->id,
            'batch_id' => $batchId,
            'keyword' => 'batch keyword two',
            'target_domain' => 'mybatchsite.com',
            'country' => 'in',
            'device' => 'desktop',
            'position' => 12,
            'is_ranked' => true,
            'ranking_url' => 'https://mybatchsite.com/page-two',
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/rank-tracker/batch/' . $batchId . '/export-csv');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('ID,Keyword,Domain,"Target Location",Device,"Search Engine","Organic Rank","Actual SERP Position","Ranking Landing Page URL",Status,"Crawled Date"', $content);
        $this->assertStringContainsString('batch keyword one', $content);
        $this->assertStringContainsString('batch keyword two', $content);
        $this->assertStringContainsString('https://mybatchsite.com/page-one', $content);
        $this->assertStringContainsString('https://mybatchsite.com/page-two', $content);
    }

    public function test_batch_details_endpoint_returns_json(): void
    {
        $batchId = 'B-TEST-BATCH-002';

        KeywordRankCheck::create([
            'user_id' => $this->admin->id,
            'batch_id' => $batchId,
            'keyword' => 'alpha keyword',
            'target_domain' => 'alphasite.com',
            'country' => 'in',
            'device' => 'desktop',
            'position' => 2,
            'is_ranked' => true,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->getJson('/rank-tracker/batch/' . $batchId);
        $response->assertStatus(200);
        $response->assertJson([
            'batch_id' => $batchId,
            'target_domain' => 'alphasite.com',
            'total_keywords' => 1,
            'top3' => 1,
        ]);
    }

    public function test_batch_deletion_removes_all_records_in_batch(): void
    {
        $batchId = 'B-TEST-BATCH-003';

        KeywordRankCheck::create([
            'user_id' => $this->admin->id,
            'batch_id' => $batchId,
            'keyword' => 'delete keyword 1',
            'target_domain' => 'deletesite.com',
            'country' => 'in',
            'device' => 'desktop',
            'checked_at' => now(),
        ]);

        KeywordRankCheck::create([
            'user_id' => $this->admin->id,
            'batch_id' => $batchId,
            'keyword' => 'delete keyword 2',
            'target_domain' => 'deletesite.com',
            'country' => 'in',
            'device' => 'desktop',
            'checked_at' => now(),
        ]);

        $this->assertEquals(2, KeywordRankCheck::where('batch_id', $batchId)->count());

        $response = $this->actingAs($this->admin)->delete('/rank-tracker/batch/' . $batchId);
        $response->assertStatus(200);

        $this->assertEquals(0, KeywordRankCheck::where('batch_id', $batchId)->count());
    }

    public function test_regular_user_cannot_export_or_delete_admin_batch(): void
    {
        $batchId = 'B-ADMIN-SECRET-BATCH';

        KeywordRankCheck::create([
            'user_id' => $this->admin->id,
            'batch_id' => $batchId,
            'keyword' => 'admin classified keyword',
            'target_domain' => 'classified.com',
            'country' => 'in',
            'device' => 'desktop',
            'checked_at' => now(),
        ]);

        // Attempt batch CSV export by regular user -> 403 Forbidden
        $exportResponse = $this->actingAs($this->regularUser)->get('/rank-tracker/batch/' . $batchId . '/export-csv');
        $exportResponse->assertStatus(403);

        // Attempt batch delete by regular user -> 403 Forbidden
        $deleteResponse = $this->actingAs($this->regularUser)->delete('/rank-tracker/batch/' . $batchId);
        $deleteResponse->assertStatus(403);
    }

    public function test_rank_database_page_batch_view_mode_renders_batches(): void
    {
        $batchId = 'B-PAGE-BATCH-TEST';

        KeywordRankCheck::create([
            'user_id' => $this->admin->id,
            'batch_id' => $batchId,
            'keyword' => 'database batch display keyword',
            'target_domain' => 'databasebatch.com',
            'country' => 'in',
            'device' => 'desktop',
            'position' => 1,
            'is_ranked' => true,
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/rank-database?view_mode=batches');
        $response->assertStatus(200);
        $response->assertSee($batchId);
        $response->assertSee('databasebatch.com');
        $response->assertSee('database batch display keyword');
    }

    public function test_keyword_rank_checker_service_check_batch_generates_batch_id(): void
    {
        $mockSerp = $this->createMock(\App\Services\SerpApiClientService::class);
        $mockSerp->method('searchGoogle')->willReturn([
            'results' => [
                [
                    'position' => 1,
                    'title' => 'Sample Title',
                    'link' => 'https://example.com/test',
                    'snippet' => 'Sample Snippet',
                    'domain' => 'example.com',
                ]
            ],
            'organic_results' => [
                [
                    'position' => 1,
                    'title' => 'Sample Title',
                    'link' => 'https://example.com/test',
                    'snippet' => 'Sample Snippet',
                    'domain' => 'example.com',
                ]
            ],
            'serp_features' => [],
            'search_url' => 'https://serpapi.com/search?q=test',
            'is_cached' => true,
        ]);

        $service = new \App\Services\KeywordRankCheckerService($mockSerp);
        $results = $service->checkBatch(
            ['test keyword 1', 'test keyword 2'],
            'example.com',
            ['country' => 'in', 'device' => 'desktop'],
            $this->admin->id
        );

        $this->assertCount(2, $results);
        $this->assertNotEmpty($results[0]['batch_id']);
        $this->assertStringStartsWith('B-', $results[0]['batch_id']);
        $this->assertEquals($results[0]['batch_id'], $results[1]['batch_id']);
    }

    public function test_seo_specialist_can_access_rank_tracker_and_export(): void
    {
        $seoUser = User::factory()->create(['name' => 'SEO Specialist']);
        $seoUser->assignRole('seo_specialist');

        // Can access rank tracker
        $response = $this->actingAs($seoUser)->get('/rank-tracker');
        $response->assertStatus(200);

        // Can access rank database
        $responseDb = $this->actingAs($seoUser)->get('/rank-database');
        $responseDb->assertStatus(200);

        // Can export CSV
        $responseExport = $this->actingAs($seoUser)->get('/rank-tracker/export/csv');
        $responseExport->assertStatus(200);

        // Can access agency clients
        $responseClients = $this->actingAs($seoUser)->get('/clients');
        $responseClients->assertStatus(200);

        // Cannot access content generation, blog creator, rewriter or articles
        $this->actingAs($seoUser)->get('/blog-creator')->assertStatus(403);
        $this->actingAs($seoUser)->get('/articles')->assertStatus(403);
        $this->actingAs($seoUser)->get('/rewriter')->assertStatus(403);
    }

    public function test_viewer_is_forbidden_from_rank_tracker_but_can_view_rank_database(): void
    {
        $viewer = User::factory()->create(['name' => 'Viewer User']);
        $viewer->assignRole('viewer');

        // Cannot access rank tracker (no track-ranks permission)
        $response = $this->actingAs($viewer)->get('/rank-tracker');
        $response->assertStatus(403);

        // Can access rank database (has view-rank-database permission)
        $responseDb = $this->actingAs($viewer)->get('/rank-database');
        $responseDb->assertStatus(200);

        // Cannot export CSV (no export-rank-data permission)
        $responseExport = $this->actingAs($viewer)->get('/rank-tracker/export/csv');
        $responseExport->assertStatus(403);
    }

    public function test_seo_specialist_cannot_delete_records_without_delete_permission(): void
    {
        $seoUser = User::factory()->create(['name' => 'SEO Specialist']);
        $seoUser->assignRole('seo_specialist');

        $record = KeywordRankCheck::create([
            'user_id' => $seoUser->id,
            'keyword' => 'test seo keyword',
            'target_domain' => 'example.com',
            'country' => 'in',
            'device' => 'desktop',
            'is_ranked' => true,
        ]);

        // Attempting to delete without delete-rank-data permission returns 403
        $response = $this->actingAs($seoUser)->delete('/rank-tracker/' . $record->id);
        $response->assertStatus(403);

        $this->assertDatabaseHas('keyword_rank_checks', ['id' => $record->id]);
    }
}

