<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessKeywordRankBatchJob;
use App\Models\Client;
use App\Models\KeywordRankCheck;
use App\Models\RankCheckBatch;
use App\Models\User;
use App\Services\KeywordRankCheckerService;
use App\Services\SerpApiClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KeywordRankController extends Controller
{
    /**
     * Check if user is an admin or super admin
     */
    protected function isAdmin(?User $user): bool
    {
        return $user && ($user->hasAnyRole(['admin', 'super_admin']) || $user->hasRole('admin') || $user->hasRole('super_admin'));
    }
    /**
     * Display the Rank Tracker Dashboard & History Table
     */
    public function index(Request $request, SerpApiClientService $serpClient)
    {
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);
        $activeClient = $user?->active_client_id ? Client::find($user->active_client_id) : null;
        $allClients = Client::where('is_active', true)->orderBy('name')->get();
        $allUsers = $isAdmin ? User::orderBy('name')->get() : collect();

        // Query Filters
        $clientId = $request->input('client_id');
        $filterUserId = $request->input('user_id');
        $search = $request->input('search');
        $rankFilter = $request->input('rank_filter'); // top3, top10, striking, unranked
        $country = $request->input('country');

        $query = KeywordRankCheck::query()->with(['client', 'user']);
        $kpiBaseQuery = KeywordRankCheck::query();

        // Role-based scoping: Admins view all, regular users view only their own records
        if (! $isAdmin) {
            $query->where('user_id', $user->id);
            $kpiBaseQuery->where('user_id', $user->id);
        } elseif (! empty($filterUserId) && $filterUserId !== 'all') {
            $query->where('user_id', $filterUserId);
            $kpiBaseQuery->where('user_id', $filterUserId);
        }

        if ($request->filled('client_id')) {
            if ($clientId === 'none') {
                $query->whereNull('client_id');
                $kpiBaseQuery->whereNull('client_id');
            } elseif ($clientId !== 'all') {
                $query->where('client_id', $clientId);
                $kpiBaseQuery->where('client_id', $clientId);
            }
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('keyword', 'like', "%{$search}%")
                    ->orWhere('target_domain', 'like', "%{$search}%")
                    ->orWhere('ranking_title', 'like', "%{$search}%");
            });
        }

        if ($rankFilter === 'top3') {
            $query->top3();
        } elseif ($rankFilter === 'top10') {
            $query->top10();
        } elseif ($rankFilter === 'striking') {
            $query->whereBetween('position', [11, 50]);
        } elseif ($rankFilter === 'unranked') {
            $query->unranked();
        }

        if (! empty($country) && $country !== 'all') {
            $query->where('country', strtolower($country));
            $kpiBaseQuery->where('country', strtolower($country));
        }

        $viewMode = $request->input('view_mode', 'batches');
        $batches = $this->paginateBatches($query, 15);
        $rankChecks = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        // Calculate Telemetry KPI Metrics (scoped by role, user, and client filters)
        $totalQueries = (clone $kpiBaseQuery)->count();
        $top3Count = (clone $kpiBaseQuery)->top3()->count();
        $top10Count = (clone $kpiBaseQuery)->top10()->count();
        $strikingCount = (clone $kpiBaseQuery)->whereBetween('position', [11, 50])->count();
        $unrankedCount = (clone $kpiBaseQuery)->unranked()->count();
        $avgRank = round((clone $kpiBaseQuery)->ranked()->avg('position') ?? 0, 1);

        $kpis = [
            'total' => $totalQueries,
            'top3' => $top3Count,
            'top10' => $top10Count,
            'striking' => $strikingCount,
            'unranked' => $unrankedCount,
            'avg_position' => $avgRank,
        ];

        // Fetch Live SerpApi Credits
        $credits = $serpClient->getAccountCredits();

        // Common Google Search Countries
        $countries = [
            'in' => 'India',
            'us' => 'United States',
            'gb' => 'United Kingdom',
            'ca' => 'Canada',
            'au' => 'Australia',
            'de' => 'Germany',
            'fr' => 'France',
            'es' => 'Spain',
            'it' => 'Italy',
            'nl' => 'Netherlands',
            'br' => 'Brazil',
            'jp' => 'Japan',
            'sg' => 'Singapore',
            'ae' => 'United Arab Emirates',
        ];

        // Pre-fill target domain from active client if available
        $prefillDomain = $activeClient?->website_url ?? '';

        return view('pages.rank_tracker.index', compact(
            'rankChecks',
            'batches',
            'viewMode',
            'kpis',
            'credits',
            'activeClient',
            'allClients',
            'allUsers',
            'isAdmin',
            'countries',
            'prefillDomain',
            'clientId',
            'filterUserId',
            'search',
            'rankFilter',
            'country'
        ));
    }

    /**
     * Execute live rank check for single or bulk keywords
     */
    public function check(
        Request $request,
        KeywordRankCheckerService $rankService,
        SerpApiClientService $serpClient
    ) {
        $validated = $request->validate([
            'target_domain' => 'required|string|max:255',
            'keywords' => 'required|string',
            'country' => 'nullable|string|max:10',
            'location' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:10',
            'device' => 'nullable|string|in:desktop,mobile',
            'match_type' => 'nullable|string|in:domain,exact_url',
            'target_url' => 'nullable|string|max:500',
            'client_id' => 'nullable|integer',
            'force_refresh' => 'nullable|boolean',
        ]);

        if (! $serpClient->hasKey()) {
            return response()->json([
                'success' => false,
                'message' => 'SerpApi API key is not configured! Please set your SerpApi key in Settings & Presets.',
            ], 422);
        }

        $rawKeywords = $validated['keywords'];
        // Split by newlines and commas
        $lines = preg_split('/[\r\n,]+/', $rawKeywords);
        $keywordsList = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (! empty($trimmed)) {
                $keywordsList[] = $trimmed;
            }
        }

        if (empty($keywordsList)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide at least one keyword to check.',
            ], 422);
        }

        $user = $request->user();
        // Generic mode fix: If client_id is explicitly submitted as empty / none, strictly keep it null!
        $clientId = ! empty($validated['client_id']) ? (int) $validated['client_id'] : null;

        $normalizedLocation = ! empty($validated['location']) ? trim($validated['location']) : null;
        if ($normalizedLocation) {
            $normalizedLocation = preg_replace('/\s*,\s*/', ', ', $normalizedLocation);
        }

        $options = [
            'country' => $validated['country'] ?? 'in',
            'location' => $normalizedLocation,
            'language' => $validated['language'] ?? 'en',
            'device' => $validated['device'] ?? 'desktop',
            'match_type' => $validated['match_type'] ?? 'domain',
            'target_url' => $validated['target_url'] ?? null,
            'force_refresh' => true, // Enforce no_cache=true to guarantee real-time un-cached Google results
        ];

        try {
            $results = $rankService->checkBatch(
                $keywordsList,
                $validated['target_domain'],
                $options,
                $user?->id,
                $clientId
            );

            // Fetch freshly updated credits
            $credits = $serpClient->getAccountCredits(true);
            $batchId = $results[0]['batch_id'] ?? null;

            return response()->json([
                'success' => true,
                'batch_id' => $batchId,
                'results' => $results,
                'credits' => $credits,
                'count' => count($results),
                'message' => 'Checked '.count($results).' keyword(s) successfully!',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rank check failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return live Top 10 SERP competitor landscape for a check (scoped by role)
     */
    public function getSerpCompetitors(Request $request, $id)
    {
        $user = $request->user();
        $check = KeywordRankCheck::findOrFail($id);

        if (! $this->isAdmin($user) && $check->user_id !== $user?->id) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        return response()->json([
            'id' => $check->id,
            'keyword' => $check->keyword,
            'target_domain' => $check->target_domain,
            'position' => $check->position,
            'is_ranked' => $check->is_ranked,
            'ranking_url' => $check->ranking_url,
            'country' => strtoupper($check->country),
            'location' => $check->location,
            'device' => $check->device,
            'competitors' => $check->top_competitors ?? [],
            'serp_features' => $check->serp_features ?? [],
            'checked_at' => $check->checked_at ? $check->checked_at->format('M d, Y H:i') : null,
        ]);
    }

    /**
     * Return live matching canonical locations from SerpApi for UI autocomplete
     */
    public function getLocations(Request $request, SerpApiClientService $serpClient)
    {
        $query = $request->input('q', '');
        $country = $request->input('country', 'in');

        $locations = $serpClient->getMatchingLocations($query, $country);

        return response()->json($locations);
    }

    /**
     * Return live SerpApi credit balance via AJAX
     */
    public function getLiveCredits(SerpApiClientService $serpClient)
    {
        $credits = $serpClient->getAccountCredits(true);

        return response()->json($credits);
    }

    /**
     * Delete a single check record (scoped by role)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $check = KeywordRankCheck::findOrFail($id);

        if (! $this->isAdmin($user) && $check->user_id !== $user?->id) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            abort(403, 'Unauthorized.');
        }

        $check->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Rank check deleted successfully.',
            ]);
        }

        return back()->with('success', 'Rank check deleted successfully.');
    }

    /**
     * Clear all checks for active client or all (scoped by role)
     */
    public function clearHistory(Request $request)
    {
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);
        $clientId = $request->input('client_id');

        $query = KeywordRankCheck::query();

        // Non-admins can only clear their own records
        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if (! empty($clientId) && $clientId !== 'all') {
            $query->where('client_id', $clientId);
        }

        $count = $query->count();
        $query->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Cleared {$count} rank check record(s).",
            ]);
        }

        return back()->with('success', "Cleared {$count} rank check record(s).");
    }

    /**
     * Standard CSV Columns requested by user
     */
    protected function getCsvHeader(): array
    {
        return [
            'ID',
            'Keyword',
            'Domain',
            'Target Location',
            'Device',
            'Search Engine',
            'Organic Rank',
            'Actual SERP Position',
            'Ranking Landing Page URL',
            'Status',
            'Crawled Date',
        ];
    }

    /**
     * Format a rank check item into the exact export CSV row
     */
    protected function formatCsvRow(KeywordRankCheck $item): array
    {
        $domain = $item->target_domain;
        if (! str_starts_with($domain, 'http://') && ! str_starts_with($domain, 'https://')) {
            $domain = 'https://'.rtrim($domain, '/').'/';
        }

        $rankingUrl = $item->ranking_url;
        if (empty($rankingUrl)) {
            $rankingUrl = $item->target_url ?: $domain;
        }

        $organicRank = $item->position ? (string) $item->position : 'Not in Top 50';
        $actualPosition = $item->position ? (string) $item->position : 'Not in Top 50';

        $crawledDate = $item->checked_at
            ? $item->checked_at->format('Y-m-d H:i:s')
            : ($item->created_at ? $item->created_at->format('Y-m-d H:i:s') : '');

        $targetLocation = $item->location;
        if (empty($targetLocation)) {
            $targetLocation = ! empty($item->country) ? strtoupper($item->country) : 'National';
        }

        return [
            $item->id,
            $item->keyword,
            $domain,
            $targetLocation,
            ucfirst($item->device ?? 'Desktop'),
            'Google',
            $organicRank,
            $actualPosition,
            $rankingUrl,
            'Completed',
            $crawledDate,
        ];
    }

    /**
     * Export ranking results to CSV (scoped by role)
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);
        $clientId = $request->input('client_id');

        $query = KeywordRankCheck::query()->with(['client', 'user'])->orderBy('id', 'desc');

        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('client_id')) {
            if ($clientId === 'none') {
                $query->whereNull('client_id');
            } elseif ($clientId !== 'all') {
                $query->where('client_id', $clientId);
            }
        }

        $records = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="keyword_rankings_'.date('Y_m_d_His').'.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->getCsvHeader());

            foreach ($records as $item) {
                fputcsv($handle, $this->formatCsvRow($item));
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Download comprehensive single query CSV matching exact table columns
     */
    public function exportSingleCsv(Request $request, $id): StreamedResponse
    {
        $user = $request->user();
        $check = KeywordRankCheck::with(['client', 'user'])->findOrFail($id);

        if (! $this->isAdmin($user) && $check->user_id !== $user?->id) {
            abort(403, 'Unauthorized to export this record.');
        }

        $filename = 'rank_query_'.Str::slug($check->keyword).'_'.date('Y_m_d_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->stream(function () use ($check) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->getCsvHeader());
            fputcsv($handle, $this->formatCsvRow($check));

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Dedicated Rank Database Archive & Audits Page (modeled like Content Database)
     */
    public function database(Request $request)
    {
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);

        $allClients = Client::where('is_active', true)->orderBy('name')->get();
        $allUsers = $isAdmin ? User::orderBy('name')->get() : collect();

        // Filters
        $search = $request->input('search');
        $clientId = $request->input('client_id');
        $rankFilter = $request->input('rank_filter');
        $device = $request->input('device');
        $country = $request->input('country');
        $filterUserId = $request->input('user_id');

        $query = KeywordRankCheck::query()->with(['client', 'user']);

        // Role scoping
        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        } elseif (! empty($filterUserId) && $filterUserId !== 'all') {
            $query->where('user_id', $filterUserId);
        }

        // Search
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('keyword', 'like', "%{$search}%")
                    ->orWhere('target_domain', 'like', "%{$search}%")
                    ->orWhere('ranking_title', 'like', "%{$search}%")
                    ->orWhere('ranking_url', 'like', "%{$search}%");
            });
        }

        // Client filter
        if ($request->filled('client_id')) {
            if ($clientId === 'none') {
                $query->whereNull('client_id');
            } elseif ($clientId !== 'all') {
                $query->where('client_id', $clientId);
            }
        }

        // Rank Tier filter
        if ($rankFilter === 'top3') {
            $query->top3();
        } elseif ($rankFilter === 'top10') {
            $query->top10();
        } elseif ($rankFilter === 'striking') {
            $query->whereBetween('position', [11, 50]);
        } elseif ($rankFilter === 'unranked') {
            $query->unranked();
        }

        // Device filter
        if (! empty($device) && $device !== 'all') {
            $query->where('device', strtolower($device));
        }

        // Country filter
        if (! empty($country) && $country !== 'all') {
            $query->where('country', strtolower($country));
        }

        $viewMode = $request->input('view_mode', 'batches');
        $batches = $this->paginateBatches($query, 15);
        $rankChecks = (clone $query)->orderBy('id', 'desc')->paginate(20)->withQueryString();

        // 5 KPI Metric Cards (scoped by role and client)
        $kpiBaseQuery = KeywordRankCheck::query();
        if (! $isAdmin) {
            $kpiBaseQuery->where('user_id', $user->id);
        } elseif (! empty($filterUserId) && $filterUserId !== 'all') {
            $kpiBaseQuery->where('user_id', $filterUserId);
        }
        if ($request->filled('client_id')) {
            if ($clientId === 'none') {
                $kpiBaseQuery->whereNull('client_id');
            } elseif ($clientId !== 'all') {
                $kpiBaseQuery->where('client_id', $clientId);
            }
        }

        $metrics = [
            'total_queries' => (clone $kpiBaseQuery)->count(),
            'top3' => (clone $kpiBaseQuery)->top3()->count(),
            'top10' => (clone $kpiBaseQuery)->top10()->count(),
            'striking' => (clone $kpiBaseQuery)->whereBetween('position', [11, 50])->count(),
            'unranked' => (clone $kpiBaseQuery)->unranked()->count(),
            'avg_position' => round((clone $kpiBaseQuery)->ranked()->avg('position') ?? 0, 1),
        ];

        $countries = [
            'in' => 'India',
            'us' => 'United States',
            'gb' => 'United Kingdom',
            'ca' => 'Canada',
            'au' => 'Australia',
            'de' => 'Germany',
            'fr' => 'France',
            'ae' => 'United Arab Emirates',
        ];

        return view('pages.rank_tracker.database', compact(
            'rankChecks',
            'batches',
            'viewMode',
            'metrics',
            'allClients',
            'allUsers',
            'isAdmin',
            'countries',
            'search',
            'clientId',
            'rankFilter',
            'device',
            'country',
            'filterUserId'
        ));
    }

    /**
     * Paginate scan batches with eager loaded summary telemetry
     */
    protected function paginateBatches($query, int $perPage = 15)
    {
        $batchIdsQuery = (clone $query)
            ->reorder()
            ->select('batch_id')
            ->selectRaw('MAX(id) as latest_id')
            ->whereNotNull('batch_id')
            ->groupBy('batch_id')
            ->orderByDesc('latest_id');

        $paginatedBatchIds = $batchIdsQuery->paginate($perPage)->withQueryString();

        $batchRecords = (clone $query)->with(['client', 'user'])
            ->whereIn('batch_id', $paginatedBatchIds->pluck('batch_id'))
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('batch_id');

        return $paginatedBatchIds->through(function ($item) use ($batchRecords) {
            $items = $batchRecords->get($item->batch_id, collect());
            $first = $items->first();
            if (! $first) {
                return null;
            }

            $totalKws = $items->count();
            $rankedCount = $items->where('is_ranked', true)->count();
            $top3Count = $items->where('is_ranked', true)->where('position', '<=', 3)->count();
            $top10Count = $items->where('is_ranked', true)->whereBetween('position', [1, 10])->count();
            $strikingCount = $items->where('is_ranked', true)->whereBetween('position', [11, 50])->count();
            $unrankedCount = $items->filter(fn ($r) => ! $r->is_ranked || is_null($r->position) || $r->position > 50)->count();

            $bestPosition = $items->where('is_ranked', true)->min('position');

            return (object) [
                'batch_id' => $item->batch_id,
                'target_domain' => $first->target_domain,
                'client' => $first->client,
                'user' => $first->user,
                'user_id' => $first->user_id,
                'country' => $first->country,
                'location' => $first->location,
                'device' => $first->device,
                'checked_at' => $first->checked_at ?? $first->created_at,
                'total_keywords' => $totalKws,
                'ranked_count' => $rankedCount,
                'top3_count' => $top3Count,
                'top10_count' => $top10Count,
                'striking_count' => $strikingCount,
                'unranked_count' => $unrankedCount,
                'best_position' => $bestPosition,
                'keywords_preview' => $items->pluck('keyword')->take(3)->implode(', ').($totalKws > 3 ? ' (+'.($totalKws - 3).' more)' : ''),
                'items' => $items,
            ];
        });
    }

    /**
     * Download CSV for an entire Scan Batch containing all its keywords
     */
    public function exportBatchCsv(Request $request, string $batchId): StreamedResponse
    {
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);

        $batchExists = KeywordRankCheck::where('batch_id', $batchId)->exists();
        if (! $batchExists) {
            abort(404, 'No keyword records found for this batch.');
        }

        if (! $isAdmin) {
            $userOwnsBatch = KeywordRankCheck::where('batch_id', $batchId)->where('user_id', $user?->id)->exists();
            if (! $userOwnsBatch) {
                abort(403, 'Unauthorized access to this batch.');
            }
        }

        $query = KeywordRankCheck::with(['client', 'user'])
            ->where('batch_id', $batchId)
            ->orderBy('id', 'asc');

        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            abort(404, 'No keyword records found for this batch.');
        }

        $domain = Str::slug($records->first()->target_domain ?: 'scan');
        $filename = 'rank_batch_'.$batchId.'_'.$domain.'_'.date('Y_m_d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->getCsvHeader());

            foreach ($records as $item) {
                fputcsv($handle, $this->formatCsvRow($item));
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Get JSON details of a scan batch for inspection modal
     */
    public function getBatchDetails(Request $request, string $batchId)
    {
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);

        $batchExists = KeywordRankCheck::where('batch_id', $batchId)->exists();
        if (! $batchExists) {
            return response()->json(['message' => 'Batch not found.'], 404);
        }

        if (! $isAdmin) {
            $userOwnsBatch = KeywordRankCheck::where('batch_id', $batchId)->where('user_id', $user?->id)->exists();
            if (! $userOwnsBatch) {
                return response()->json(['message' => 'Unauthorized access to this batch.'], 403);
            }
        }

        $query = KeywordRankCheck::with(['client', 'user'])
            ->where('batch_id', $batchId)
            ->orderBy('id', 'asc');

        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            return response()->json(['message' => 'Batch not found.'], 404);
        }

        $first = $records->first();

        return response()->json([
            'batch_id' => $batchId,
            'target_domain' => $first->target_domain,
            'client_name' => $first->client?->name ?? 'Generic Run',
            'country' => strtoupper($first->country),
            'location' => $first->location,
            'device' => ucfirst($first->device),
            'checked_at' => $first->checked_at ? $first->checked_at->format('Y-m-d H:i') : $first->created_at->format('Y-m-d H:i'),
            'scanned_ago' => $first->checked_at ? $first->checked_at->diffForHumans() : $first->created_at->diffForHumans(),
            'total_keywords' => $records->count(),
            'top3' => $records->where('is_ranked', true)->where('position', '<=', 3)->count(),
            'top10' => $records->where('is_ranked', true)->whereBetween('position', [1, 10])->count(),
            'striking' => $records->where('is_ranked', true)->whereBetween('position', [11, 50])->count(),
            'unranked' => $records->filter(fn ($r) => ! $r->is_ranked || is_null($r->position) || $r->position > 50)->count(),
            'keywords' => $records->map(function ($item) {
                return [
                    'id' => $item->id,
                    'keyword' => $item->keyword,
                    'position' => $item->position,
                    'display_rank' => $item->is_ranked && $item->position ? '#'.$item->position : '> 50 (Unranked)',
                    'is_ranked' => $item->is_ranked,
                    'ranking_url' => $item->ranking_url,
                    'ranking_title' => $item->ranking_title,
                    'rank_change' => $item->rank_change,
                    'serp_features' => $item->serp_features,
                ];
            }),
        ]);
    }

    /**
     * Delete an entire scan batch and all its checks
     */
    public function destroyBatch(Request $request, string $batchId)
    {
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);

        $batchExists = KeywordRankCheck::where('batch_id', $batchId)->exists();
        if (! $batchExists) {
            return response()->json(['message' => 'Batch not found.'], 404);
        }

        if (! $isAdmin) {
            $userOwnsBatch = KeywordRankCheck::where('batch_id', $batchId)->where('user_id', $user?->id)->exists();
            if (! $userOwnsBatch) {
                return response()->json(['message' => 'Unauthorized access to this batch.'], 403);
            }
        }

        $query = KeywordRankCheck::where('batch_id', $batchId);

        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        }

        $count = $query->delete();

        return response()->json([
            'success' => true,
            'deleted_count' => $count,
            'message' => 'Batch and its '.$count.' keyword records deleted successfully.',
        ]);
    }

    /**
     * Display the Bulk CSV Upload Studio
     */
    public function bulkUploadView(Request $request)
    {
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);

        $allClients = Client::where('is_active', true)->orderBy('name')->get();
        $activeClient = $user?->active_client_id ? Client::find($user->active_client_id) : null;

        // Recent batches
        $batchesQuery = RankCheckBatch::with(['client', 'user'])->latest();
        if (! $isAdmin) {
            $batchesQuery->where('user_id', $user->id);
        }
        $recentBatches = $batchesQuery->take(15)->get();

        return view('pages.rank_tracker.bulk', compact(
            'allClients',
            'activeClient',
            'recentBatches',
            'isAdmin'
        ));
    }

    /**
     * Download a clean CSV template for bulk keyword scanning
     */
    public function downloadSampleCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sample_bulk_keywords.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($handle, ['keyword', 'target_domain', 'location', 'target_url']);

            // Sample Rows
            fputcsv($handle, [
                'best eye doctor in Ahmedabad',
                'sterlinghospitals.com',
                'Ahmedabad, Gujarat, India',
                'https://www.sterlinghospitals.com/specialities/ophthalmology',
            ]);
            fputcsv($handle, [
                'best cardiac surgeon in Ahmedabad',
                'sterlinghospitals.com',
                'Ahmedabad, Gujarat, India',
                '',
            ]);
            fputcsv($handle, [
                'best hospital in Gujarat',
                '',
                '',
                '',
            ]);
            fputcsv($handle, [
                'top cardiologist near me',
                '',
                'Ahmedabad, Gujarat, India',
                '',
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Ingest CSV file, validate keywords, and dispatch background queue batch job
     */
    public function startBulkProcess(Request $request, KeywordRankCheckerService $checkerService, SerpApiClientService $serpClient)
    {
        $request->validate([
            'csv_file' => 'required|file|max:10240', // 10MB
            'target_domain' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'target_url' => 'nullable|string|max:500',
            'match_type' => 'nullable|in:domain,exact_url',
            'country' => 'nullable|string|max:10',
            'language' => 'nullable|string|max:10',
            'device' => 'nullable|in:desktop,mobile',
            'depth' => 'nullable|integer|in:10,20,30,50',
            'client_id' => 'nullable|exists:clients,id',
        ]);

        $user = $request->user();
        $targetDomain = trim($request->input('target_domain'));
        $cleanTargetDomain = preg_replace('#^https?://#i', '', $targetDomain);
        $cleanTargetDomain = preg_replace('#^www\.#i', '', $cleanTargetDomain);
        $cleanTargetDomain = trim(explode('/', $cleanTargetDomain)[0]);

        $country = strtolower(trim($request->input('country', 'in')));
        $location = $request->input('location');
        if (! empty($location)) {
            $location = preg_replace('/\s*,\s*/', ', ', trim($location));
            if (substr_count($location, ',') < 2) {
                $location = $serpClient->canonicalizeLocation($location, $country) ?: $location;
            }
        }

        $defaultOptions = [
            'target_domain' => $cleanTargetDomain,
            'target_url' => $request->input('target_url'),
            'match_type' => $request->input('match_type', 'domain'),
            'location' => $location,
            'country' => $country,
            'language' => strtolower(trim($request->input('language', 'en'))),
            'device' => strtolower($request->input('device', 'desktop')),
            'depth' => (int) $request->input('depth', 50),
        ];

        // Secure file storage per Rule 4
        $uploadedFile = $request->file('csv_file');
        $originalExt = $uploadedFile->getClientOriginalExtension() ?: 'csv';
        $safeDomain = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cleanTargetDomain);
        $timestamp = date('Ymd_His');
        $storedFilename = "Bulk_Scan_{$safeDomain}_{$timestamp}.{$originalExt}";
        $uploadedFile->storeAs('imports', $storedFilename, 'local');

        // Parse & extract keywords from CSV
        try {
            $parsedKeywords = $checkerService->parseKeywordsFromCsv($uploadedFile, $defaultOptions);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse CSV file: ' . $e->getMessage(),
            ], 422);
        }

        if (empty($parsedKeywords)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid keywords found in the uploaded CSV. Ensure the CSV contains a "keyword" column or a list of search queries.',
            ], 422);
        }

        $batchId = 'B-' . date('Ymd-His') . '-' . strtolower(Str::random(4));

        $batch = RankCheckBatch::create([
            'batch_id' => $batchId,
            'user_id' => $user->id,
            'client_id' => $request->input('client_id') ?: $user->active_client_id,
            'filename' => $uploadedFile->getClientOriginalName(),
            'target_domain' => $cleanTargetDomain,
            'target_url' => $defaultOptions['target_url'],
            'match_type' => $defaultOptions['match_type'],
            'location' => $location,
            'country' => $country,
            'language' => $defaultOptions['language'],
            'device' => $defaultOptions['device'],
            'depth' => $defaultOptions['depth'],
            'total_keywords' => count($parsedKeywords),
            'completed_keywords' => 0,
            'failed_keywords' => 0,
            'status' => 'pending',
            'extracted_keywords' => $parsedKeywords,
            'started_at' => null,
            'completed_at' => null,
        ]);

        // Dispatch background queue job
        ProcessKeywordRankBatchJob::dispatch($batch->id);

        return response()->json([
            'success' => true,
            'batch_id' => $batch->batch_id,
            'total_keywords' => $batch->total_keywords,
            'target_domain' => $batch->target_domain,
            'location' => $batch->location,
            'message' => 'Batch initialized. ' . $batch->total_keywords . ' keywords queued for background scanning.',
        ]);
    }

    /**
     * Poll live progress of a running background scan batch
     */
    public function getBatchProgress(Request $request, string $batchId)
    {
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);

        $batch = RankCheckBatch::where('batch_id', $batchId)->first();
        if (! $batch) {
            return response()->json(['message' => 'Batch not found.'], 404);
        }

        if (! $isAdmin && $batch->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized access to this batch.'], 403);
        }

        // Fetch latest 5 checks completed in this batch
        $recentChecks = KeywordRankCheck::where('batch_id', $batchId)
            ->latest('id')
            ->take(6)
            ->get(['id', 'keyword', 'position', 'is_ranked', 'ranking_url', 'ranking_title'])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'keyword' => $item->keyword,
                    'position' => $item->position,
                    'is_ranked' => $item->is_ranked,
                    'display_rank' => $item->is_ranked ? '#' . $item->position : '> 50 (Unranked)',
                    'ranking_url' => $item->ranking_url,
                    'ranking_title' => $item->ranking_title,
                ];
            });

        return response()->json([
            'batch_id' => $batch->batch_id,
            'status' => $batch->status,
            'total_keywords' => $batch->total_keywords,
            'completed_keywords' => $batch->completed_keywords,
            'failed_keywords' => $batch->failed_keywords,
            'progress_percentage' => $batch->progress_percentage,
            'top_3_count' => $batch->top_3_count,
            'top_10_count' => $batch->top_10_count,
            'page_1_count' => $batch->page_1_count,
            'unranked_count' => $batch->unranked_count,
            'started_at' => $batch->started_at?->diffForHumans(),
            'completed_at' => $batch->completed_at?->diffForHumans(),
            'recent_checks' => $recentChecks,
        ]);
    }

    /**
     * Cancel a running background batch
     */
    public function cancelBatch(Request $request, string $batchId)
    {
        $user = $request->user();
        $isAdmin = $this->isAdmin($user);

        $batch = RankCheckBatch::where('batch_id', $batchId)->first();
        if (! $batch) {
            return response()->json(['message' => 'Batch not found.'], 404);
        }

        if (! $isAdmin && $batch->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized access to this batch.'], 403);
        }

        $batch->update([
            'status' => 'cancelled',
            'error_message' => 'Cancelled by user ' . ($user->name ?? 'User'),
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Batch scan cancelled successfully.',
        ]);
    }
}
