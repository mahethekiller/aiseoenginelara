<?php

namespace App\Services;

use App\Models\SerpApiCache;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SerpApiClientService
{
    protected ?string $apiKey = null;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? $this->resolveApiKey();
    }

    /**
     * Resolve SerpApi key hierarchically: Database -> JSON Config -> .env
     */
    public function resolveApiKey(): ?string
    {
        // 1. Check Database system_settings
        $dbKey = SystemSetting::get('serpapi_key');
        if (! empty($dbKey) && ! str_contains($dbKey, '••••')) {
            return $dbKey;
        }

        // 2. Check JSON app_config.json
        $path = 'config/app_config.json';
        if (Storage::disk('local')->exists($path)) {
            $config = json_decode(Storage::disk('local')->get($path), true);
            if (! empty($config['api_keys']['serpapi']) && ! str_contains($config['api_keys']['serpapi'], '••••')) {
                $fileKey = $config['api_keys']['serpapi'];
                // Persist to DB for future queries
                SystemSetting::set('serpapi_key', $fileKey, 'api_keys', true, 'SerpApi API key for Google SERP intelligence');

                return $fileKey;
            }
        }

        // 3. Check .env variables
        $envKey = env('SERPAPI_API_KEY') ?: env('SERP_API_KEY');
        if (! empty($envKey)) {
            SystemSetting::set('serpapi_key', $envKey, 'api_keys', true, 'SerpApi API key for Google SERP intelligence');

            return $envKey;
        }

        return null;
    }

    /**
     * Set and persist SerpApi key directly to database
     */
    public function setApiKey(string $key): void
    {
        $this->apiKey = $key;
        SystemSetting::set('serpapi_key', $key, 'api_keys', true, 'SerpApi API key for Google SERP intelligence');

        // Also sync to app_config.json for consistency
        $path = 'config/app_config.json';
        if (Storage::disk('local')->exists($path)) {
            $config = json_decode(Storage::disk('local')->get($path), true) ?: [];
            $config['api_keys']['serpapi'] = $key;
            Storage::disk('local')->put($path, json_encode($config, JSON_PRETTY_PRINT));
        }

        // Clear account credits cache on new key
        Cache::forget('serpapi_account_credits');
    }

    /**
     * Check if a valid API key is present
     */
    public function hasKey(): bool
    {
        $key = $this->resolveApiKey();

        return ! empty($key) && $key !== 'demo_key';
    }

    /**
     * Fetch Live Account Credits directly from SerpApi (https://serpapi.com/account)
     * Cached for 15 minutes unless $forceRefresh is true
     */
    public function getAccountCredits(bool $forceRefresh = false): array
    {
        $key = $this->resolveApiKey();

        if (empty($key)) {
            return [
                'success' => false,
                'has_key' => false,
                'error' => 'SerpApi API key is not configured in Settings.',
                'searches_left' => 0,
                'this_month_usage' => 0,
                'searches_per_month' => 0,
                'plan_name' => 'Not Configured',
            ];
        }

        $cacheKey = 'serpapi_account_credits';

        if (! $forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(8)->get('https://serpapi.com/account', [
                'api_key' => $key,
            ]);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error'] ?? 'HTTP '.$response->status().' - Failed to fetch account credits';

                return [
                    'success' => false,
                    'has_key' => true,
                    'error' => $errorMessage,
                    'searches_left' => 0,
                    'this_month_usage' => 0,
                    'searches_per_month' => 0,
                    'plan_name' => 'Error',
                ];
            }

            $data = $response->json();

            $totalLeft = isset($data['total_searches_left']) 
                ? (int) $data['total_searches_left'] 
                : (int) ($data['plan_searches_left'] ?? 0);

            $credits = [
                'success' => true,
                'has_key' => true,
                'account_id' => $data['account_id'] ?? null,
                'account_email' => $data['account_email'] ?? null,
                'account_status' => $data['account_status'] ?? 'Active',
                'plan_name' => $data['plan_name'] ?? 'Production',
                'searches_per_month' => (int) ($data['searches_per_month'] ?? 0),
                'searches_left' => $totalLeft,
                'plan_searches_left' => (int) ($data['plan_searches_left'] ?? 0),
                'this_month_usage' => (int) ($data['this_month_usage'] ?? 0),
                'total_searches_left' => $totalLeft,
                'extra_credits' => (int) ($data['extra_credits'] ?? 0),
                'last_hour_searches' => (int) ($data['last_hour_searches'] ?? 0),
                'cached_at' => now()->toIso8601String(),
            ];

            Cache::put($cacheKey, $credits, now()->addMinutes(15));

            return $credits;
        } catch (\Throwable $e) {
            Log::warning('SerpApi getAccountCredits failed: '.$e->getMessage());

            return [
                'success' => false,
                'has_key' => true,
                'error' => 'Connection failed: '.$e->getMessage(),
                'searches_left' => 0,
                'this_month_usage' => 0,
                'searches_per_month' => 0,
                'plan_name' => 'Connection Error',
            ];
        }
    }

    /**
     * Resolve a raw city or location to its exact Google Canonical Name via SerpApi Locations API
     */
    public function canonicalizeLocation(string $location, ?string $country = null): string
    {
        $raw = trim($location);
        if (empty($raw)) {
            return '';
        }

        // If already looks canonical (contains commas like "City,State,Country")
        if (substr_count($raw, ',') >= 2) {
            return $raw;
        }

        $cacheKey = 'serpapi_loc_'.md5(strtolower($raw).'_'.strtolower($country ?? ''));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($raw, $country) {
            try {
                $response = Http::timeout(5)->get('https://serpapi.com/locations.json', [
                    'q' => $raw,
                    'limit' => 5,
                ]);

                if ($response->successful()) {
                    $locations = $response->json();
                    if (! empty($locations) && is_array($locations)) {
                        if ($country) {
                            $countryUpper = strtoupper($country);
                            foreach ($locations as $loc) {
                                if (isset($loc['country_code']) && strtoupper($loc['country_code']) === $countryUpper) {
                                    return $loc['canonical_name'] ?? $raw;
                                }
                            }
                        }

                        return $locations[0]['canonical_name'] ?? $raw;
                    }
                }
            } catch (\Throwable $e) {
                Log::debug('SerpApi location resolution failed: '.$e->getMessage());
            }

            return $raw;
        });
    }

    /**
     * Fetch matching canonical locations from SerpApi for live UI autocomplete suggestions
     */
    public function getMatchingLocations(string $query, ?string $country = null, int $limit = 6): array
    {
        $raw = trim($query);
        if (strlen($raw) < 2) {
            return [];
        }

        $cacheKey = 'serpapi_loc_sugg_'.md5(strtolower($raw).'_'.strtolower($country ?? ''));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($raw, $country, $limit) {
            try {
                $response = Http::timeout(4)->get('https://serpapi.com/locations.json', [
                    'q' => $raw,
                    'limit' => 10,
                ]);

                if ($response->successful()) {
                    $locations = $response->json();
                    if (! empty($locations) && is_array($locations)) {
                        $countryUpper = $country ? strtoupper($country) : null;
                        $results = [];

                        // Prioritize matching country if provided
                        if ($countryUpper) {
                            usort($locations, function ($a, $b) use ($countryUpper) {
                                $aMatch = isset($a['country_code']) && strtoupper($a['country_code']) === $countryUpper;
                                $bMatch = isset($b['country_code']) && strtoupper($b['country_code']) === $countryUpper;
                                if ($aMatch && ! $bMatch) {
                                    return -1;
                                }
                                if (! $aMatch && $bMatch) {
                                    return 1;
                                }

                                return 0;
                            });
                        }

                        foreach (array_slice($locations, 0, $limit) as $item) {
                            $reach = (int) ($item['reach'] ?? 0);
                            $reachFormatted = $reach > 1000000
                                ? round($reach / 1000000, 1).'M reach'
                                : ($reach > 1000 ? round($reach / 1000).'K reach' : '');

                            $results[] = [
                                'canonical_name' => $item['canonical_name'] ?? ($item['name'] ?? ''),
                                'name' => $item['name'] ?? '',
                                'country_code' => $item['country_code'] ?? '',
                                'target_type' => $item['target_type'] ?? 'Location',
                                'reach' => $reachFormatted,
                            ];
                        }

                        return $results;
                    }
                }
            } catch (\Throwable $e) {
                Log::debug('SerpApi getMatchingLocations failed: '.$e->getMessage());
            }

            return [];
        });
    }

    /**
     * Search Google via SerpApi (Top 50 organic results)
     * Features persistent 24-hour database caching.
     */
    public function searchGoogle(array $params, bool $forceRefresh = false): array
    {
        $key = $this->resolveApiKey();

        if (empty($key)) {
            throw new \RuntimeException('SerpApi API Key is not configured. Please add your SerpApi key in Settings & Presets.');
        }

        $gl = strtolower(trim($params['gl'] ?? 'us'));
        $googleDomains = [
            'in' => 'google.co.in',
            'gb' => 'google.co.uk',
            'uk' => 'google.co.uk',
            'au' => 'google.com.au',
            'ca' => 'google.ca',
            'ae' => 'google.ae',
            'de' => 'google.de',
            'fr' => 'google.fr',
            'es' => 'google.es',
            'it' => 'google.it',
            'jp' => 'google.co.jp',
            'br' => 'google.com.br',
            'sg' => 'google.com.sg',
            'nl' => 'google.nl',
        ];
        $googleDomain = $params['google_domain'] ?? ($googleDomains[$gl] ?? 'google.com');

        // Standardize parameters for Google search matching real regional browsing
        $searchParams = [
            'engine' => 'google',
            'google_domain' => $googleDomain,
            'q' => trim($params['q'] ?? ''),
            'gl' => $gl,
            'hl' => strtolower(trim($params['hl'] ?? 'en')),
            'device' => strtolower($params['device'] ?? 'desktop') === 'mobile' ? 'mobile' : 'desktop',
        ];

        if (! empty($params['start']) && (int) $params['start'] > 0) {
            $searchParams['start'] = (int) $params['start'];
        }

        if (! empty($params['location'])) {
            $canonicalLoc = $this->canonicalizeLocation($params['location'], $searchParams['gl']);
            $searchParams['location'] = $canonicalLoc ?: trim($params['location']);
        }

        if (empty($searchParams['q'])) {
            throw new \InvalidArgumentException('Search query keyword (q) is required.');
        }

        // Cache key hash
        $cacheParams = $searchParams;
        ksort($cacheParams);
        $cacheKey = md5('serp_google_'.json_encode($cacheParams));

        // Check persistent database cache
        if (! $forceRefresh) {
            $cached = SerpApiCache::valid()->where('cache_key', $cacheKey)->first();
            if ($cached && ! empty($cached->response_data)) {
                $data = $cached->response_data;
                $data['is_cached'] = true;
                $data['cached_at'] = $cached->created_at ? $cached->created_at->toIso8601String() : now()->toIso8601String();

                return $data;
            }
        }

        // Execute live authenticated HTTP request
        try {
            $apiParams = array_merge($searchParams, [
                'api_key' => $key,
            ]);

            $response = Http::timeout(25)->get('https://serpapi.com/search.json', $apiParams);

            if ($response->failed()) {
                $json = $response->json();
                $errorMsg = $json['error'] ?? 'SerpApi returned HTTP '.$response->status();
                throw new \RuntimeException('SerpApi Error: '.$errorMsg);
            }

            $responseData = $response->json();

            if (isset($responseData['error'])) {
                throw new \RuntimeException('SerpApi Error: '.$responseData['error']);
            }

            // Save fresh response to 24-hour persistent database cache
            SerpApiCache::updateOrCreate(
                ['cache_key' => $cacheKey],
                [
                    'engine' => 'google',
                    'query_params' => $searchParams,
                    'response_data' => $responseData,
                    'api_units_spent' => 1,
                    'expires_at' => now()->addHours(24),
                ]
            );

            // Invalidate in-memory account credits cache so balance updates on next fetch
            Cache::forget('serpapi_account_credits');

            $responseData['is_cached'] = false;
            $responseData['cached_at'] = now()->toIso8601String();

            return $responseData;
        } catch (\Throwable $e) {
            Log::error('SerpApiClientService searchGoogle failed', [
                'params' => $searchParams,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
