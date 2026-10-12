---
name: serpapi-search
description: "Queries and parses Google Search Engine Results Pages (SERP) using SerpApi's Google Search API. Use when building rank trackers, performing local SEO auditing, scraping organic rankings, extracting local map pack places, or analyzing competitor search visibility. Triggers on 'serpapi', 'google search api', 'keyword rank check', 'rank tracker', 'scrape serps', 'google results api', 'local serp tracking'."
---

# SerpApi Google Search API Integration

Production guide and engineering blueprint for executing Google Search Engine Results Pages (SERP) queries, scraping localized organic rankings, extracting SERP features, and building SEO tracking tools using SerpApi.

---

## 1. When to Use This Skill

- **Keyword Rank Tracking**: Discover exact rank positions (1–100) for a target domain or specific landing page.
- **Local SEO & Maps Pack**: Extract Google 3-Pack businesses (`local_results.places`), ratings, review counts, and GPS coordinates.
- **SERP Features Auditing**: Detect Featured Snippets (`answer_box`), People Also Ask (`related_questions`), Knowledge Graphs, and Reddit/Forum discussions.
- **Device Discrepancy Audits**: Compare SERP layout and ranking shifts between `desktop` and `mobile` crawlers.
- **Competitor Intelligence**: Crawl top ranking competitors, titles, URLs, and rich snippets for target keywords.

---

## 2. API Endpoints & Request Routing

| Endpoint | Method | Purpose |
| :--- | :--- | :--- |
| `https://serpapi.com/search.json` | `GET` | Core search endpoint for Google SERP queries |
| `https://serpapi.com/locations.json` | `GET` | Geocoding autocomplete and canonical location validator |
| `https://serpapi.com/account` | `GET` | Account plan, credit usage, and hourly rate-limit status |
| `https://serpapi.com/searches/{id}.json` | `GET` | Retrieve search results for asynchronous queries (`async=true`) |

---

## 3. Core Workflow: Keyword Rank Tracking

Use this checklist when building or debugging SERP rank tracking routines:

- [ ] **1. Validate Credentials**: Ensure `SERPAPI_API_KEY` is present and active via `/account`.
- [ ] **2. Normalize Location**: Sanitize location string with single space after every comma (e.g. `"Ahmedabad, Gujarat, India"`).
- [ ] **3. Emulate Target Device**: Choose `desktop` (standard PC) or `mobile` (smartphone user agent).
- [ ] **4. Select Google Domain & Country**: Match `google_domain` (e.g. `google.co.in`) with `gl` (`in`) and `hl` (`en`).
- [ ] **5. Implement Multi-Page Pagination Loop**: Loop `start = 0, 10, 20, 30...` up to desired depth (Top 50/100). Do NOT use `num=100`.
- [ ] **6. Match Target Domain**: Normalize result URLs (strip `http://`, `https://`, `www.`, and trailing `/`) before comparing.
- [ ] **7. Extract SERP Features**: Check for Position 0 Featured Snippets and Local Maps Pack placements on Page 1 (`start=0`).
- [ ] **8. Handle Edge Cases**: Flag unranked keywords (>50 or >100) and log HTTP errors without halting the worker batch.

---

## 4. Critical Production Rules & Pitfalls

### Rule 1: Canonical Location Formatting (Comma-Space Requirement)
> [!IMPORTANT]
> Google maps geographic regions using the encoded `uule` parameter derived from `location`. If commas lack single trailing spaces (e.g. `"Ahmedabad,Gujarat,India"` instead of `"Ahmedabad, Gujarat, India"`), SerpApi cannot match Google's geocoding table and falls back to broader country-level IP routing, completely altering local rankings!
>
> **Enforce universal location sanitization:**
> ```php
> $normalizedLocation = preg_replace('/\s*,\s*/', ', ', trim($rawLocation));
> ```

### Rule 2: Multi-Page Pagination with `start` Offset (Do NOT use `num=100`)
Google has deprecated `num=100` on most localized desktop and mobile search indices. Requesting `num=100` frequently returns only 10 results. To check rankings down to Top 50 or Top 100:
- Query Page 1: `start=0` (Ranks 1–10).
- Query Page 2: `start=10` (Ranks 11–20).
- Query Page 3: `start=20` (Ranks 21–30).
- Query Page 4: `start=30` (Ranks 31–40).
- Query Page 5: `start=40` (Ranks 41–50).
- Absolute Rank Formula: `currentRank = $start + ($itemIndex + 1)`.

### Rule 3: High HTTP Timeout Configuration
SerpApi renders live Google pages through proxy networks and residential IP pools.
- **Set HTTP client timeout to 45–60 seconds**.
- Short timeouts (10–15s) will trigger intermittent `cURL error 28: Operation timed out` exceptions during peak traffic.
- Configure automatic retry on failure: `Http::timeout(60)->retry(2, 500)`.

### Rule 4: Domain Normalization Engine
Never perform strict string equality on raw URLs (`https://www.example.com/` vs `example.com`). Always extract host and strip `www`:
```php
function normalizeDomain(string $urlOrDomain): string {
    $host = parse_url(trim($urlOrDomain), PHP_URL_HOST) ?? trim($urlOrDomain);
    return strtolower(preg_replace('/^www\./i', '', trim($host, '/')));
}
```

---

## 5. Key Parameters Cheat Sheet

| Parameter | Type | Required | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `q` | string | **Yes** | — | Search query. Supports operators (`site:`, `intitle:`, `""`). |
| `api_key` | string | **Yes** | — | Secret SerpApi account token. |
| `location` | string | Optional | — | Canonical geographic location (e.g. `"Ahmedabad, Gujarat, India"`). |
| `google_domain` | string | Optional | `google.com` | Google top-level domain (e.g. `google.co.in`, `google.co.uk`). |
| `gl` | string | Optional | `us` | Country code for search edition (e.g. `in`, `us`, `uk`). |
| `hl` | string | Optional | `en` | Language interface code (e.g. `en`, `hi`, `es`). |
| `device` | string | Optional | `desktop` | Client device emulation: `desktop`, `mobile`, or `tablet`. |
| `start` | integer | Optional | `0` | Result offset (multiples of 10: `0`, `10`, `20`, `30`, `40`). |
| `tbm` | string | Optional | — | Vertical filter: `isch` (Images), `lcl` (Local), `nws` (News), `vid` (Videos), `shop` (Shopping). |
| `tbs` | string | Optional | — | Date/verbatim filters: `qdr:d` (24h), `qdr:w` (week), `qdr:m` (month), `li:1` (verbatim). |
| `no_cache` | boolean | Optional | `false` | Force fresh live scrape bypassing 1h cache. |
| `output` | string | Optional | `json` | Output format: `json` (standard), `html` (raw HTML), `md` (LLM markdown). |
| `json_restrictor`| string | Optional | — | Limit response keys (e.g. `organic_results,local_results`) to reduce latency. |

👉 *For the complete exhaustive parameter guide, see [parameters_reference.md](resources/parameters_reference.md).*

---

## 6. Parsing Common SERP Components

### 1. Organic Search Results (`organic_results`)
```php
$organic = $response['organic_results'] ?? [];
foreach ($organic as $index => $item) {
    $rank = $start + ($index + 1);
    $title = $item['title'] ?? '';
    $link = $item['link'] ?? '';
    $snippet = $item['snippet'] ?? '';
    $sitelinks = $item['sitelinks']['inline'] ?? [];
}
```

### 2. Local 3-Pack Map Results (`local_results.places`)
```php
$places = $response['local_results']['places'] ?? [];
foreach ($places as $place) {
    $businessName = $place['title'] ?? '';
    $rating = $place['rating'] ?? null;
    $reviews = $place['reviews'] ?? 0;
    $address = $place['address'] ?? '';
    $website = $place['links']['website'] ?? '';
    $gps = $place['gps_coordinates'] ?? []; // ['latitude' => ..., 'longitude' => ...]
}
```

### 3. Featured Snippet / Answer Box (`answer_box`)
Position 0 widget displayed above regular organic results:
```php
if (!empty($response['answer_box'])) {
    $snippetType = $response['answer_box']['type'] ?? '';
    $snippetTitle = $response['answer_box']['title'] ?? '';
    $snippetUrl = $response['answer_box']['link'] ?? '';
    $snippetText = $response['answer_box']['snippet'] ?? '';
}
```

👉 *For exhaustive JSON response schemas, see [response_schemas.md](resources/response_schemas.md).*

---

## 7. Production Laravel Rank Tracker Template

Here is the minimal production rank checker pattern:

```php
use Illuminate\Support\Facades\Http;

public function getTargetRank(string $keyword, string $targetDomain, string $location, int $maxDepth = 50): ?array
{
    // 1. Sanitize location spacing
    $cleanLocation = preg_replace('/\s*,\s*/', ', ', trim($location));
    $cleanTarget = strtolower(preg_replace('/^www\./i', '', trim($targetDomain)));
    $pageSize = 10;
    $maxPages = (int) ceil($maxDepth / $pageSize);

    for ($page = 0; $page < $maxPages; $page++) {
        $start = $page * $pageSize;

        $response = Http::timeout(60)->retry(2, 500)->get('https://serpapi.com/search.json', [
            'engine'        => 'google',
            'api_key'       => config('services.serpapi.key'),
            'q'             => $keyword,
            'location'      => $cleanLocation,
            'google_domain' => 'google.co.in',
            'gl'            => 'in',
            'hl'            => 'en',
            'device'        => 'desktop',
            'start'         => $start,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('SerpApi error: ' . $response->status());
        }

        $results = $response->json('organic_results') ?? [];
        if (empty($results)) {
            break;
        }

        foreach ($results as $idx => $item) {
            $itemHost = strtolower(parse_url($item['link'] ?? '', PHP_URL_HOST) ?? '');
            $itemHost = preg_replace('/^www\./i', '', $itemHost);

            if ($itemHost === $cleanTarget || str_ends_with($itemHost, '.' . $cleanTarget)) {
                return [
                    'rank'  => $start + ($idx + 1),
                    'page'  => $page + 1,
                    'url'   => $item['link'] ?? '',
                    'title' => $item['title'] ?? '',
                ];
            }
        }
    }

    return null; // Not found within maxDepth
}
```

👉 *For the full feature-complete service implementation with local pack and featured snippet detection, see [rank_tracker_service.php](examples/rank_tracker_service.php).*

---

## 8. Supplemental Resources & Deep Dives

- [Exhaustive Parameters Reference (Every Parameter Explained)](resources/parameters_reference.md)
- [Complete JSON Response Schemas & Samples](resources/response_schemas.md)
- [Production Rank Tracker Service Implementation (Laravel)](examples/rank_tracker_service.php)
