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
            $location = preg_replace('/\s*,\s*/', ', ', $location);
            if (substr_count($location, ',') < 2) {
                $location = $this->serpClient->canonicalizeLocation($location, $country) ?: $location;
            }
        }
        $language = strtolower(trim($options['language'] ?? 'en'));
        $device = strtolower($options['device'] ?? 'desktop') === 'mobile' ? 'mobile' : 'desktop';
        $forceRefresh = isset($options['force_refresh']) ? (bool) $options['force_refresh'] : true;
        $depth = max(10, min(100, (int) ($options['depth'] ?? 50)));

        // Fetch Google SERP via SerpApi
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

        // Scan organic results (Page 1)
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

        // 2. Check if Google Local Map 3-Pack (local_results) belongs to target domain
        $localPlaces = [];
        if (! empty($serpResult['local_results'])) {
            if (isset($serpResult['local_results']['places']) && is_array($serpResult['local_results']['places'])) {
                $localPlaces = $serpResult['local_results']['places'];
            } elseif (is_array($serpResult['local_results'])) {
                $localPlaces = $serpResult['local_results'];
            }
        }

        if (! empty($localPlaces)) {
            $matchedFeatures[] = 'local_pack';
            foreach ($localPlaces as $place) {
                $placeLink = $place['links']['website'] ?? ($place['website'] ?? ($place['link'] ?? ''));
                if (empty($placeLink)) {
                    continue;
                }

                if ($this->isMatch($placeLink, $cleanTargetDomain, $targetUrl, $matchType)) {
                    $placePos = (int) ($place['position'] ?? 0);
                    if ($placePos > 0 && ($matchedPosition === null || $placePos < $matchedPosition)) {
                        $matchedPosition = $placePos;
                        $matchedUrl = $placeLink;
                        $matchedTitle = ($place['title'] ?? 'Local Business') . ' (Google Map 3-Pack)';
                        $type = $place['type'] ?? 'Local Business';
                        $address = $place['address'] ?? '';
                        $rating = ! empty($place['rating']) ? ' (' . $place['rating'] . '★ · ' . ($place['reviews'] ?? 0) . ' reviews)' : '';
                        $matchedSnippet = trim("{$type} · {$address}{$rating}");
                    }
                }
            }
        }

        // 3. Scan Organic Results list (Page 1)
        foreach ($organicResults as $item) {
            $pos = (int) ($item['position'] ?? 0);
            if ($pos > $depth || $pos < 1) {
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

        // Multi-page crawl: If not found on Page 1, crawl subsequent pages up to requested depth
        if ($matchedPosition === null && ! empty($serpResult['pagination']['other_pages'])) {
            $otherPages = $serpResult['pagination']['other_pages'];
            $maxPagesByDepth = (int) ceil($depth / 10);
            $maxPageToCheck = min($maxPagesByDepth, count($otherPages) + 1);
            $runningRank = count($organicResults);

            for ($page = 2; $page <= $maxPageToCheck; $page++) {
                $startOffset = ($page - 1) * 10;
                $pageParams = array_merge($serpParams, ['start' => $startOffset]);

                try {
                    $nextPageResult = $this->serpClient->searchGoogle($pageParams, $forceRefresh);
                    $nextOrganic = $nextPageResult['organic_results'] ?? [];

                    // Short-circuit: If Google returned 0 results, no further pages exist
                    if (empty($nextOrganic)) {
                        break;
                    }

                    foreach ($nextOrganic as $idx => $item) {
                        $rawPos = (int) ($item['position'] ?? 0);
                        // True sequential position across pages
                        $pos = ($rawPos > $startOffset) ? $rawPos : ($runningRank + $idx + 1);
                        if ($pos > $depth) {
                            break 2;
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

                    // Short-circuit: If this page has fewer than 5 results, Google index is exhausted
                    if (count($nextOrganic) < 5) {
                        break;
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

        // 4. Extract Top 10 Competitors Landscape (including Google 3-Pack places)
        $topCompetitors = [];
        $compCount = 0;

        if (! empty($localPlaces)) {
            foreach ($localPlaces as $place) {
                if ($compCount >= 10) {
                    break;
                }
                $placeLink = $place['links']['website'] ?? ($place['website'] ?? ($place['link'] ?? ''));
                $compDomain = ! empty($placeLink) ? (parse_url($placeLink, PHP_URL_HOST) ?? '') : '';
                $compDomain = preg_replace('#^www\.#i', '', $compDomain);
                $isTarget = ! empty($placeLink) ? $this->isMatch($placeLink, $cleanTargetDomain, $targetUrl, $matchType) : false;

                $placePos = (int) ($place['position'] ?? 0);
                $snippet = trim(($place['type'] ?? 'Local Business') . ' · ' . ($place['address'] ?? ''));
                if (! empty($place['rating'])) {
                    $snippet .= ' · ' . $place['rating'] . '★ (' . ($place['reviews'] ?? 0) . ' reviews)';
                }

                $topCompetitors[] = [
                    'position' => $placePos ?: ($compCount + 1),
                    'title' => ($place['title'] ?? 'Local Business') . ' [Google 3-Pack]',
                    'link' => $placeLink ?: ($place['place_id_search'] ?? ''),
                    'domain' => $compDomain ?: 'google.com/maps',
                    'snippet' => $snippet,
                    'is_target' => $isTarget,
                    'is_local_pack' => true,
                ];
                $compCount++;
            }
        }

        foreach ($organicResults as $comp) {
            if ($compCount >= 10) {
                break;
            }
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
        @set_time_limit(300);

        $results = [];
        $cleanKeywords = [];
        $seenLower = [];

        foreach ($keywords as $kw) {
            $trimmed = trim($kw);
            $lower = mb_strtolower($trimmed);
            if (! empty($trimmed) && ! isset($seenLower[$lower])) {
                $cleanKeywords[] = $trimmed;
                $seenLower[$lower] = true;
            }
        }

        // Limit batch to maximum 25 keywords per run to prevent excessive waits
        $cleanKeywords = array_slice($cleanKeywords, 0, 25);
        $batchId = 'B-' . date('Ymd-His') . '-' . Str::lower(Str::random(4));

        // Pre-resolve canonical location once for the entire batch to eliminate repeated geocoding calls
        if (! empty($options['location'])) {
            $rawLoc = preg_replace('/\s*,\s*/', ', ', trim($options['location']));
            $country = strtolower(trim($options['country'] ?? 'us'));
            if (substr_count($rawLoc, ',') < 2) {
                $canonicalLoc = $this->serpClient->canonicalizeLocation($rawLoc, $country);
                $options['location'] = $canonicalLoc ?: $rawLoc;
            } else {
                $options['location'] = $rawLoc;
            }
        }

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

    /**
     * Parse and extract keywords and optional per-keyword overrides from uploaded CSV
     */
    public function parseKeywordsFromCsv(\Illuminate\Http\UploadedFile|string $file, array $defaultOptions = []): array
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new \InvalidArgumentException('Unable to read CSV file.');
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \InvalidArgumentException('Failed to open CSV file stream.');
        }

        $firstLine = fgets($handle);
        rewind($handle);

        // Strip UTF-8 BOM if present
        if ($firstLine !== false && str_starts_with($firstLine, "\xEF\xBB\xBF")) {
            fseek($handle, 3);
        }

        $headers = [];
        $rows = [];
        $isFirstRow = true;

        while (($row = fgetcsv($handle, 2048, ',')) !== false) {
            if (empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                continue; // Skip blank lines
            }

            if ($isFirstRow) {
                $isFirstRow = false;
                $lowered = array_map(fn ($h) => strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', (string) $h))), $row);
                // Check if this row is a header row
                if (in_array('keyword', $lowered) || in_array('keywords', $lowered) || in_array('query', $lowered)) {
                    $headers = $lowered;
                    continue;
                }
                // If not header, treat as data row
            }

            if (! empty($headers)) {
                $named = [];
                foreach ($headers as $idx => $colName) {
                    $named[$colName] = isset($row[$idx]) ? trim((string) $row[$idx]) : null;
                }
                $kw = $named['keyword'] ?? ($named['keywords'] ?? ($named['query'] ?? null));
                if (! empty($kw)) {
                    $rows[] = [
                        'keyword' => $kw,
                        'target_domain' => ! empty($named['target_domain']) ? $named['target_domain'] : ($named['domain'] ?? ($defaultOptions['target_domain'] ?? null)),
                        'location' => ! empty($named['location']) ? $named['location'] : ($defaultOptions['location'] ?? null),
                        'target_url' => ! empty($named['target_url']) ? $named['target_url'] : ($named['url'] ?? ($defaultOptions['target_url'] ?? null)),
                        'match_type' => ! empty($named['match_type']) ? $named['match_type'] : ($defaultOptions['match_type'] ?? 'domain'),
                    ];
                }
            } else {
                // Single column or raw keyword list without headers
                $kw = trim((string) ($row[0] ?? ''));
                if (! empty($kw)) {
                    $rows[] = [
                        'keyword' => $kw,
                        'target_domain' => ! empty($row[1]) ? trim((string) $row[1]) : ($defaultOptions['target_domain'] ?? null),
                        'location' => ! empty($row[2]) ? trim((string) $row[2]) : ($defaultOptions['location'] ?? null),
                        'target_url' => ! empty($row[3]) ? trim((string) $row[3]) : ($defaultOptions['target_url'] ?? null),
                        'match_type' => $defaultOptions['match_type'] ?? 'domain',
                    ];
                }
            }
        }

        fclose($handle);

        // Case-insensitive deduplication by keyword
        $seen = [];
        $deduped = [];
        foreach ($rows as $item) {
            $key = mb_strtolower(trim((string) $item['keyword']));
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $deduped[] = $item;
            }
        }

        return $deduped;
    }
}
