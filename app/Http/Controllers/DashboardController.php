<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiPreset;
use App\Models\AiUsageLog;
use App\Models\Article;
use App\Models\Client;
use App\Models\RewriterJob;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the contextual dashboard (Admin Executive Command Center vs. Creator Studio).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->hasRole(['super_admin', 'admin']);

        // Allow administrators to toggle view perspective for auditing creator experience
        $requestedView = $request->query('view');
        $showAdminView = $isAdmin && $requestedView !== 'user';

        if ($showAdminView) {
            return $this->adminDashboard($request, $user);
        }

        return $this->userDashboard($request, $user);
    }

    /**
     * Render the Admin Executive Command Center.
     */
    protected function adminDashboard(Request $request, User $user)
    {
        // 1. Overall System Metrics
        $totalArticles = Article::count();
        $totalWords = (int) Article::sum('word_count');
        $avgSeoScore = round((float) (Article::avg('seo_score') ?: 0), 1);
        $avgReadingEase = round((float) (Article::avg('flesch_reading_ease') ?: 0), 1);
        $totalClients = Client::count();
        $activeClients = Client::where('is_active', true)->count();
        $totalUsers = User::count();

        // 2. Token & Cost Telemetry (Live AI Usage Logs)
        $totalCostUsd = round((float) (AiUsageLog::sum('estimated_cost_usd') ?: 0), 4);
        $totalCostInr = round((float) (AiUsageLog::sum('estimated_cost_inr') ?: 0), 2);
        if ($totalCostInr <= 0 && $totalCostUsd > 0) {
            $totalCostInr = round($totalCostUsd * 87.5, 2);
        }
        $totalLlmCalls = AiUsageLog::count();
        $totalTokens = (int) AiUsageLog::sum('total_tokens');

        // 3. WordPress Publishing Sync Metrics
        $wpPublishedCount = Article::whereNotNull('wordpress_post_url')->count();
        $wpPublishRate = $totalArticles > 0 ? round(($wpPublishedCount / $totalArticles) * 100, 1) : 0;

        // 4. Model Spend Breakdown
        $modelBreakdown = AiUsageLog::selectRaw('model, count(*) as calls_count, sum(total_tokens) as tokens_sum, sum(estimated_cost_usd) as cost_sum')
            ->groupBy('model')
            ->orderByDesc('cost_sum')
            ->get();

        // 5. Active LLM Presets
        $activePresets = AiPreset::where('is_active', true)->get();

        // 6. Recent Articles Portal-Wide (With User & Client relationships)
        $recentArticles = Article::with(['user'])
            ->latest()
            ->take(6)
            ->get();

        // 7. Agency Clients Roster with Health
        $clients = Client::latest()
            ->take(5)
            ->get();

        // 8. Recent Rewriter Jobs
        $recentRewrites = RewriterJob::with('user')
            ->latest()
            ->take(5)
            ->get();

        $metrics = compact(
            'totalArticles',
            'totalWords',
            'avgSeoScore',
            'avgReadingEase',
            'totalClients',
            'activeClients',
            'totalUsers',
            'totalCostUsd',
            'totalCostInr',
            'totalLlmCalls',
            'totalTokens',
            'wpPublishedCount',
            'wpPublishRate'
        );

        return view('pages.dashboard.admin', compact(
            'metrics',
            'recentArticles',
            'clients',
            'modelBreakdown',
            'activePresets',
            'recentRewrites',
            'user'
        ));
    }

    /**
     * Render the Creator / User Studio Dashboard.
     */
    protected function userDashboard(Request $request, User $user)
    {
        // 1. Personal Creator Metrics
        $myArticlesCount = Article::where('user_id', $user->id)->count();
        $myTotalWords = (int) Article::where('user_id', $user->id)->sum('word_count');
        $myAvgSeoScore = round((float) (Article::where('user_id', $user->id)->avg('seo_score') ?: 0), 1);
        $myAvgReadingEase = round((float) (Article::where('user_id', $user->id)->avg('flesch_reading_ease') ?: 0), 1);
        $myWpPublished = Article::where('user_id', $user->id)->whereNotNull('wordpress_post_url')->count();
        $myRewriterJobsCount = RewriterJob::where('user_id', $user->id)->count();

        // 2. Active Client Directives
        $activeClient = $user->active_client_id ? Client::find($user->active_client_id) : null;
        $allClients = Client::where('is_active', true)->orderBy('name')->get();

        // 3. User's Recent Articles
        $recentArticles = Article::where('user_id', $user->id)
            ->latest()
            ->take(6)
            ->get();

        // 4. User's Recent Rewriter Jobs
        $recentRewriterJobs = RewriterJob::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // 5. Active User AI Presets
        $activePreset = AiPreset::where('is_active', true)->first();

        $metrics = compact(
            'myArticlesCount',
            'myTotalWords',
            'myAvgSeoScore',
            'myAvgReadingEase',
            'myWpPublished',
            'myRewriterJobsCount'
        );

        return view('pages.dashboard.user', compact(
            'metrics',
            'activeClient',
            'allClients',
            'recentArticles',
            'recentRewriterJobs',
            'activePreset',
            'user'
        ));
    }
}
