# 🚀 Master AI Implementation Specification: AI SEO Engine & Content Intelligence Platform
### Tech Stack: Laravel 12 + Blade + DaisyUI 5 (Tailwind CSS 4) + jQuery / Vanilla JS (Zero React)

> **PROMPT DIRECTIVE FOR AI DEVELOPER:**
> You are an elite principal full-stack software engineer. Your task is to build the complete, production-ready, enterprise-grade **AI SEO Engine & Content Intelligence Platform** specified in this document from scratch.
> 
> **STRICT CONSTRAINTS:**
> 1. **Framework**: Laravel 12 (PHP 8.2+).
> 2. **Frontend UI**: Server-rendered Laravel **Blade templates** styled with **Tailwind CSS 4** and **DaisyUI 5** components (Dark Theme / Night palette matching `#080c14` / `#0b101b`).
> 3. **Client-Side Interactivity**: **jQuery / Vanilla JavaScript** with Fetch/AJAX. **DO NOT USE React, Vue, Inertia, or any SPA framework.**
> 4. **SCOPE DIRECTIVE (PHASE 1 vs. PHASE 2)**:
>    - **BUILD NOW (Phase 1 Core Operational Platform)**:
>      1. **SEO Blog Creator (`/blog-creator`)** — Primary application landing page & article workbench.
>      2. **Agency Clients Management (`/clients`)** — Client profiles, brand voice, sitemap crawler & cache, active client switcher.
>      3. **Prompt Blueprints Archetypes (`/prompt-templates`)** — Custom and system prompt template archetypes CRUD.
>      4. **Rewriter Studio (`/rewriter`)** — Layout-preserving website URL scraper & rewriter.
>      5. **Content Database (`/articles`)** — Historical articles archive, audits, download, WP publishing.
>      6. **Multi-Provider LLM Presets & Cost Reports (`/settings`)** — Gemini, OpenAI, Claude, DeepSeek, model sync, custom models, and USD ($) / INR (₹) token cost reports.
>      7. **User & Role Permissions (`/users`, `/permissions`)** — RBAC user administration.
>      8. **Authentication (`/login`, `/logout`)**.
>    - **DEFERRED FOR LATER (Phase 2 Expansion)**:
>      - The **10-Step AI SEO Pipeline (`/pipeline/*`)** is intentionally deferred to be created later. DO NOT implement the 10-step pipeline views, controller actions, or pipeline database migrations in Phase 1. Keep the blueprint documentation for when Phase 2 is requested.
> 5. **ZERO MOCK DATA / 100% LIVE EXECUTION POLICY**:
>    - **STRICTLY ZERO MOCK DATA**: Absolutely NO mock data, canned fake responses, dummy fallback articles, or simulated job records anywhere in controllers, services, seeders, or scripts.
>    - **100% Live Provider APIs**: All LLM operations (Gemini, OpenAI, Claude, DeepSeek) must hit the real live provider APIs. If an API key is unconfigured or invalid, or if an API call fails (quota exhausted, rate limited, network timeout), do NOT fall back to dummy/mock text. Immediately return an error code and display a user-facing error toast with the exact failure cause.
>    - **Real Web Scraping & Sitemaps**: Rewriter Studio and Sitemap Crawler must execute real HTTP Guzzle requests against the live target URLs and sitemaps.
>    - **Real WordPress REST API Publishing**: All WP publishing actions must authenticate and dispatch real REST requests to the client's configured WordPress endpoint.
>    - **Lean Database Seeders**: Seeders must ONLY seed fundamental system lookup data (roles/permissions, system prompt templates archetypes, article options dropdown values, and the initial Admin account). Never seed dummy articles, fake clients, or placeholder analytics.

---

## 1. Executive Summary & Phase 1 Scope

The **AI SEO Engine** in Phase 1 is a focused, high-performance automated longform copywriting, prompt intelligence, web rewriter, and agency multi-client management platform operating on **100% live API integrations with zero mock data**.

### Phase 1 Core Systems (Build Now):
1. **SEO Blog Creator (`/blog-creator`) [Primary Workspace & Default Landing Page]**:
   - Dual-Mode generation: **Multi-Pass Parallel Section Writing** (Outline -> Parallel Section Prompts -> HTML Compiler -> Humanizer -> Metadata Synthesis) and **Single-Pass Master Draft**.
   - **Complete Prompt Inspector**: One-click preview modal showing the 100% complete, unredacted system & user prompts across all passes before running generation.
   - **Active Client Brand Voice vs. Generic Mode**: Support for attaching an Agency Client (brand voice, target audience, default CTA, cached internal sitemap links, approved external reference domains) OR running in **Independent / Generic Mode** without corporate bias.
   - Custom Industry / Domain text field & Point of View (POV: First Person, Second Person, Third Person) selector.
   - Live real-time console telemetry logs polling.
   - Interactive live article preview with Flesch Reading Ease score, SEO optimization score, word count, estimated cost in USD & INR, HTML/DOCX downloads, and 1-click WordPress REST API publishing.
2. **Agency Multi-Client Management (`/clients`)**:
   - Client profiles, brand tones, target audiences, default CTAs, approved citation domains, WordPress credentials, and automated XML sitemap crawler for contextual internal link indexing.
3. **Prompt Blueprints Archetypes (`/prompt-templates`)**:
   - CRUD for system and custom prompt blueprints with archetype directives, cloning, and resetting.
4. **Rewriter Studio (`/rewriter`)**:
   - URL scraping, DOM hierarchy & CSS layout preservation, 4 rewrite modes (Paraphrase, SEO Refresh, Expansion, Tone Shift), side-by-side comparison, and export.
5. **Content Database (`/articles`)**:
   - Filterable archive of generated articles, SEO audit scores, reading ease, copy HTML, export DOCX, and live WP republishing.
6. **Multi-Provider LLM Orchestration & Presets (`/settings`)**:
   - Dynamic orchestration of Google Gemini (Gemini 2.0 Flash, 1.5 Pro, 2.5 Flash, 3.1 Flash Lite), OpenAI (GPT-4o, GPT-4o-mini), Anthropic Claude (Claude 3.7 Sonnet, Claude 3.5 Haiku), and DeepSeek (V3, R1).
   - Live Model Sync from provider APIs, custom model registration, temperature/top-p tuning, and token cost tracking ($ USD & ₹ INR).
7. **User & RBAC Permissions (`/users`, `/permissions`)**:
   - Role-Based Access Control: Super Admin, Admin, Editor, Creator, Viewer.

---

### Phase 2 Expansion (Deferred - To Be Created Later):
- **10-Step AI SEO Pipeline (`/pipeline/step/{id}`)**: Industry research, topic discovery, keyword clustering, content brief, WordPress gate, GSC/GA4 analytics, diagnostics, and reinjection loop. (Documented in Section 8 for future implementation).

---

## 2. Tech Stack & Architecture (No React)

```
┌────────────────────────────────────────────────────────────────────────┐
│                   BROWSER CLIENT (DAISYUI 5 + JQUERY)                  │
│  - DaisyUI Drawer (Sidebar) + Navbar (Client Switcher) + DaisyUI Steps │
│  - DaisyUI Modals (Prompt Inspector, Client CRUD, Article Viewer)      │
│  - jQuery / Vanilla JS AJAX Handlers + SSE / Polling Telemetry Console │
│  - DaisyUI Toasts (Auto-dismissing alerts with sound/animation)        │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ HTTP / AJAX
┌───────────────────────────────────▼────────────────────────────────────┐
│                    LARAVEL 12 MVC BACKEND (PHP 8.2+)                   │
│  - Routing: routes/web.php (Blade page views + AJAX JSON endpoints)    │
│  - Controllers: SeoBlog, Pipeline, Client, PromptTemplate, Settings    │
│  - Middleware: Authenticated Session / Spatie Role-Permission Guards   │
│  - Services: MultiProviderLlmClient, PromptBuilder, SitemapCrawler,    │
│              SemrushClientService, AiHumanizer, ArticleFormatter       │
│  - Background Queue: Database Queue Worker (php artisan queue:listen)  │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ Eloquent ORM
┌───────────────────────────────────▼────────────────────────────────────┐
│                    MYSQL / SQLITE DATABASE ENGINE                      │
│  - 27 Database Migrations, 20 Models, Foreign Key Integrity            │
│  - Caching: semrush_api_caches, sitemap_cache, app_config.json         │
└────────────────────────────────────────────────────────────────────────┘
```

### Required Composer & NPM Dependencies
```json
// composer.json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "spatie/laravel-permission": "^6.0",
    "guzzlehttp/guzzle": "^7.8",
    "phpoffice/phpword": "^1.2"
  }
}
```

```json
// package.json (Zero React)
{
  "private": true,
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build"
  },
  "devDependencies": {
    "@tailwindcss/vite": "^4.0.0",
    "tailwindcss": "^4.0.0",
    "daisyui": "^5.0.0",
    "vite": "^7.0.0",
    "laravel-vite-plugin": "^2.0.0"
  },
  "dependencies": {
    "jquery": "^3.7.1"
  }
}
```

---

## 3. Database Schema & Migration Specifications

Implement all 27 database migrations in `database/migrations/`:

### 1. `users` table
- `id` (bigint, unsigned, primary key)
- `name` (string)
- `email` (string, unique)
- `password` (string)
- `active_client_id` (unsignedBigInteger, nullable, foreign key -> clients.id, onDelete null)
- `remember_token` (string, nullable)
- `timestamps`

### 2. `clients` table
- `id` (bigint, unsigned, primary key)
- `user_id` (unsignedBigInteger, foreign key -> users.id, onDelete cascade)
- `name` (string, e.g. "GEIMS Hospital")
- `website_url` (string, e.g. "https://geimshospital.com/")
- `industry` (string, nullable)
- `brand_tone` (string, e.g. "Authoritative, Empathetic, Medical Expert")
- `target_audience` (text, nullable)
- `cta_default` (text, nullable)
- `competitor_urls` (json, nullable)
- `approved_reference_domains` (json, nullable, e.g. `["who.int", "nih.gov"]`)
- `sitemap_url` (string, nullable)
- `sitemap_cache` (longText, nullable)
- `last_crawled_at` (timestamp, nullable)
- `wordpress_url` (string, nullable)
- `wordpress_username` (string, nullable)
- `wordpress_app_password` (string, nullable)
- `is_active` (boolean, default true)
- `timestamps`

### 3. `ai_presets` table
- `id` (bigint, unsigned, primary key)
- `user_id` (unsignedBigInteger, foreign key -> users.id, onDelete cascade)
- `name` (string, e.g. "Gemini 2.0 Flash - High Speed")
- `provider` (string, e.g. "gemini", "openai", "claude", "deepseek")
- `model` (string, e.g. "gemini-2.0-flash", "gpt-4o", "claude-3-7-sonnet")
- `temperature` (decimal 3,2, default 0.70)
- `top_p` (decimal 3,2, default 0.95)
- `max_workers` (integer, default 4)
- `custom_instructions` (text, nullable)
- `is_active` (boolean, default false)
- `timestamps`

### 4. `synced_models` table
- `id` (bigint, unsigned, primary key)
- `provider` (string)
- `model_id` (string)
- `display_name` (string)
- `context_window` (integer, default 128000)
- `is_custom` (boolean, default false)
- `timestamps`

### 5. `ai_prompt_templates` table
- `id` (bigint, unsigned, primary key)
- `user_id` (unsignedBigInteger, nullable, foreign key -> users.id, onDelete cascade)
- `archetype_name` (string, e.g. "Ultimate In-Depth Guide", "Comparison Matrix")
- `slug` (string, unique)
- `system_prompt_template` (longText)
- `user_prompt_template` (longText, nullable)
- `is_system` (boolean, default false)
- `timestamps`

### 6. `article_options` table
- `id` (bigint, unsigned, primary key)
- `category` (string, e.g. "tone", "target_audience", "format", "word_count", "language")
- `label` (string)
- `value` (string)
- `description` (string, nullable)
- `is_default` (boolean, default false)
- `sort_order` (integer, default 0)
- `timestamps`

### 7. `seo_generation_jobs` table
- `id` (bigint, unsigned, primary key)
- `user_id` (unsignedBigInteger, foreign key -> users.id, onDelete cascade)
- `execution_mode` (enum: 'single', 'batch', default 'single')
- `status` (enum: 'pending', 'processing', 'completed', 'failed', default 'pending')
- `parameters` (json)
- `total_items` (integer, default 1)
- `completed_items` (integer, default 0)
- `logs` (json, nullable)
- `error_message` (text, nullable)
- `timestamps`

### 8. `articles` table
- `id` (bigint, unsigned, primary key)
- `user_id` (unsignedBigInteger, foreign key -> users.id, onDelete cascade)
- `seo_generation_job_id` (unsignedBigInteger, nullable, foreign key -> seo_generation_jobs.id, onDelete set null)
- `title` (string)
- `slug` (string)
- `meta_title` (string, nullable)
- `meta_description` (text, nullable)
- `html_content` (longText)
- `word_count` (integer, default 0)
- `seo_score` (integer, default 60)
- `flesch_reading_ease` (decimal 5,2, default 65.00)
- `prompt_tokens` (integer, default 0)
- `completion_tokens` (integer, default 0)
- `schema_json` (json, nullable)
- `wordpress_post_id` (string, nullable)
- `wordpress_post_url` (string, nullable)
- `timestamps`

### 9. `rewriter_jobs` table
- `id` (bigint, unsigned, primary key)
- `user_id` (unsignedBigInteger, foreign key -> users.id, onDelete cascade)
- `target_url` (string)
- `mode` (enum: 'semantic_clean', 'layout_preserving', default 'layout_preserving')
- `intensity` (enum: 'standard', 'deep', 'maximum', default 'deep')
- `status` (enum: 'pending', 'processing', 'completed', 'failed', default 'pending')
- `original_html` (longText, nullable)
- `rewritten_html` (longText, nullable)
- `prompt_tokens` (integer, default 0)
- `completion_tokens` (integer, default 0)
- `logs` (json, nullable)
- `error_message` (text, nullable)
- `timestamps`

### 10. `ai_usage_logs` & `token_usage_logs` tables
- `id`, `user_id`, `provider`, `model`, `prompt_tokens`, `completion_tokens`, `cost_usd` (decimal 8,6), `cost_inr` (decimal 10,4), `feature` (string), `metadata` (json), `timestamps`.

### 11. [PHASE 2 DEFERRED] Pipeline Tables (To Be Created Later)
> ⚠️ **NOTE**: These tables belong to the deferred 10-Step Pipeline and must **NOT** be created or migrated in Phase 1:
- `topic_discoveries`: `id`, `client_id`, `cluster_theme`, `seed_topic`, `search_volume`, `cpc`, `trends_data` (json), `seasonality_score`, `is_approved`, `timestamps`.
- `keyword_clusters`: `id`, `client_id`, `topic_discovery_id`, `primary_keyword`, `secondary_keywords` (json), `search_intent`, `difficulty`, `cannibalization_risk`, `timestamps`.
- `content_briefs`: `id`, `client_id`, `keyword_cluster_id`, `target_h1`, `suggested_headings` (json), `internal_link_targets` (json), `competitor_serp_breakdown` (json), `target_word_count`, `timestamps`.
- `publishing_records`: `id`, `client_id`, `article_id`, `wp_post_id`, `live_url`, `status`, `body_html`, `timestamps`.
- `performance_metrics_gsc_ga4`: `id`, `publishing_record_id`, `clicks`, `impressions`, `ctr`, `avg_position`, `bounce_rate`, `recorded_date`, `timestamps`.
- `ai_performance_diagnostics`: `id`, `publishing_record_id`, `health_status` (healthy, declining, critical), `diagnosis_type`, `findings_json`, `recommendations_json`, `timestamps`.
- `learning_repositories`: `id`, `client_id`, `rule_type`, `rule_content`, `impact_score`, `applicable_content_types` (json), `timestamps`.
- `optimization_recommendations`: `id`, `client_id`, `recommendation_text`, `action_payload` (json), `is_applied`, `timestamps`.
- `semrush_api_caches`: `id`, `query_hash` (string, unique), `endpoint`, `request_payload` (json), `response_payload` (json), `expires_at`, `timestamps`.

---

## 4. Backend Services & AI Core Engine

### A. `MultiProviderLlmClient.php`
Manages **100% live API connectivity** across Google Gemini, OpenAI, Claude, and DeepSeek.
- **Strict Zero Mock Policy**: Never fall back to simulated, canned, or dummy text responses under any circumstance.
- **Unconfigured Key & API Error Handling**:
  - If an API key for the requested provider is missing in `config/app_config.json` or `.env`, immediately throw an `UnconfiguredApiKeyException` or return `{ "status": "error", "message": "API key for {provider} is unconfigured. Please configure it in Settings." }`.
  - If a provider API returns an HTTP error (401 Unauthorized, 429 Rate Limit Exceeded, 500 Provider Overloaded), catch the Guzzle `RequestException` and surface the exact API error payload to the user in a DaisyUI error toast.
- **Methods**:
  - `generateText(string $systemPrompt, string $userPrompt, array $options = []): array` (executes live REST calls: Gemini `generateContent`, OpenAI `/v1/chat/completions`, Claude `/v1/messages`, DeepSeek `/v1/chat/completions`).
  - `generateTextParallel(array $prompts, array $options = []): array` (executes parallel concurrent HTTP curl multi-requests for sub-sections).
  - `calculateCost(string $provider, string $model, int $promptTokens, int $completionTokens): array` (calculates dual USD and INR ₹ using official rates).

### B. `PromptBuilder.php`
Encapsulates all strict prompt directives:
1. **Banned Words Filter**:
   - ❌ Strictly bans: `can`, `hence`, `thus`, `as per`, `etc`, `via`, `therefore`, `moreover`, `then`, `have to`, `must`.
   - ❌ Bans starting sentences with `however` or `but`.
   - Forces active voice, simple present tense, bold lead-in titles (`<li><strong>Title:</strong> ...</li>`).
2. **Point of View (POV) Formatter**:
   - `Second Person`: Direct address (`you / your`).
   - `First Person`: Team & practitioner authority (`we / our / I`).
   - `Third Person`: Objective journalistic analysis (`he / she / they`).
3. **Domain / Industry Context**:
   - Injects `- Target Industry / Domain Context: {$industry}` into guidelines.
4. **Mandatory Conclusion Section**:
   - In `buildOutlinePrompt()`: forces schema `"type": "intro | key-takeaways | standard | comparison-table | faq | conclusion | cta"` and mandates a 200–250 word `conclusion` section.
   - In `buildSectionPrompt()`: handles `conclusion` by ordering 2–3 comprehensive synthesis paragraphs with strategic verdict before the final CTA.
5. **No Hardcoded Templates (Agent Standard)**:
   - In Live Mode JSON prompts, always use clean structural placeholders (`"<organic secondary keyword 1>"`, `"<real searcher question 1>"`) instead of echoing hardcoded template strings.

### C. `AiHumanizer.php`
- Scans generated HTML content and removes robotic transition phrases (`in conclusion`, `dive deep into`, `it's important to remember`, `navigating the landscape of`). Smooths paragraph rhythms.

### D. `SitemapCrawlerService.php`
- Fetches target client XML sitemaps (`/sitemap.xml`, `/sitemap_index.xml`), parses `<loc>` entries, caches them in `clients.sitemap_cache`, and provides `getRelevantSitemapLinks(Client $client, string $topic, int $limit = 3)` using Jaccard word-stem similarity for automated contextual internal linking.

### E. `SerpCrawler.php`
- Uses DuckDuckGo HTML search scraping to extract top competitor URLs and H1/H2/H3 heading structures for gap analysis without requiring third-party credits.

---

## 5. Web Routes Specification (`routes/web.php`)

All routes must be declared in `routes/web.php` with session authentication:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SeoBlogController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\PromptTemplateController;
use App\Http\Controllers\RewriterController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AiUsageLogController;

// Authentication
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Application
Route::middleware(['auth'])->group(function () {
    // Default Redirect: Lands on SEO Blog Creator
    Route::get('/', function () { return redirect()->route('blog.creator'); });

    // Global Client Switcher
    Route::post('/client/switch', [ClientController::class, 'switchActiveClient'])->name('client.switch');

    // 1. SEO Blog Creator (Core Primary Workspace)
    Route::get('/blog-creator', [SeoBlogController::class, 'index'])->name('blog.creator');
    Route::post('/blog-creator/generate', [SeoBlogController::class, 'generate'])->name('blog.generate');
    Route::post('/blog-creator/preview-prompt', [SeoBlogController::class, 'previewPrompt'])->name('blog.preview.prompt');
    Route::get('/blog-creator/jobs/{id}/logs', [SeoBlogController::class, 'getJobLogs'])->name('blog.job.logs');
    Route::post('/blog-creator/articles/{id}/publish-wp', [SeoBlogController::class, 'publishToWordPress'])->name('blog.publish.wp');

    // 3. Agency Clients Management
    Route::resource('clients', ClientController::class);
    Route::post('/clients/{client}/crawl-sitemap', [ClientController::class, 'crawlSitemap'])->name('clients.crawl');

    // 4. Prompt Blueprints
    Route::resource('prompt-templates', PromptTemplateController::class);
    Route::post('/prompt-templates/{id}/duplicate', [PromptTemplateController::class, 'duplicate'])->name('prompt-templates.duplicate');
    Route::post('/prompt-templates/{id}/reset', [PromptTemplateController::class, 'reset'])->name('prompt-templates.reset');

    // 5. Rewriter Studio
    Route::get('/rewriter', [RewriterController::class, 'index'])->name('rewriter.index');
    Route::post('/rewriter/create', [RewriterController::class, 'create'])->name('rewriter.create');
    Route::get('/rewriter/jobs/{id}/status', [RewriterController::class, 'getStatus'])->name('rewriter.status');
    Route::delete('/rewriter/jobs/{id}', [RewriterController::class, 'destroy'])->name('rewriter.destroy');
    Route::get('/rewriter/jobs/{id}/download/{format}', [RewriterController::class, 'download'])->name('rewriter.download');

    // 6. Content Database / Articles
    Route::resource('articles', ArticleController::class)->only(['index', 'show', 'destroy']);
    Route::get('/articles/{id}/download/{format}', [ArticleController::class, 'download'])->name('articles.download');

    // 7. Settings & Presets
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/api-keys', [SettingsController::class, 'saveApiKeys'])->name('settings.api_keys');
    Route::post('/settings/presets', [SettingsController::class, 'savePreset'])->name('settings.presets.save');
    Route::post('/settings/presets/{id}/activate', [SettingsController::class, 'activatePreset'])->name('settings.presets.activate');
    Route::delete('/settings/presets/{id}', [SettingsController::class, 'deletePreset'])->name('settings.presets.delete');
    Route::post('/settings/sync-models', [SettingsController::class, 'syncModels'])->name('settings.models.sync');
    Route::post('/settings/custom-model', [SettingsController::class, 'addCustomModel'])->name('settings.models.add');

    // 8. User Management & Permissions
    Route::middleware(['role:super_admin|admin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::get('/permissions', [RolePermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions/sync', [RolePermissionController::class, 'sync'])->name('permissions.sync');
    });

    // 8. Cost & Usage Reports
    Route::get('/reports/token-usage', [AiUsageLogController::class, 'index'])->name('reports.usage');

    /*
    |--------------------------------------------------------------------------
    | [PHASE 2 DEFERRED] 10-Step AI Pipeline Routes (To Be Created Later)
    |--------------------------------------------------------------------------
    Route::prefix('pipeline')->group(function () {
        Route::get('/step/{stepId}', [PipelineController::class, 'showStep'])->name('pipeline.step');
        Route::post('/step-1/research', [PipelineController::class, 'runStep1'])->name('pipeline.step1');
        Route::post('/step-2/discover-topics', [PipelineController::class, 'runStep2'])->name('pipeline.step2');
        Route::post('/step-3/validate-trends', [PipelineController::class, 'runStep3'])->name('pipeline.step3');
        Route::post('/step-4/cluster-keywords', [PipelineController::class, 'runStep4'])->name('pipeline.step4');
        Route::post('/step-5/generate-brief', [PipelineController::class, 'runStep5'])->name('pipeline.step5');
        Route::post('/step-6/produce-content', [PipelineController::class, 'runStep6'])->name('pipeline.step6');
        Route::post('/step-6/publish-wp', [PipelineController::class, 'publishToWp'])->name('pipeline.step6.wp');
        Route::get('/step-7/analytics/{id}', [PipelineController::class, 'getAnalytics'])->name('pipeline.step7.analytics');
        Route::post('/step-8/diagnose/{id}', [PipelineController::class, 'runDiagnostics'])->name('pipeline.step8.diagnose');
        Route::get('/step-9/learnings', [PipelineController::class, 'getLearnings'])->name('pipeline.step9.learnings');
        Route::post('/step-10/reinject', [PipelineController::class, 'reinjectRules'])->name('pipeline.step10.reinject');
    });
    */
});
```

---

## 6. Frontend Layout & DaisyUI Component Structure

### Base Layout: `resources/views/layouts/app.blade.php`
Implements the DaisyUI **Drawer** component for the collapsible sidebar:

```html
<!DOCTYPE html>
<html lang="en" data-theme="night">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AI SEO Engine') - Content Intelligence</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#080c14] text-slate-100 min-h-screen font-sans selection:bg-indigo-500/25 selection:text-indigo-200">
    <div class="drawer lg:drawer-open">
        <input id="app-drawer" type="checkbox" class="drawer-toggle" />
        
        <!-- Page Content Wrapper -->
        <div class="drawer-content flex flex-col min-h-screen">
            <!-- Top Navbar -->
            @include('components.navbar')

            <!-- Main Page View Content -->
            <main class="flex-1 p-4 lg:p-6 bg-[#080c14]/90 overflow-y-auto">
                @yield('content')
            </main>

            <!-- Bottom Statusbar -->
            @include('components.statusbar')
        </div>

        <!-- Sidebar Drawer Side -->
        <div class="drawer-side z-40">
            <label for="app-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            @include('components.sidebar')
        </div>
    </div>

    <!-- Global Toast Container -->
    <div id="toast-container" class="toast toast-top toast-end z-[999999]"></div>

    <!-- Global Scripts -->
    <script>
        // CSRF Token Setup for jQuery AJAX
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // Global Toast Notification Helper
        function showToast(message, type = 'info') {
            const alertClass = type === 'error' ? 'alert-error' : (type === 'success' ? 'alert-success' : 'alert-info');
            const icon = type === 'error' ? 'alert-circle' : (type === 'success' ? 'check-circle' : 'info');
            const toastId = 'toast-' + Date.now();
            const toastHtml = `
                <div id="${toastId}" class="alert ${alertClass} shadow-lg py-2.5 px-4 text-xs font-medium flex items-center gap-2 animate-in fade-in slide-in-from-top-2">
                    <i data-lucide="${icon}" class="w-4 h-4"></i>
                    <span>${message}</span>
                </div>
            `;
            $('#toast-container').append(toastHtml);
            lucide.createIcons();
            setTimeout(() => { $(`#${toastId}`).fadeOut(300, function() { $(this).remove(); }); }, 4000);
        }

        $(document).ready(function() {
            lucide.createIcons();
        });
    </script>
    @stack('scripts')
</body>
</html>
```

### Top Navbar Component: `resources/views/components/navbar.blade.php`
Features:
- Drawer toggle button (on mobile).
- Workspace Title display.
- **Active Client Quick Switcher Dropdown**:
  - Contains `<option value="">None (Generic Mode)</option>`.
  - Lists all active clients. Selecting an option sends an AJAX `POST /client/switch` and reloads the current page context without losing settings.
- Active Model Preset Badge (`gemini / gemini-2.0-flash`).
- User Profile dropdown with Logout.

### Sidebar Component: `resources/views/components/sidebar.blade.php`
DaisyUI Menu with:
- Brand Logo ("AI SEO Engine - Content Intelligence", Pro Badge).
- **Primary Workspaces Menu (Phase 1 Active)**:
  - **SEO Blog Creator (`/blog-creator`)** (Highlighted primary entry point with PenTool icon)
  - **Agency Clients (`/clients`)** (Building2 icon)
  - **Prompt Blueprints (`/prompt-templates`)** (FileCode icon)
  - **Rewriter Studio (`/rewriter`)** (RefreshCw icon)
  - **Content Database (`/articles`)** (Database icon)
  - **User Manager (`/users`)** (Users icon)
  - **Role Permissions (`/permissions`)** (Shield icon)
  - **Settings & Presets (`/settings`)** (Settings icon)
- Bottom LLM Engine Status Indicator ("● Ready - Gemini").
- *(Note: The 10-Step Pipeline accordion is deferred for Phase 2 and is omitted from the Phase 1 sidebar)*.

---

## 7. SEO Blog Creator Page Specification (`/blog-creator`)

File: `resources/views/blog-creator/index.blade.php`

### Left Sidebar: Article Controls Form
1. **Client Profile Selector**:
   - `<select id="client-select" class="select select-bordered select-sm w-full bg-[#080c14] text-xs">`
   - Option 1: `<option value="">None (Generic / Independent Article)</option>`
   - Option 2+: Client list. Shows badge when client or generic mode is active.
2. **Topic Input**: Required text input.
3. **Focus Primary Keyword & Secondary Keywords Input**: Text inputs with tags styling.
4. **Search Intent**: Select (`Informational`, `Commercial`, `Transactional`, `Navigational`).
5. **Target Industry / Domain Context**: Text input (auto-fills from selected client, editable for custom generic niche).
6. **Point of View (POV)**:
   - Select (`Second Person (you/your) - Reader-Centric`, `First Person (we/our/I) - Team & Experience`, `Third Person (he/she/they) - Objective`).
7. **Prompt Blueprint Archetype**: Select populated from `ai_prompt_templates` (e.g. "Default AI Directives", "Ultimate Guide", etc.).
8. **Word Count, Tone, Audience, Language**: Select dropdowns populated from `article_options`.
9. **Toggles**:
   - DuckDuckGo SERP Scraper (`checkbox class="toggle toggle-primary toggle-sm"`).
   - AI Humanizer Pass (`checkbox class="toggle toggle-primary toggle-sm"`).
10. **Dual Action Button Group**:
    - **"Complete Prompt" Button**:
      ```html
      <button type="button" id="btn-preview-prompt" class="btn btn-sm btn-outline border-white/20 text-slate-200 flex-1 gap-1.5">
          <i data-lucide="eye" class="w-3.5 h-3.5 text-indigo-400"></i>
          <span>Complete Prompt</span>
      </button>
      ```
    - **"Generate Article" Button**:
      ```html
      <button type="button" id="btn-generate-article" class="btn btn-sm btn-primary bg-indigo-600 hover:bg-indigo-500 flex-1 gap-1.5">
          <i data-lucide="play" class="w-3.5 h-3.5 fill-white"></i>
          <span id="generate-btn-text">Generate Article</span>
      </button>
      ```

### Console Telemetry Card
- Monospace terminal log console (`bg-[#080c14] border border-white/10 rounded-2xl h-56 font-mono text-xs p-4 overflow-y-auto`).
- Streams real-time timestamps and log steps (Prompt compiling, Outline generation, Parallel section writing, HTML compiler, Humanizer, Metadata extraction).

### Right Pane: Live Article Workbench
- Top Bar:
  - Article Title.
  - Metrics Badges: Word Count, SEO Score (`badge-success`), Reading Ease (`badge-info`), Estimated Cost USD/INR.
  - Action Buttons:
    - `Download HTML` (Blob download).
    - `Download DOCX` (Blob download).
    - `Publish to WP` (Checks if active client has WP credentials. If Generic mode, triggers DaisyUI error toast stating a client profile is required).
- Content Viewer:
  - Renders generated HTML with key-takeaways callout box, comparison tables, FAQ accordions, dedicated conclusion section, and client CTA box.

### Complete Prompt Inspector Modal (DaisyUI 5 Modal)
Rendered at bottom of page:
```html
<dialog id="prompt_inspector_modal" class="modal modal-bottom sm:modal-middle">
  <div class="modal-box w-11/12 max-w-6xl bg-[#0a0e1a] border border-white/15 text-slate-100 p-0 shadow-2xl overflow-hidden max-h-[92vh] flex flex-col">
    <!-- Header -->
    <div class="px-6 py-3.5 border-b border-white/10 bg-[#070b14] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
          <i data-lucide="code" class="w-5 h-5"></i>
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h3 class="font-bold text-sm text-white">Complete LLM Prompt Inspector</h3>
            <span id="prompt-model-badge" class="badge badge-sm badge-outline text-indigo-300"></span>
            <span id="prompt-mode-badge" class="badge badge-sm"></span>
            <span id="prompt-pov-badge" class="badge badge-sm bg-slate-800 text-slate-300"></span>
          </div>
          <p class="text-[11px] text-slate-400 mt-0.5">100% unredacted system directives and user prompts dispatched to the AI engine for this article.</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button id="btn-refresh-prompt" class="btn btn-xs btn-ghost gap-1 border border-white/10"><i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Refresh</button>
        <form method="dialog"><button class="btn btn-xs btn-error btn-outline gap-1"><i data-lucide="x" class="w-3.5 h-3.5"></i> Close</button></form>
      </div>
    </div>

    <!-- Tabs Header -->
    <div class="tabs tabs-bordered bg-[#090d18] px-6 border-b border-white/10 text-xs">
      <a class="tab tab-active prompt-tab" data-target="tab-master"><i data-lucide="sparkles" class="w-3.5 h-3.5 mr-1.5"></i> Master Article Prompt</a>
      <a class="tab prompt-tab" data-target="tab-outline"><i data-lucide="layers" class="w-3.5 h-3.5 mr-1.5"></i> Pass 1: Outline Architecture</a>
      <a class="tab prompt-tab" data-target="tab-sections"><i data-lucide="code" class="w-3.5 h-3.5 mr-1.5"></i> Pass 2: Section Prompts</a>
      <a class="tab prompt-tab" data-target="tab-metadata"><i data-lucide="file-text" class="w-3.5 h-3.5 mr-1.5"></i> Pass 3: Metadata Prompt</a>
    </div>

    <!-- Section Sub-Tabs (Shown when Pass 2 is active) -->
    <div id="section-sub-tabs" class="hidden flex items-center gap-1.5 px-6 py-2 bg-[#080c14] border-b border-white/10 overflow-x-auto text-xs">
      <span class="text-[11px] text-slate-400 mr-2">Section:</span>
      <button class="btn btn-xs btn-primary section-sub-tab" data-sec="intro">Intro</button>
      <button class="btn btn-xs btn-ghost section-sub-tab" data-sec="standard">Standard Body</button>
      <button class="btn btn-xs btn-ghost section-sub-tab" data-sec="comparison-table">Comparison Table</button>
      <button class="btn btn-xs btn-ghost section-sub-tab" data-sec="faq">FAQ</button>
      <button class="btn btn-xs btn-ghost section-sub-tab" data-sec="conclusion">Conclusion</button>
      <button class="btn btn-xs btn-ghost section-sub-tab" data-sec="cta">CTA Box</button>
    </div>

    <!-- Tab Content & Code Previews -->
    <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-[#070b14]">
      <!-- Stats & Copy Action Bar -->
      <div class="flex items-center justify-between p-3 bg-[#0d1222] border border-white/10 rounded-xl">
        <div id="prompt-stats" class="text-xs text-slate-300 font-mono"></div>
        <div class="flex gap-2">
          <button id="btn-copy-sys" class="btn btn-xs btn-ghost border border-white/10">Copy System</button>
          <button id="btn-copy-user" class="btn btn-xs btn-ghost border border-white/10">Copy User</button>
          <button id="btn-copy-all" class="btn btn-xs btn-primary">Copy Complete Prompt</button>
        </div>
      </div>

      <!-- System Prompt Card -->
      <div class="card bg-[#050811] border border-white/10 shadow-lg">
        <div class="card-body p-4">
          <h4 class="text-xs font-mono font-bold text-emerald-400 uppercase tracking-wider mb-2 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span> System Directives & Banned Words Filter
          </h4>
          <pre id="pre-sys-prompt" class="text-xs font-mono text-emerald-200/90 whitespace-pre-wrap leading-relaxed max-h-64 overflow-y-auto"></pre>
        </div>
      </div>

      <!-- User Prompt Card -->
      <div class="card bg-[#050811] border border-white/10 shadow-lg">
        <div class="card-body p-4">
          <h4 class="text-xs font-mono font-bold text-amber-300 uppercase tracking-wider mb-2 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-amber-400"></span> User Task & Dynamic Inputs
          </h4>
          <pre id="pre-user-prompt" class="text-xs font-mono text-amber-100/90 whitespace-pre-wrap leading-relaxed max-h-64 overflow-y-auto"></pre>
        </div>
      </div>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
```

### jQuery Interactivity (`resources/js/blog-creator.js`)
```javascript
$(document).ready(function() {
    let currentPromptData = null;
    let currentTab = 'master';
    let currentSection = 'intro';

    // 1. Complete Prompt Inspector Handler
    $('#btn-preview-prompt, #btn-refresh-prompt').on('click', function() {
        const topic = $('#input-topic').val().trim();
        if (!topic) {
            showToast('Please enter a Topic before previewing the prompt.', 'error');
            return;
        }

        const formData = getFormData();
        $('#btn-refresh-prompt i').addClass('animate-spin');

        $.post('/blog-creator/preview-prompt', formData, function(res) {
            currentPromptData = res;
            renderPromptView();
            document.getElementById('prompt_inspector_modal').showModal();
        }).fail(function(err) {
            showToast(err.responseJSON?.message || 'Failed to compile prompt preview', 'error');
        }).always(function() {
            $('#btn-refresh-prompt i').removeClass('animate-spin');
        });
    });

    // 2. Tab Navigation
    $('.prompt-tab').on('click', function() {
        $('.prompt-tab').removeClass('tab-active');
        $(this).addClass('tab-active');
        currentTab = $(this).data('target').replace('tab-', '');
        if (currentTab === 'sections') {
            $('#section-sub-tabs').removeClass('hidden');
        } else {
            $('#section-sub-tabs').addClass('hidden');
        }
        renderPromptView();
    });

    $('.section-sub-tab').on('click', function() {
        $('.section-sub-tab').removeClass('btn-primary').addClass('btn-ghost');
        $(this).removeClass('btn-ghost').addClass('btn-primary');
        currentSection = $(this).data('sec');
        renderPromptView();
    });

    function renderPromptView() {
        if (!currentPromptData) return;
        let promptObj = {};
        if (currentTab === 'master') promptObj = currentPromptData.master_prompt;
        else if (currentTab === 'outline') promptObj = currentPromptData.outline_prompt;
        else if (currentTab === 'sections') promptObj = currentPromptData.section_prompts[currentSection];
        else if (currentTab === 'metadata') promptObj = currentPromptData.metadata_prompt;

        const sys = promptObj.system || '';
        const usr = promptObj.user || '';
        $('#pre-sys-prompt').text(sys);
        $('#pre-user-prompt').text(usr);

        const words = (sys + ' ' + usr).trim().split(/\s+/).filter(Boolean).length;
        $('#prompt-stats').html(`Words: <strong class="text-indigo-300">${words.toLocaleString()}</strong> | Chars: <strong class="text-indigo-300">${(sys.length + usr.length).toLocaleString()}</strong>`);
    }

    // 3. Copy Buttons
    $('#btn-copy-sys').on('click', function() {
        navigator.clipboard.writeText($('#pre-sys-prompt').text());
        showToast('System prompt copied to clipboard!', 'success');
    });
    $('#btn-copy-user').on('click', function() {
        navigator.clipboard.writeText($('#pre-user-prompt').text());
        showToast('User prompt copied to clipboard!', 'success');
    });
    $('#btn-copy-all').on('click', function() {
        const full = `### SYSTEM DIRECTIVES:\n${$('#pre-sys-prompt').text()}\n\n### USER TASK:\n${$('#pre-user-prompt').text()}`;
        navigator.clipboard.writeText(full);
        showToast('Complete prompt copied to clipboard!', 'success');
    });

    // 4. Generation Job Dispatch with Live Telemetry Polling
    $('#btn-generate-article').on('click', function() {
        const topic = $('#input-topic').val().trim();
        if (!topic) {
            showToast('Please enter a Topic to generate.', 'error');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).addClass('loading');
        $('#generate-btn-text').text('Generating Article...');
        $('#console-logs').html(`<div class="text-slate-400">[${new Date().toLocaleTimeString()}] Dispatching multi-pass generation job to core...</div>`);

        $.post('/blog-creator/generate', getFormData(), function(res) {
            pollJobLogs(res.job_id);
        }).fail(function(err) {
            showToast(err.responseJSON?.message || 'Article generation failed.', 'error');
            $btn.prop('disabled', false).removeClass('loading');
            $('#generate-btn-text').text('Generate Article');
        });
    });

    function pollJobLogs(jobId) {
        const interval = setInterval(function() {
            $.get(`/blog-creator/jobs/${jobId}/logs`, function(data) {
                if (data.logs) {
                    $('#console-logs').html(data.logs.map(l => `<div class="text-slate-300 font-mono">${l}</div>`).join(''));
                    document.getElementById('console-logs').scrollTop = document.getElementById('console-logs').scrollHeight;
                }
                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(interval);
                    $('#btn-generate-article').prop('disabled', false).removeClass('loading');
                    $('#generate-btn-text').text('Generate Article');
                    if (data.status === 'completed') {
                        showToast('Article generated successfully!', 'success');
                        window.location.reload();
                    } else {
                        showToast('Generation job failed: ' + (data.error || 'Unknown error'), 'error');
                    }
                }
            });
        }, 1500);
    }

    function getFormData() {
        return {
            client_id: $('#client-select').val(),
            topic: $('#input-topic').val(),
            primary_keyword: $('#input-primary-kw').val(),
            secondary_keywords: $('#input-secondary-kws').val(),
            industry: $('#input-industry').val(),
            pov: $('#select-pov').val(),
            search_intent: $('#select-intent').val(),
            format: $('#select-format').val(),
            word_count: $('#select-word-count').val(),
            tone: $('#select-tone').val(),
            target_audience: $('#select-audience').val(),
            language: $('#select-language').val(),
            prompt_template_id: $('#select-prompt-blueprint').val(),
            enable_serp_crawler: $('#toggle-serp').is(':checked'),
            humanizer_active: $('#toggle-humanizer').is(':checked')
        };
    }
});
```

---

## 8. [PHASE 2 DEFERRED] 10-Step AI SEO Pipeline Specification (To Be Created Later)

> ⚠️ **PHASE 2 ROADMAP NOTICE**: The 10-Step AI SEO Pipeline is intentionally deferred for future implementation. The developer MUST skip creating these views, routes, migrations, and controller methods during Phase 1. This complete specification is preserved below for reference when Phase 2 is initiated.

File: `resources/views/pipeline/index.blade.php` (Phase 2 Deferred)

### Visual Breadcrumb: DaisyUI `steps`
```html
<ul class="steps steps-horizontal w-full text-xs font-semibold mb-6 overflow-x-auto py-2">
    <li class="step {{ $stepId >= 1 ? 'step-primary' : '' }}"><a href="/pipeline/step/1">1. Research</a></li>
    <li class="step {{ $stepId >= 2 ? 'step-primary' : '' }}"><a href="/pipeline/step/2">2. Topics</a></li>
    <li class="step {{ $stepId >= 3 ? 'step-primary' : '' }}"><a href="/pipeline/step/3">3. Keywords</a></li>
    <li class="step {{ $stepId >= 4 ? 'step-primary' : '' }}"><a href="/pipeline/step/4">4. Brief</a></li>
    <li class="step {{ $stepId >= 5 ? 'step-primary' : '' }}"><a href="/pipeline/step/5">5. Production</a></li>
    <li class="step {{ $stepId >= 6 ? 'step-primary' : '' }}"><a href="/pipeline/step/6">6. Publishing</a></li>
    <li class="step {{ $stepId >= 7 ? 'step-primary' : '' }}"><a href="/pipeline/step/7">7. Analytics</a></li>
    <li class="step {{ $stepId >= 8 ? 'step-primary' : '' }}"><a href="/pipeline/step/8">8. Diagnostics</a></li>
    <li class="step {{ $stepId >= 9 ? 'step-primary' : '' }}"><a href="/pipeline/step/9">9. Knowledge</a></li>
    <li class="step {{ $stepId >= 10 ? 'step-primary' : '' }}"><a href="/pipeline/step/10">10. Reinjection</a></li>
</ul>
```

### All 10 Pipeline Step Views
1. **Step 1: Industry Research & Benchmark**:
   - SEMrush Domain Rank, Organic Keywords, Competitors list, Reference Authority domains.
   - Run Research AJAX button, Live SEMrush cache refresh button.
2. **Step 2: Topic Discovery & Trends**:
   - Seed keyword input, Google Trends seasonality chart (Chart.js or SVG sparklines), search volume, CPC, approval checkboxes for cluster generation.
3. **Step 3: Keyword Intelligence**:
   - 8-dimension keyword matrix table (Search Volume, Keyword Difficulty KD%, CPC, Search Intent, SERP Features, Trend, Cannibalization check, Action).
4. **Step 4: Content Brief & Wireframes**:
   - Generate structured heading blueprint (H1, H2, H3), target word count, suggested internal links from client sitemap, competitor heading gaps.
5. **Step 5: Production**:
   - Multi-pass drafting engine output, formatted HTML preview, SEO checklist audit.
6. **Step 6: WordPress Publishing Gate**:
   - Quality inspection gate (Checks readability, word count threshold, keyword density). WordPress category selection, publish status ('draft' or 'publish'), 1-click live publish via WP REST API.
7. **Step 7: Analytics & GSC/GA4**:
   - Post-publication metrics table (Clicks, Impressions, CTR%, Average Position, Bounce Rate).
8. **Step 8: AI Performance Diagnostics**:
   - Categorizes articles into `Healthy`, `Declining`, `Critical Underperformer`. Runs AI diagnostics on ranking drops.
9. **Step 9: Knowledge Repository**:
   - Self-learning rules extracted from top-performing articles (e.g. "Include comparison table in first 400 words for fintech topics").
10. **Step 10: Closed-Loop Prompt Reinjection**:
    - Selects approved high-impact rules from Step 9 and reinjects them directly into the active prompt templates and presets.

---

## 9. Agency Clients Workspace (`/clients`)

File: `resources/views/clients/index.blade.php`

### Features:
1. **Client Cards Grid**:
   - Card displays Client Name, Website URL, Industry Badge, Brand Tone snippet, Target Audience, and Active Client Indicator.
   - Actions:
     - `Set as Active Client` button (sets `users.active_client_id`).
     - `Crawl Sitemap` button (Triggers `POST /clients/{id}/crawl-sitemap`, parses XML sitemap, shows count of cached URLs).
     - `Edit` and `Delete` buttons.
2. **Create / Edit Client DaisyUI Modal**:
   - Form inputs: Client Name, Website URL, Industry, Brand Tone, Target Audience, Default Call-To-Action, Competitor URLs (textarea comma-separated), Approved Reference Domains (comma-separated), WordPress Endpoint URL, WP Username, WP Application Password.

---

## 10. Prompt Blueprints Workspace (`/prompt-templates`)

File: `resources/views/prompt-templates/index.blade.php`

### Features:
1. **Archetype Cards**:
   - Displays Archetype Name, System vs Custom Badge, System Prompt Directives excerpt.
   - Actions:
     - `Duplicate`: Clones archetype into a new user-editable custom blueprint.
     - `Edit`: Modifies custom blueprint.
     - `Reset`: Restores default system archetypes.
     - `Delete`: Deletes custom templates.
2. **Create / Edit Blueprint DaisyUI Modal**:
   - Form inputs: Archetype Name, Slug, System Prompt Directives (large textarea with monospace font), User Prompt Template.

---

## 11. Rewriter Studio Workspace (`/rewriter`)

File: `resources/views/rewriter/index.blade.php`

### Features:
1. **Target URL Input Form**:
   - URL field, Rewrite Mode selector (`Layout Preserving Structure` vs `Semantic Clean HTML`), Intensity level (`Standard`, `Deep`, `Maximum`), Humanizer toggle.
2. **Live DOM Scraper & Rewriter Pipeline**:
   - Guzzle / cURL scraper loads the target URL HTML.
   - `LayoutPreservingRewriter.php` uses DOMDocument / XPath to parse text nodes while preserving all container tags, CSS classes, styles, and IDs.
   - LLM re-writes text without repeating template words.
3. **Side-by-Side Comparison Viewer**:
   - Original Page vs. Rewritten Page tabs.
   - Word count delta, readability score comparison.
   - Export HTML & Export DOCX.

---

## 12. Settings, API Keys & Multi-Model Presets (`/settings`)

File: `resources/views/settings/index.blade.php`

### Tabs:
1. **API Keys Configuration**:
   - Secure inputs for Google Gemini API Key, OpenAI API Key, Anthropic Claude API Key, DeepSeek API Key, SEMrush API Key, and SerpAPI Key.
   - Saved to persistent backend storage (`config/app_config.json` and `.env`).
2. **AI Model Presets Management**:
   - Table of presets with Provider, Model, Temperature, Top-P, Active toggle, and Delete action.
   - "Create New Preset" DaisyUI modal with provider dropdown, model selector, sliders for temperature (0.0 to 1.0) and top-p, and custom instructions textarea.
   - "Sync Models" button: Calls provider API endpoints (e.g. Gemini `v1beta/models`, OpenAI `/v1/models`) and updates `synced_models` table.
   - "Add Custom Model" button: Allows manual model IDs (e.g. `claude-3-7-sonnet-20250219`).
3. **Token Usage & Cost Reports**:
   - Dual-Currency summary cards: Total Cost USD ($) and Total Cost INR (Rs. ₹).
   - Usage table broken down by Date, Provider, Model, Prompt Tokens, Completion Tokens, and Total Spent.

---

## 13. Step-by-Step Implementation Roadmap for the AI

Follow these exact implementation steps for Phase 1:

```markdown
1. SETUP & PHASE 1 MIGRATIONS:
   - Configure Tailwind CSS 4 & DaisyUI 5 in vite.config.js and resources/css/app.css.
   - Run Phase 1 core database migrations (users, clients, ai_presets, synced_models, ai_prompt_templates, article_options, seo_generation_jobs, articles, rewriter_jobs, ai_usage_logs, token_usage_logs). Skip the deferred pipeline migrations.
   - Seed default admin user (admin@webaiseo.com), default client, article options, and default prompt templates. DO NOT seed dummy articles, fake clients, or mock generation data.

2. CORE SERVICES:
   - Create app/Services/MultiProviderLlmClient.php with Gemini, OpenAI, Claude, DeepSeek API callers and dual-currency cost calculation. Ensure 100% live API execution with zero mock data and explicit error handling for missing keys/rate limits.
   - Create app/Services/PromptBuilder.php with strict banned words filter, POV formatters, client brand persona, domain context, and mandatory conclusion logic.
   - Create app/Services/SitemapCrawlerService.php, SerpCrawler.php, AiHumanizer.php, and ArticleFormatter.php.

3. BLADE LAYOUT & NAVIGATION:
   - Build resources/views/layouts/app.blade.php with DaisyUI drawer, navbar, and toast container.
   - Build resources/views/components/sidebar.blade.php with active links to Blog Creator, Clients, Prompt Blueprints, Rewriter, Articles, Settings, and Users (omitting the pipeline).
   - Build resources/views/components/navbar.blade.php with global client switcher dropdown (including Generic Mode option).

4. BLOG CREATOR WORKSPACE (PRIMARY):
   - Create app/Http/Controllers/SeoBlogController.php with index, previewPrompt, generate, getJobLogs, and publishToWordPress.
   - Build resources/views/blog-creator/index.blade.php with Complete Prompt modal and telemetry console.
   - Write jQuery logic for real-time prompt previewing, log polling, and live article display.

5. REMAINING CORE WORKSPACES:
   - Build Agency Clients CRUD (/clients) with sitemap crawl trigger.
   - Build Prompt Blueprints CRUD (/prompt-templates) with archetype duplicate and reset.
   - Build Rewriter Studio (/rewriter) with layout preservation and comparison preview.
   - Build Content Database (/articles) with article show, download, and publish.
   - Build Settings & Presets (/settings) with API keys, preset CRUD, sync models, and token cost reports.
   - Build User Management & Role Permissions (/users, /permissions).
   - Build Authentication (/login, /logout).

6. VERIFICATION:
   - Verify all Phase 1 routes in routes/web.php (landing directly on /blog-creator).
   - Verify that all API failures trigger user-facing error toasts.
   - Run tests and ensure zero React dependencies exist.
```

---

## 14. Verification Checklist

Before considering Phase 1 implementation complete, verify that:
- [ ] No React, TSX, JSX, or Vue packages exist in `package.json`.
- [ ] The app runs purely on Laravel 12 Blade templates and DaisyUI 5.
- [ ] Every page has working jQuery / Vanilla JS interactions with CSRF headers.
- [ ] The default landing page (`/`) redirects directly to the SEO Blog Creator (`/blog-creator`).
- [ ] **Zero Mock Data Policy**: Absolutely NO mock data, canned fake responses, dummy articles, or simulated job records exist.
- [ ] Real Live API Execution: Unconfigured API keys or live API failures immediately trigger user-facing DaisyUI error toasts with exact diagnostic messages.
- [ ] The "Complete Prompt" button opens the DaisyUI modal and accurately reflects all selected form inputs, POV, brand identity, and conclusion directives.
- [ ] Every generated article includes a dedicated, high-value Conclusion section before the CTA box.
- [ ] Switching clients from the top navbar updates the active client across the entire platform.
- [ ] Generic Mode works seamlessly when "None" is selected.
- [ ] WordPress publishing works with client credentials and blocks publishing in Generic Mode.
- [ ] The 10-Step Pipeline is deferred and omitted from active Phase 1 navigation.
