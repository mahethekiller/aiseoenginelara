<?php

namespace App\Services;

use App\Models\KeywordRankCheck;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KeywordRankCheckerService
{
    protected SerpApiClientService $serpClient;

    public function __construct(SerpApiClientService $serpClient)
    {
        $this->serpClient = $serpClient;
    }

    /**
     * Normalize domain string for accurate hostname comparison
     */
    public function normalizeDomain(string $input): string
    {
        $domain = trim($input);
        // Strip protocol
        $domain = preg_replace('#^https?://#i', '', $domain);
        // Strip paths & query params if domain mode
        $parts = explode('/', $domain);
        $domain = $parts[0];
        // Strip port if any
        $portParts = explode(':', $domain);
        $domain = $portParts[0];
        // Strip www. prefix
        $domain = preg_replace('#^www\.#i', '', $domain);

        return strtolower(trim($domain));
    }

    /**
     * Normalize URL for exact matching
     */
    public function normalizeUrl(string $url): string
    {
        $url = trim($url);
        // Ensure protocol for parse_url
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }
        $parsed = parse_url($url);
        $host = preg_replace('#^www\.#i', '', strtolower($parsed['host'] ?? ''));
        $path = rtrim($parsed['path'] ?? '', '/');
        $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';

        return $host.$path.$query;
    }

    /**
     * Execute a rank check for a single keyword
     */
    public function checkKeyword(
        string $keyword,
        string $targetDomain,
        array $options = [],
        ?int $userId = null,
        ?int $clientId = null,
        ?string $batchId = null
    ): array {
        $keyword = trim($keyword);
        $cleanTargetDomain = $this->normalizeDomain($targetDomain);
        $matchType = ($options['match_type'] ?? 'domain') === 'exact_url' ? 'exact_url' : 'domain';
        $targetUrl = ! empty($options['target_url']) ? trim($options['target_url']) : null;
        $country = strtolower(trim($options['country'] ?? 'us'));
        $location = ! empty($options['location']) ? trim($options['location']) : null;
        if (! empty($location)) {
            $location = $this->serpClient->canonicalizeLocation($location, $country) ?: $location;
        }
        $language = strtolower(trim($options['language'] ?? 'en'));
        $device = strtolower($options['device'] ?? 'desktop') === 'mobile' ? 'mobile' : 'desktop';
        $forceRefresh = (bool) ($options['force_refresh'] ?? false);

        // Fetch Top 50 Google SERP via SerpApi
        $serpParams = [
            'q' => $keyword,
            'gl' => $country,
            'hl' => $language,
            'device' => $device,
        ];
        if (! empty($location)) {
            $serpParams['location'] = $location;
        }

        $serpResult = $this->serpClient->searchGoogle($serpParams, $forceRefresh);

        // Scan organic results (Top 50)
        $organicResults = $serpResult['organic_results'] ?? [];
        $featuredSnippet = $serpResult['answer_box'] ?? ($serpResult['featured_snippet'] ?? null);

        $matchedPosition = null;
        $matchedUrl = null;
        $matchedTitle = null;
        $matchedSnippet = null;
        $matchedFeatures = [];

        // 1. Check if Answer Box / Featured Snippet belongs to target domain
        if (! empty($featuredSnippet) && ! empty($featuredSnippet['link'])) {
            $featLink = $featuredSnippet['link'];
            if ($this->isMatch($featLink, $cleanTargetDomain, $targetUrl, $matchType)) {
                $matchedPosition = 1;
                $matchedUrl = $featLink;
                $matchedTitle = $featuredSnippet['title'] ?? 'Featured Snippet';
                $matchedSnippet = $featuredSnippet['snippet'] ?? ($featuredSnippet['answer'] ?? null);
                $matchedFeatures[] = 'featured_snippet';
            }
        }

        // 2. Scan Organic Results list (Page 1)
        foreach ($organicResults as $item) {
            $pos = (int) ($item['position'] ?? 0);
            if ($pos > 50 || $pos < 1) {
                continue;
            }

            $link = $item['link'] ?? '';
            if (empty($link)) {
                continue;
            }

            if ($this->isMatch($link, $cleanTargetDomain, $targetUrl, $matchType)) {
                if ($matchedPosition === null || $pos < $matchedPosition) {
                    $matchedPosition = $pos;
                    $matchedUrl = $link;
                    $matchedTitle = $item['title'] ?? null;
                    $matchedSnippet = $item['snippet'] ?? null;

                    if (! empty($item['sitelinks'])) {
                        $matchedFeatures[] = 'sitelinks';
                    }
                    if (! empty($item['rich_snippet'])) {
                        $matchedFeatures[] = 'rich_snippet';
                    }
                    break;
                }
            }
        }

        // Multi-page crawl: If not found on Page 1, crawl Pages 2 to 5 (up to rank 50)
        if ($matchedPosition === null && ! empty($serpResult['pagination']['other_pages'])) {
            $otherPages = $serpResult['pagination']['other_pages'];
            $maxPageToCheck = min(5, count($otherPages) + 1);
            $runningRank = count($organicResults);

            for ($page = 2; $page <= $maxPageToCheck; $page++) {
                $startOffset = ($page - 1) * 10;
                $pageParams = array_merge($serpParams, ['start' => $startOffset]);

                try {
                    $nextPageResult = $this->serpClient->searchGoogle($pageParams, $forceRefresh);
                    $nextOrganic = $nextPageResult['organic_results'] ?? [];

                    foreach ($nextOrganic as $idx => $item) {
                        $rawPos = (int) ($item['position'] ?? 0);
                        // True sequential position across pages: use actual running organic result count
                        $pos = ($rawPos > $startOffset) ? $rawPos : ($runningRank + $idx + 1);
                        if ($pos > 50) {
                            break;
                        }

                        $link = $item['link'] ?? '';
                        if (! empty($link) && $this->isMatch($link, $cleanTargetDomain, $targetUrl, $matchType)) {
                            $matchedPosition = $pos;
                            $matchedUrl = $link;
                            $matchedTitle = $item['title'] ?? null;
                            $matchedSnippet = $item['snippet'] ?? null;
                            break 2; // Found! Break out of pagination loop
                        }
                    }

                    $runningRank += count($nextOrganic);
                } catch (\Throwable $e) {
                    Log::debug("Pagination crawl page {$page} failed: ".$e->getMessage());
                    break;
                }
            }
        }

        // Detect other SERP features present on page
        if (! empty($serpResult['related_questions'])) {
            $matchedFeatures[] = 'people_also_ask';
        }
        if (! empty($serpResult['knowledge_graph'])) {
            $matchedFeatures[] = 'knowledge_graph';
        }
        $matchedFeatures = array_values(array_unique($matchedFeatures));

        // 3. Extract Top 10 Competitors Landscape
        $topCompetitors = [];
        $compCount = 0;
        foreach ($organicResults as $comp) {
            $compPos = (int) ($comp['position'] ?? 0);
            if ($compPos < 1) {
                continue;
            }
            $compLink = $comp['link'] ?? '';
            $compDomain = parse_url($compLink, PHP_URL_HOST) ?? '';
            $compDomain = preg_replace('#^www\.#i', '', $compDomain);

            $topCompetitors[] = [
                'position' => $compPos,
                'title' => $comp['title'] ?? 'Untitled',
                'link' => $compLink,
                'domain' => $compDomain,
                'snippet' => $comp['snippet'] ?? '',
                'is_target' => $this->isMatch($compLink, $cleanTargetDomain, $targetUrl, $matchType),
            ];

            $compCount++;
            if ($compCount >= 10) {
                break;
            }
        }

        // 4. Query Previous Check for Rank Delta
        $previousCheck = KeywordRankCheck::where('target_domain', $cleanTargetDomain)
            ->where('keyword', $keyword)
            ->where('country', $country)
            ->where('device', $device)
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->latest('id')
            ->first();

        $previousPosition = $previousCheck?->position;
        $rankChange = null;

        if ($matchedPosition !== null) {
            if ($previousPosition !== null) {
                $rankChange = $previousPosition - $matchedPosition; // +3 = gained 3 ranks
            } else {
                $rankChange = 999; // Flag for NEW entry into Top 50
            }
        } elseif ($previousPosition !== null) {
            $rankChange = -999; // Dropped out of Top 50
        }

        $isRanked = ($matchedPosition !== null && $matchedPosition <= 50);

        // 5. Store / Record Check in Database
        $record = KeywordRankCheck::create([
            'user_id' => $userId,
            'client_id' => $clientId,
            'batch_id' => $batchId,
            'keyword' => $keyword,
            'target_domain' => $cleanTargetDomain,
            'target_url' => $targetUrl,
            'match_type' => $matchType,
            'country' => $country,
            'location' => $location,
            'language' => $language,
            'device' => $device,
            'position' => $isRanked ? $matchedPosition : null,
            'is_ranked' => $isRanked,
            'ranking_url' => $matchedUrl,
            'ranking_title' => $matchedTitle,
            'ranking_snippet' => $matchedSnippet,
            'previous_position' => $previousPosition,
            'rank_change' => $rankChange,
            'serp_features' => $matchedFeatures,
            'top_competitors' => $topCompetitors,
            'serpapi_search_url' => $serpResult['search_metadata']['json_endpoint'] ?? null,
            'checked_at' => now(),
        ]);

        return [
            'id' => $record->id,
            'batch_id' => $record->batch_id,
            'keyword' => $keyword,
            'target_domain' => $cleanTargetDomain,
            'position' => $record->position,
            'is_ranked' => $record->is_ranked,
            'display_rank' => $isRanked ? '#'.$matchedPosition : '> 50 (Unranked)',
            'ranking_url' => $record->ranking_url,
            'ranking_title' => $record->ranking_title,
            'ranking_snippet' => $record->ranking_snippet,
            'previous_position' => $record->previous_position,
            'rank_change' => $record->rank_change,
            'country' => strtoupper($country),
            'device' => $device,
            'location' => $location,
            'is_cached' => $serpResult['is_cached'] ?? false,
            'top_competitors' => $topCompetitors,
            'serp_features' => $matchedFeatures,
            'checked_at' => $record->checked_at->diffForHumans(),
        ];
    }

    /**
     * Batch check multiple keywords sequentially
     */
    public function checkBatch(
        array $keywords,
        string $targetDomain,
        array $options = [],
        ?int $userId = null,
        ?int $clientId = null
    ): array {
        $results = [];
        $cleanKeywords = [];

        foreach ($keywords as $kw) {
            $trimmed = trim($kw);
            if (! empty($trimmed) && ! in_array($trimmed, $cleanKeywords)) {
                $cleanKeywords[] = $trimmed;
            }
        }

        // Limit batch to maximum 25 keywords per run to prevent excessive waits
        $cleanKeywords = array_slice($cleanKeywords, 0, 25);
        $batchId = 'B-' . date('Ymd-His') . '-' . Str::lower(Str::random(4));

        foreach ($cleanKeywords as $index => $keyword) {
            try {
                $res = $this->checkKeyword($keyword, $targetDomain, $options, $userId, $clientId, $batchId);
                $results[] = $res;

                // Brief 150ms throttle pause between live calls
                if (empty($res['is_cached']) && $index < count($cleanKeywords) - 1) {
                    usleep(150000);
                }
            } catch (\Throwable $e) {
                Log::error("Keyword check failed for '{$keyword}': ".$e->getMessage());
                $results[] = [
                    'keyword' => $keyword,
                    'target_domain' => $targetDomain,
                    'error' => $e->getMessage(),
                    'is_ranked' => false,
                    'position' => null,
                    'display_rank' => 'Error',
                ];
            }
        }

        return $results;
    }

    /**
     * Check if a found SERP link matches the target criteria
     */
    protected function isMatch(string $link, string $cleanTargetDomain, ?string $targetUrl, string $matchType): bool
    {
        if (empty($link)) {
            return false;
        }

        if ($matchType === 'exact_url' && ! empty($targetUrl)) {
            return $this->normalizeUrl($link) === $this->normalizeUrl($targetUrl);
        }

        // Domain-wide matching
        $linkHost = parse_url($link, PHP_URL_HOST);
        if (empty($linkHost)) {
            return false;
        }
        $cleanLinkHost = preg_replace('#^www\.#i', '', strtolower($linkHost));

        return $cleanLinkHost === $cleanTargetDomain || str_ends_with($cleanLinkHost, '.'.$cleanTargetDomain);
    }
}
