---
name: serpapi-search
description: "Queries and parses Google Search Engine Results Pages (SERP) using SerpApi's Google Search API. Use when building rank trackers, performing local SEO auditing, scraping organic rankings, extracting local map pack places, or analyzing competitor search visibility. Triggers on 'serpapi', 'google search api', 'keyword rank check', 'rank tracker', 'scrape serps', 'google results api', 'local serp tracking'."
---

# SerpApi Google Search API Integration

Production guide for querying, scraping, and parsing Google Search Engine Results Pages (SERP) via SerpApi.

## When to Use

- Building keyword rank tracking systems and SEO dashboards.
- Scraping localized organic Google search rankings (`desktop` or `mobile`).
- Extracting Google Map Pack / Local 3-Pack places (`local_results`).
- Detecting SERP features: Featured Snippets (`answer_box`), People Also Ask (`related_questions`), Knowledge Graph (`knowledge_graph`).
- Auditing competitor domains and landing page rankings for targeted search queries.

---

## Architecture & Endpoints

SerpApi translates live Google search queries into structured JSON responses via real-time browser rendering and proxy routing.

| Endpoint | Method | Purpose |
| :--- | :--- | :--- |
| `https://serpapi.com/search.json` | `GET` | Core search endpoint for Google SERP queries |
| `https://serpapi.com/locations.json` | `GET` | Canonical location search & validation |
| `https://serpapi.com/account` | `GET` | Check account subscription, plan limit, and remaining searches |

---

## Core Query Parameters

| Parameter | Type | Required | Description | Example |
| :--- | :--- | :--- | :--- | :--- |
| `engine` | string | Optional | Search engine selector (defaults to `google`). | `google` |
| `q` | string | **Yes** | Search keyword or query string. | `best emergency medicine specialist in Ahmedabad` |
| `api_key` | string | **Yes** | SerpApi secret API key. | `your_serpapi_key` |
| `location` | string | Optional | Canonical geographic location for local SERPs. | `Ahmedabad, Gujarat, India` |
| `gl` | string | Optional | Two-letter country code for Google search edition. | `in`, `us`, `gb` |
| `hl` | string | Optional | Two-letter language code for the search interface. | `en`, `hi`, `es` |
| `google_domain`| string | Optional | Specific Google top-level domain. | `google.co.in`, `google.com` |
| `device` | string | Optional | Client device emulation: `desktop`, `mobile`, or `tablet`. | `desktop` |
| `start` | integer| Optional | Pagination offset for search results (multiples of 10). | `0`, `10`, `20`, `30`, `40` |
| `num` | integer| Optional | Number of results per request (Note: often ignored by Google for localized queries). | `10` |
| `output` | string | Optional | Format of the response (`json` or `html`). | `json` |

---

## Critical Production Gotchas

### 1. Comma-Space Canonical Location Formatting
> [!IMPORTANT]
> Google maps geographic regions using the encoded `uule` parameter derived from `location`. If commas lack single trailing spaces (e.g. `"Ahmedabad,Gujarat,India"` instead of `"Ahmedabad, Gujarat, India"`), SerpApi cannot match Google's canonical canonical geocoding table and falls back to broader country-level IP routing.
>
> **Always sanitize location strings before dispatching queries:**
> ```php
> $normalizedLocation = preg_replace('/\s*,\s*/', ', ', trim($rawLocation));
> ```

### 2. Multi-Page Pagination with `start` Offset
Google has deprecated `num=100` on most localized desktop and mobile search indices. Requesting `num=100` frequently returns only 10 results. To check rankings down to Top 50 or Top 100:
- Query page 1 with `start=0` (positions 1–10).
- Query page 2 with `start=10` (positions 11–20).
- Query page 3 with `start=20` (positions 21–30), and so on.
- Maintain a running absolute position counter: `offset + result['position']`.

### 3. HTTP Client Timeout Configuration
SerpApi performs real-time browser rendering and proxy routing across residential pools.
- Standard timeout: Minimum **45 to 60 seconds**.
- Short timeouts (e.g. 15s) will produce sporadic `cURL error 28: Operation timed out` failures during high Google proxy traffic.

### 4. Domain & URL Normalization for Rank Checking
When checking if a client domain matches a SERP organic result, strip schemes, www prefixes, and trailing slashes:
```php
function normalizeDomain(string $url): string {
    $host = parse_url(trim($url), PHP_URL_HOST) ?? $url;
    return strtolower(preg_replace('/^www\./i', '', trim($host)));
}
```

---

## Response Structure & Parsing

A standard response from `https://serpapi.com/search.json` contains structured blocks:

### 1. Search Metadata & Parameters
```json
{
  "search_metadata": {
    "id": "673abc...",
    "status": "Success",
    "total_time_taken": 1.42
  },
  "search_parameters": {
    "engine": "google",
    "q": "emergency hospital",
    "location_requested": "Ahmedabad, Gujarat, India",
    "location_used": "Ahmedabad, Gujarat, India",
    "google_domain": "google.co.in",
    "hl": "en",
    "gl": "in",
    "device": "desktop"
  }
}
```

### 2. Organic Results (`organic_results`)
```json
{
  "organic_results": [
    {
      "position": 1,
      "title": "Emergency Medicine Services - Sterling Hospitals",
      "link": "https://www.sterlinghospitals.com/specialities/emergency-medicine",
      "displayed_link": "https://www.sterlinghospitals.com › specialities › emergency-medicine",
      "snippet": "Sterling Hospitals provides 24x7 emergency medical trauma care in Ahmedabad...",
      "sitelinks": {
        "inline": [
          { "title": "Contact Us", "link": "https://www.sterlinghospitals.com/contact" }
        ]
      }
    }
  ]
}
```

### 3. Local Pack Places (`local_results`)
When queries have local intent, Google embeds a 3-pack map module:
```json
{
  "local_results": {
    "places": [
      {
        "position": 1,
        "title": "Sterling Hospital Ahmedabad",
        "rating": 4.6,
        "reviews": 1250,
        "type": "Emergency room",
        "address": "Sterling Hospital Rd, Memnagar, Ahmedabad",
        "gps_coordinates": { "latitude": 23.0512, "longitude": 72.5312 },
        "links": {
          "website": "https://www.sterlinghospitals.com/"
        }
      }
    ]
  }
}
```

### 4. Featured Snippets / Answer Box (`answer_box`)
```json
{
  "answer_box": {
    "type": "organic_result",
    "title": "Emergency Medicine Definition",
    "link": "https://en.wikipedia.org/wiki/Emergency_medicine",
    "snippet": "Emergency medicine is the medical speciality concerned with the care of illnesses..."
  }
}
```

---

## Laravel Implementation Blueprint

### 1. Robust Service Wrapper (`SerpApiClientService.php`)

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SerpApiClientService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://serpapi.com/search.json';

    public function __construct()
    {
        $this->apiKey = config('services.serpapi.key', env('SERPAPI_API_KEY', ''));
    }

    /**
     * Search Google SERP with canonical location formatting and high timeout.
     */
    public function search(array $params): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('SerpApi API key is not configured.');
        }

        // Sanitize location spacing if present
        if (!empty($params['location'])) {
            $params['location'] = preg_replace('/\s*,\s*/', ', ', trim($params['location']));
        }

        $queryParams = array_merge([
            'engine'        => 'google',
            'api_key'       => $this->apiKey,
            'output'        => 'json',
            'device'        => 'desktop',
            'gl'            => 'in',
            'hl'            => 'en',
            'google_domain' => 'google.co.in',
            'start'         => 0,
        ], $params);

        $response = Http::timeout(60)
            ->retry(2, 500)
            ->get($this->baseUrl, $queryParams);

        if (!$response->successful()) {
            Log::error('SerpApi Search Failed', [
                'status' => $response->status(),
                'error'  => $response->json('error') ?? $response->body(),
            ]);
            throw new \RuntimeException('SerpApi request failed: ' . ($response->json('error') ?? $response->status()));
        }

        return $response->json() ?? [];
    }
}
```

### 2. Multi-Page Target Rank Checker Loop

```php
public function findTargetRank(string $query, string $targetDomain, string $location, int $maxDepth = 50): ?array
{
    $cleanTarget = strtolower(preg_replace('/^www\./i', '', trim($targetDomain)));
    $pageSize = 10;
    $maxPages = ceil($maxDepth / $pageSize);

    for ($page = 0; $page < $maxPages; $page++) {
        $start = $page * $pageSize;
        $data = $this->serpApi->search([
            'q'        => $query,
            'location' => $location,
            'start'    => $start,
        ]);

        $organic = $data['organic_results'] ?? [];
        if (empty($organic)) {
            break; // No more results returned
        }

        foreach ($organic as $index => $item) {
            $currentRank = $start + ($index + 1);
            $itemHost = strtolower(parse_url($item['link'] ?? '', PHP_URL_HOST) ?? '');
            $cleanHost = preg_replace('/^www\./i', '', $itemHost);

            if ($cleanHost === $cleanTarget || str_contains($cleanHost, $cleanTarget)) {
                return [
                    'rank'       => $currentRank,
                    'title'      => $item['title'] ?? '',
                    'url'        => $item['link'] ?? '',
                    'snippet'    => $item['snippet'] ?? '',
                    'found_on'   => "page " . ($page + 1),
                ];
            }
        }
    }

    return null; // Unranked within maxDepth
}
```

---

## Verifying Account Status

To monitor remaining search quota programmatically:

```php
$account = Http::timeout(15)->get('https://serpapi.com/account', [
    'api_key' => $apiKey,
]);

$searchesLeft = $account->json('total_searches_left');
$plan = $account->json('plan_name');
```
