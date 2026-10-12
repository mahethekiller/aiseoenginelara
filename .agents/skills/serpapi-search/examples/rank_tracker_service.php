<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Production-ready SerpApi Client & Rank Tracker Service.
 */
class SerpApiRankTrackerService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://serpapi.com/search.json';
    protected string $locationUrl = 'https://serpapi.com/locations.json';
    protected string $accountUrl = 'https://serpapi.com/account';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?: config('services.serpapi.key', env('SERPAPI_API_KEY', ''));
    }

    /**
     * Executes a Google SERP search query with normalized parameters.
     */
    public function search(array $params): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('SERPAPI_API_KEY is not configured.');
        }

        // Canonical location sanitation (comma followed by single space)
        if (!empty($params['location'])) {
            $params['location'] = preg_replace('/\s*,\s*/', ', ', trim($params['location']));
        }

        $payload = array_merge([
            'engine'        => 'google',
            'api_key'       => $this->apiKey,
            'output'        => 'json',
            'device'        => 'desktop',
            'gl'            => 'in',
            'hl'            => 'en',
            'google_domain' => 'google.co.in',
            'start'         => 0,
            'no_cache'      => false,
        ], $params);

        $response = Http::timeout(60)
            ->retry(2, 500)
            ->get($this->baseUrl, $payload);

        if (!$response->successful()) {
            $errorMessage = $response->json('error') ?? 'HTTP status ' . $response->status();
            Log::error('SerpApi Request Failed', [
                'error'  => $errorMessage,
                'params' => $payload,
            ]);
            throw new \RuntimeException("SerpApi error: {$errorMessage}");
        }

        return $response->json() ?? [];
    }

    /**
     * Crawls Google search pages to find the rank position of a target domain or URL.
     *
     * @param string $query Target keyword query
     * @param string $targetDomain Target domain or URL (e.g. sterlinghospitals.com)
     * @param string $location Geographic location (e.g. "Ahmedabad, Gujarat, India")
     * @param int $maxDepth Maximum SERP rank depth to inspect (e.g. 50 or 100)
     * @param string $device "desktop" or "mobile"
     * @return array Rank result breakdown
     */
    public function checkKeywordRank(
        string $query,
        string $targetDomain,
        string $location = '',
        int $maxDepth = 50,
        string $device = 'desktop'
    ): array {
        $cleanTarget = $this->normalizeDomain($targetDomain);
        $pageSize = 10;
        $maxPages = (int) ceil($maxDepth / $pageSize);

        $localPackRank = null;
        $featuredSnippetFound = false;

        for ($page = 0; $page < $maxPages; $page++) {
            $start = $page * $pageSize;

            $data = $this->search([
                'q'        => $query,
                'location' => $location,
                'start'    => $start,
                'device'   => $device,
            ]);

            // 1. Inspect Position 0 Featured Snippet on Page 1
            if ($page === 0 && !empty($data['answer_box'])) {
                $boxLink = $data['answer_box']['link'] ?? '';
                if ($this->isDomainMatch($boxLink, $cleanTarget)) {
                    $featuredSnippetFound = true;
                }
            }

            // 2. Inspect Local Map Pack 3-Pack on Page 1
            if ($page === 0 && !empty($data['local_results']['places'])) {
                foreach ($data['local_results']['places'] as $pIdx => $place) {
                    $placeWeb = $place['links']['website'] ?? '';
                    if ($this->isDomainMatch($placeWeb, $cleanTarget)) {
                        $localPackRank = $place['position'] ?? ($pIdx + 1);
                        break;
                    }
                }
            }

            // 3. Inspect Organic Results
            $organic = $data['organic_results'] ?? [];
            if (empty($organic)) {
                break; // No further results returned
            }

            foreach ($organic as $itemIndex => $item) {
                $currentRank = $start + ($itemIndex + 1);
                $itemUrl = $item['link'] ?? '';

                if ($this->isDomainMatch($itemUrl, $cleanTarget)) {
                    return [
                        'status'           => 'ranked',
                        'rank'             => $currentRank,
                        'page'             => $page + 1,
                        'title'            => $item['title'] ?? '',
                        'url'              => $itemUrl,
                        'snippet'          => $item['snippet'] ?? '',
                        'featured_snippet' => $featuredSnippetFound,
                        'local_pack_rank'  => $localPackRank,
                    ];
                }
            }
        }

        return [
            'status'           => 'unranked',
            'rank'             => null,
            'max_depth'        => $maxDepth,
            'featured_snippet' => $featuredSnippetFound,
            'local_pack_rank'  => $localPackRank,
        ];
    }

    /**
     * Resolves canonical location recommendations for user input.
     */
    public function getCanonicalLocations(string $query, int $limit = 5): array
    {
        $response = Http::timeout(10)->get($this->locationUrl, [
            'q'     => $query,
            'limit' => $limit,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Retrieves account balance and search credits.
     */
    public function getAccountQuota(): array
    {
        $response = Http::timeout(15)->get($this->accountUrl, [
            'api_key' => $this->apiKey,
        ]);

        return $response->json() ?? [];
    }

    /**
     * Strips protocol, port, and leading www from hostnames.
     */
    protected function normalizeDomain(string $urlOrDomain): string
    {
        $parsed = parse_url(trim($urlOrDomain), PHP_URL_HOST);
        $host = $parsed ?: trim($urlOrDomain);
        return strtolower(preg_replace('/^www\./i', '', trim($host, '/')));
    }

    /**
     * Determines whether an organic result URL matches the target domain.
     */
    protected function isDomainMatch(string $resultUrl, string $targetNormalized): bool
    {
        if (empty($resultUrl) || empty($targetNormalized)) {
            return false;
        }

        $resultHost = $this->normalizeDomain($resultUrl);
        return $resultHost === $targetNormalized || str_ends_with($resultHost, '.' . $targetNormalized);
    }
}
