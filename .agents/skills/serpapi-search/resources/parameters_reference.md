# SerpApi Google Search API - Exhaustive Parameters Reference

Complete specification of every parameter supported by the `https://serpapi.com/search.json` (Google Search Engine) endpoint.

---

## 1. Primary Query Parameters

| Parameter | Type | Required | Default | Description & Values |
| :--- | :--- | :--- | :--- | :--- |
| `q` | `string` | **Yes** | — | Search query. Supports Google search operators (`site:`, `inurl:`, `intitle:`, `filetype:`, quotes `""`, minus `-`, OR `\|`). |
| `api_key` | `string` | **Yes** | — | Your SerpApi secret account API key. Keep private and configure via environment variables (`SERPAPI_API_KEY`). |
| `engine` | `string` | Optional | `google` | Search engine provider. Set to `google` for Google Web Search. (Other options: `google_maps`, `google_jobs`, `google_scholar`, `google_news`, `bing`, `duckduckgo`, `baidu`, `yahoo`, `yandex`, `ebay`, `youtube`, `walmart`, `home_depot`). |

---

## 2. Localization & Geotargeting Parameters

| Parameter | Type | Required | Default | Description & Production Behavior |
| :--- | :--- | :--- | :--- | :--- |
| `location` | `string` | Optional | — | Canonical geographic location string (e.g. `"Austin, Texas, United States"` or `"Ahmedabad, Gujarat, India"`). <br>**CRITICAL**: Must have a single space following every comma. Lack of whitespace breaks SerpApi's mapping to Google's `uule` geocode algorithm and falls back to country IP routing. Validate via `/locations.json`. |
| `uule` | `string` | Optional | — | Google-encoded geographical location string (e.g. `w+CAIQICIaQXVzdGluLFRleGFzLFVuaXRlZCBTdGF0ZXM`). When specified, SerpApi bypasses canonical location resolution and forwards the exact raw geocode to Google. |
| `google_domain` | `string` | Optional | `google.com` | Google domain edition to emulate (e.g. `google.co.in`, `google.co.uk`, `google.com.au`, `google.de`, `google.fr`, `google.ca`). |
| `gl` | `string` | Optional | `us` | Two-letter country code for Google search edition (e.g. `in` for India, `us` for USA, `uk` for Great Britain, `ca` for Canada, `ae` for UAE, `de` for Germany). |
| `hl` | `string` | Optional | `en` | Two-letter language code for Google user interface (e.g. `en` for English, `hi` for Hindi, `es` for Spanish, `fr` for French, `de` for German, `ja` for Japanese). Can also accept region subtags like `en-gb` or `es-419`. |
| `cr` | `string` | Optional | — | Country restriction filter. Limits search results to documents originating from specified countries. Format: `country{two-letter uppercase code}` separated by `\|` (e.g. `countryIN\|countryUS`). |
| `lr` | `string` | Optional | — | Language restriction filter. Limits search results to documents written in specified languages. Format: `lang_{two-letter lowercase code}` separated by `\|` (e.g. `lang_en\|lang_hi`). |

---

## 3. Search Type & Vertical Modifiers (`tbm`)

The `tbm` (to be matched) parameter routes the query into specialized Google search verticals:

| `tbm` Value | Vertical Target | Documentation Link / Notes |
| :--- | :--- | :--- |
| *(omitted)* | Regular Organic Search | Default Google Web Search SERP. |
| `isch` | Google Images Search | Extracts high-res images, image thumbnails, source links. |
| `lcl` | Google Local Search | Full local business directory listings with map coordinates. |
| `vid` | Google Videos Search | Indexed videos across YouTube, Vimeo, and web publishers. |
| `nws` | Google News Search | Live news publications, article timestamps, publication sources. |
| `shop` | Google Shopping Search | Merchant listings, prices, discount badges, store ratings. |
| `pts` | Google Patents Search | Patent claims, grant numbers, filing dates, abstract citations. |
| `bks` | Google Books Search | Google Books index, authors, publishers, publication dates. |

---

## 4. Advanced Filters & Query Controls

| Parameter | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `tbs` | `string` | — | (to be searched) Advanced search parameter string: <br>• **Time filtering**: `qdr:h` (past hour), `qdr:d` (past 24h), `qdr:w` (past week), `qdr:m` (past month), `qdr:y` (past year). <br>• **Custom date range**: `cdr:1,cd_min:MM/DD/YYYY,cd_max:MM/DD/YYYY`. <br>• **Verbatim search**: `li:1` (forces literal phrase match without Google synonym expansion). |
| `safe` | `string` | `off` (blur) | SafeSearch adult content filter. Values: `active` (strictly exclude adult content) or `off` (blur explicit content). |
| `nfpr` | `integer` | `0` | Exclusion of auto-corrected spelling results. Set to `1` to prevent Google from returning results for "Did you mean: ...", forcing exact query spelling. |
| `filter` | `integer` | `1` | Controls Google's duplicate and omitted results filter. Set to `0` to include similar/omitted results (shows full raw index). Set to `1` (default) to hide duplicate listings. |

---

## 5. Pagination & Offset Parameters

| Parameter | Type | Default | Description & Behavior |
| :--- | :--- | :--- | :--- |
| `start` | `integer` | `0` | Result offset. Skips the specified number of results. <br>`0` = Page 1 (Ranks 1–10) <br>`10` = Page 2 (Ranks 11–20) <br>`20` = Page 3 (Ranks 21–30) <br>`30` = Page 4 (Ranks 31–40) <br>`40` = Page 5 (Ranks 41–50) |
| `num` | `integer` | `10` | Number of results requested. **Note**: Google has deprecated `num=100` on most localized, mobile, and desktop SERPs. Requesting `num=100` often yields only 10 results. Always paginate using `start` instead. |

---

## 6. SerpApi Infrastructure & Execution Parameters

| Parameter | Type | Default | Description & Rules |
| :--- | :--- | :--- | :--- |
| `device` | `string` | `desktop` | Emulated device user agent and viewport. <br>• `desktop`: Standard PC Chrome browser. <br>• `mobile`: Smartphone mobile web browser (critical for mobile rank tracking). <br>• `tablet`: Tablet browser (emulates iPad). |
| `no_cache` | `boolean` | `false` | When `true`, forces SerpApi to bypass the 1-hour cache and scrape a fresh live result directly from Google. Cached searches are free and do not deduct account credits. <br>**Rule**: Cannot be used together with `async=true`. |
| `async` | `boolean` | `false` | Asynchronous submission mode. When `true`, SerpApi immediately returns an initial response with `search_metadata.status: "Processing"` and `search_metadata.id`. Poll `/searches/{id}.json` to obtain the final payload. <br>**Rule**: Cannot be used together with `no_cache=true` or on Ludicrous Speed plans. |
| `output` | `string` | `json` | Desired output serialization: <br>• `json`: Standard parsed JSON object (default). <br>• `html`: Raw unmodified Google SERP HTML document. <br>• `md`: Markdown-formatted output optimized for LLM prompting and AI agents. |
| `json_restrictor` | `string` | — | Field-level response pruning. Restricts JSON response payload to specific root keys (e.g. `json_restrictor=organic_results` or `json_restrictor=local_results,search_information`). Dramatically reduces response payload size and HTTP transfer latency. |
| `zero_trace` | `boolean` | `false` | Enterprise privacy mode. When `true`, SerpApi does not retain search queries, HTML snapshots, or parameter logs on its infrastructure. |

---

## 7. Auxiliary Endpoints

### Canonical Location Autocomplete
```http
GET https://serpapi.com/locations.json?q={search_term}&limit={limit}
```
**Response Item**:
```json
{
  "id": "5857e4c27fa01f5c093a02bb",
  "google_id": 1007788,
  "google_parent_id": 9075254,
  "name": "Ahmedabad, Gujarat, India",
  "canonical_name": "Ahmedabad,Gujarat,India",
  "country_code": "IN",
  "target_type": "City",
  "reach": 5570000,
  "gps": [23.022505, 72.571362]
}
```

### Account Balance & Quota
```http
GET https://serpapi.com/account?api_key={api_key}
```
**Response**:
```json
{
  "account_id": "usr_abc123",
  "api_key": "your_api_key",
  "account_email": "admin@example.com",
  "plan_name": "Developer (5,000 searches/month)",
  "plan_searches_left": 4820,
  "total_searches_left": 4820,
  "extra_credits": 0,
  "this_month_usage": 180,
  "searches_per_hour_limit": 1000
}
```

### Asynchronous Result Polling
```http
GET https://serpapi.com/searches/{search_id}.json?api_key={api_key}
```
When `status === "Success"`, the complete payload is returned.
