@extends('layouts.app')

@section('title', 'SERP Rank Tracker')
@section('page_title', 'SERP Rank Tracker')
@section('page_badge', 'Top 50 Organic Intelligence')

@section('content')
<div class="space-y-6">
    <!-- Header with Live Credits Badge & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-base-100 p-5 rounded-2xl border border-base-300 shadow-sm">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="badge badge-primary badge-outline badge-sm font-mono uppercase tracking-wider">SerpApi Live</span>
                <span class="text-xs text-base-content/60">Google Search Rank Tracker (Top 50 Depth)</span>
            </div>
            <h1 class="text-2xl font-black text-base-content tracking-tight">Keyword Ranking Checker</h1>
            <p class="text-xs text-base-content/70">
                100% live Google SERP organic positions, landing page citations, and top 10 competitor landscape.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Live SerpApi Credits Badge -->
            <div id="credits-badge-wrapper" class="flex items-center gap-2.5 bg-base-200/80 border border-base-300 rounded-xl px-3.5 py-2 shadow-xs">
                <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center">
                    <i data-lucide="zap" class="w-4 h-4 fill-amber-500"></i>
                </div>
                <div class="text-xs">
                    <div class="text-[10px] text-base-content/60 font-semibold uppercase tracking-wider">SerpApi Credits</div>
                    <div class="font-mono font-bold text-base-content flex items-center gap-1">
                        <span id="credits-left-val" class="text-primary">{{ number_format($credits['searches_left'] ?? 0) }}</span>
                        @if(($credits['extra_credits'] ?? 0) > 0)
                            <span class="text-base-content/60 text-[11px]" title="{{ number_format($credits['extra_credits']) }} extra credits available">available</span>
                        @else
                            <span class="text-base-content/40 text-[11px]">/ {{ $credits['searches_per_month'] ?? 'N/A' }}</span>
                        @endif
                        <span class="text-[10px] text-base-content/50 font-normal">({{ $credits['plan_name'] ?? 'Plan' }})</span>
                    </div>
                </div>
                <button type="button" onclick="refreshLiveCredits(this)" class="btn btn-ghost btn-circle btn-xs ml-1 hover:bg-base-300" title="Refresh Live Credit Balance">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-base-content/70"></i>
                </button>
            </div>

            <!-- Export CSV & Actions -->
            @can('export-rank-data')
            <a href="{{ route('rank-tracker.export.csv', ['client_id' => $clientId]) }}" class="btn btn-outline btn-sm gap-1.5 border-base-300 text-xs font-semibold shadow-xs">
                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                <span>Export CSV</span>
            </a>
            @endcan
            @can('delete-rank-data')
            @if($rankChecks->count() > 0)
                <button type="button" onclick="confirmClearHistory()" class="btn btn-ghost text-error btn-sm text-xs gap-1">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Clear</span>
                </button>
            @endif
            @endcan
        </div>
    </div>

    <!-- Telemetry KPI Cards Bar (5-Column Responsive Grid) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- 1. Total Tracked -->
        <div class="card bg-base-100 border border-base-300 p-3.5 rounded-xl shadow-xs">
            <div class="flex items-center justify-between text-base-content/60 text-xs mb-1">
                <span class="font-semibold">Tracked Queries</span>
                <i data-lucide="hash" class="w-3.5 h-3.5 text-indigo-400"></i>
            </div>
            <div class="text-2xl font-black text-base-content font-mono">{{ number_format($kpis['total']) }}</div>
            <div class="text-[10px] text-base-content/50 mt-0.5">Top 50 evaluation</div>
        </div>

        <!-- 2. Top 3 Positions -->
        <div class="card bg-base-100 border border-base-300 p-3.5 rounded-xl shadow-xs border-l-4 border-l-emerald-500">
            <div class="flex items-center justify-between text-base-content/60 text-xs mb-1">
                <span class="font-semibold text-emerald-600 dark:text-emerald-400">Top 3 (#1 - #3)</span>
                <i data-lucide="trophy" class="w-3.5 h-3.5 text-emerald-500"></i>
            </div>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ number_format($kpis['top3']) }}</div>
            <div class="text-[10px] text-emerald-600/70 dark:text-emerald-400/70 mt-0.5">High CTR podium</div>
        </div>

        <!-- 3. Top 10 Positions -->
        <div class="card bg-base-100 border border-base-300 p-3.5 rounded-xl shadow-xs border-l-4 border-l-cyan-500">
            <div class="flex items-center justify-between text-base-content/60 text-xs mb-1">
                <span class="font-semibold text-cyan-600 dark:text-cyan-400">Page 1 (#4 - #10)</span>
                <i data-lucide="check-circle" class="w-3.5 h-3.5 text-cyan-500"></i>
            </div>
            <div class="text-2xl font-black text-cyan-600 dark:text-cyan-400 font-mono">{{ number_format($kpis['top10']) }}</div>
            <div class="text-[10px] text-cyan-600/70 dark:text-cyan-400/70 mt-0.5">First page results</div>
        </div>

        <!-- 4. Striking Distance (11-50) -->
        <div class="card bg-base-100 border border-base-300 p-3.5 rounded-xl shadow-xs border-l-4 border-l-amber-500">
            <div class="flex items-center justify-between text-base-content/60 text-xs mb-1">
                <span class="font-semibold text-amber-600 dark:text-amber-400">Striking (11 - 50)</span>
                <i data-lucide="trending-up" class="w-3.5 h-3.5 text-amber-500"></i>
            </div>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">{{ number_format($kpis['striking']) }}</div>
            <div class="text-[10px] text-amber-600/70 dark:text-amber-400/70 mt-0.5">Optimization targets</div>
        </div>

        <!-- 5. Unranked (> 50) -->
        <div class="card bg-base-100 border border-base-300 p-3.5 rounded-xl shadow-xs border-l-4 border-l-slate-400">
            <div class="flex items-center justify-between text-base-content/60 text-xs mb-1">
                <span class="font-semibold text-slate-500">Unranked (> 50)</span>
                <i data-lucide="eye-off" class="w-3.5 h-3.5 text-slate-400"></i>
            </div>
            <div class="text-2xl font-black text-base-content/60 font-mono">{{ number_format($kpis['unranked']) }}</div>
            <div class="text-[10px] text-base-content/50 mt-0.5">Beyond top 50</div>
        </div>

        <!-- 6. Average Rank -->
        <div class="card bg-base-100 border border-base-300 p-3.5 rounded-xl shadow-xs border-l-4 border-l-primary">
            <div class="flex items-center justify-between text-base-content/60 text-xs mb-1">
                <span class="font-semibold text-primary">Avg Ranked Pos</span>
                <i data-lucide="bar-chart-2" class="w-3.5 h-3.5 text-primary"></i>
            </div>
            <div class="text-2xl font-black text-primary font-mono">{{ $kpis['avg_position'] > 0 ? '#'.$kpis['avg_position'] : '—' }}</div>
            <div class="text-[10px] text-base-content/50 mt-0.5">Across ranked keywords</div>
        </div>
    </div>    <!-- Main Live Search & Check Studio -->
    <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-5 sm:p-6 space-y-5">
        <!-- Studio Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-base-300">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold shadow-xs">
                    <i data-lucide="sparkles" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-black text-base-content leading-tight">Live SERP Rank Scanner</h2>
                        <span class="badge badge-primary badge-outline badge-xs font-mono font-bold uppercase tracking-wider">Top 50 Depth</span>
                    </div>
                    <p class="text-xs text-base-content/60 mt-0.5">Real-time Google organic position tracker, canonical geotargeting & competitor intelligence</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if(!$credits['has_key'])
                    <a href="{{ route('settings.index') }}" class="btn btn-warning btn-xs gap-1 font-bold">
                        <i data-lucide="key" class="w-3.5 h-3.5"></i> Setup SerpApi Key
                    </a>
                @else
                    <span class="badge badge-success badge-sm font-mono gap-1 text-[11px] font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                        SerpApi Ready
                    </span>
                @endif
            </div>
        </div>

        <form id="rank-check-form" class="space-y-4">
            @csrf

            <!-- Section 1: Target Domain & Market Profile (4 Balanced Columns) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                <!-- 1. Target Domain & Existing Client Selector -->
                <div>
                    <div class="flex items-center justify-between py-1">
                        <label class="text-xs font-bold text-base-content flex items-center gap-1.5">
                            <i data-lucide="globe" class="w-3.5 h-3.5 text-primary"></i>
                            Target Domain <span class="text-error">*</span>
                        </label>
                        @if($allClients->count() > 0)
                            <select id="client_quick_select" name="client_id" onchange="handleClientQuickSelect(this)" class="select select-bordered select-xs text-[10px] h-6 min-h-6 bg-base-200/80 border-base-300 max-w-[150px] font-semibold" title="Auto-fill domain from existing client">
                                <option value="" data-domain="">Custom / Generic</option>
                                @foreach($allClients as $c)
                                    @php
                                        $cleanUrl = preg_replace('#^https?://#i', '', rtrim($c->website_url, '/'));
                                    @endphp
                                    <option value="{{ $c->id }}" data-domain="{{ $cleanUrl }}">
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="relative">
                        <input type="text" name="target_domain" id="target_domain_input"
                               value="{{ old('target_domain', $prefillDomain) }}"
                               placeholder="geimshospital.com or sub.domain.com"
                               class="input input-bordered input-sm w-full bg-base-200/50 text-xs font-mono font-medium focus:input-primary transition-all"
                               required />
                    </div>
                    <span class="text-[10px] text-base-content/50 mt-1 block">
                        Matches all ranking subpages (e.g. <code>domain.com/*</code>)
                    </span>
                </div>

                <!-- 2. Match Strategy -->
                <div>
                    <label class="label py-1 text-xs font-bold text-base-content flex items-center gap-1.5">
                        <i data-lucide="crosshair" class="w-3.5 h-3.5 text-indigo-400"></i>
                        Match Strategy
                    </label>
                    <select name="match_type" id="match_type_select" onchange="toggleExactUrlField(this.value)" class="select select-bordered select-sm w-full bg-base-200/50 text-xs font-medium focus:select-primary transition-all">
                        <option value="domain" selected>Domain-Wide (Any subpage)</option>
                        <option value="exact_url">Exact URL (Specific landing page)</option>
                    </select>
                    <span class="text-[10px] text-base-content/50 mt-1 block">
                        Switch to Exact URL for individual articles
                    </span>
                </div>

                <!-- 3. Target Country (gl - Default India) -->
                <div>
                    <label class="label py-1 text-xs font-bold text-base-content flex items-center gap-1.5">
                        <i data-lucide="map" class="w-3.5 h-3.5 text-amber-500"></i>
                        Target Country (gl)
                    </label>
                    <select name="country" class="select select-bordered select-sm w-full bg-base-200/50 text-xs font-medium focus:select-primary transition-all">
                        @foreach($countries as $code => $cName)
                            <option value="{{ $code }}" {{ $code === 'in' ? 'selected' : '' }}>
                                {{ $cName }} ({{ strtoupper($code) }})
                            </option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-base-content/50 mt-1 block">
                        Google country index edition
                    </span>
                </div>

                <!-- 4. Device Platform (Segmented Pill Switcher) -->
                <div>
                    <label class="label py-1 text-xs font-bold text-base-content flex items-center gap-1.5">
                        <i data-lucide="layers" class="w-3.5 h-3.5 text-cyan-500"></i>
                        Device Platform
                    </label>
                    <div class="grid grid-cols-2 gap-1 p-1 bg-base-200/70 rounded-lg border border-base-300">
                        <label class="flex items-center justify-center gap-1.5 py-1 px-2.5 rounded-md text-xs font-semibold cursor-pointer transition-all has-[:checked]:bg-primary has-[:checked]:text-primary-content has-[:checked]:shadow-xs text-base-content/70 hover:text-base-content select-none">
                            <input type="radio" name="device" value="desktop" checked class="sr-only device-radio" />
                            <i data-lucide="laptop" class="w-3.5 h-3.5"></i>
                            <span>Desktop</span>
                        </label>
                        <label class="flex items-center justify-center gap-1.5 py-1 px-2.5 rounded-md text-xs font-semibold cursor-pointer transition-all has-[:checked]:bg-primary has-[:checked]:text-primary-content has-[:checked]:shadow-xs text-base-content/70 hover:text-base-content select-none">
                            <input type="radio" name="device" value="mobile" class="sr-only device-radio" />
                            <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                            <span>Mobile</span>
                        </label>
                    </div>
                    <span class="text-[10px] text-base-content/50 mt-1 block">
                        Emulates mobile vs desktop SERP layout
                    </span>
                </div>
            </div>

            <!-- Optional Exact URL Container (Expands when exact_url is selected) -->
            <div id="exact-url-container" class="hidden p-3 bg-base-200/60 rounded-xl border border-base-300 transition-all">
                <label class="label py-0.5 text-xs font-bold text-base-content flex items-center gap-1.5">
                    <i data-lucide="link" class="w-3.5 h-3.5 text-primary"></i> Exact Landing Page URL <span class="text-error">*</span>
                </label>
                <div class="relative mt-1">
                    <i data-lucide="external-link" class="w-4 h-4 absolute left-3 top-2.5 text-base-content/40"></i>
                    <input type="url" name="target_url" placeholder="https://example.com/blog/cardiac-specialist-guide"
                           class="input input-bordered input-sm w-full pl-9 bg-base-100 text-xs font-mono focus:input-primary" />
                </div>
                <span class="text-[10px] text-base-content/50 mt-1 block">
                    Only results matching this exact URL will be recorded as your rank position.
                </span>
            </div>

            <!-- Section 2: Regional Geotarget & Environment Precision -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 items-start">
                <!-- City / Canonical Geotarget (Span 2 on sm & lg: matches Domain + Strategy above) -->
                <div class="col-span-1 sm:col-span-2 relative">
                    <div class="flex items-center justify-between">
                        <label class="label py-1 text-xs font-bold text-base-content flex items-center gap-1.5">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500"></i>
                            <span>City / Canonical Location</span>
                        </label>
                        <span id="canonical-status-pill" class="hidden badge badge-success badge-xs font-mono text-[9px] gap-1">
                            <i data-lucide="check-check" class="w-2.5 h-2.5"></i> Canonical Geotarget
                        </span>
                    </div>
                    <div class="relative">
                        <i data-lucide="map-pin" class="w-4 h-4 absolute left-3 top-2.5 text-base-content/40"></i>
                        <input type="text" id="location_input" name="location" autocomplete="off"
                               placeholder="e.g. Ahmedabad, New York, London, Tokyo..."
                               class="input input-bordered input-sm w-full pl-9 pr-8 bg-base-200/50 text-xs font-medium focus:input-primary transition-all"
                               oninput="handleLocationInput(this)" />
                        <span id="loc-spinner" class="hidden loading loading-spinner loading-xs absolute right-3 top-2.5 text-primary"></span>
                    </div>
                    <!-- Dropdown suggestions popup -->
                    <div id="location-suggestions" class="hidden absolute z-50 left-0 right-0 mt-1 bg-base-100 border border-base-300 rounded-xl shadow-2xl max-h-52 overflow-y-auto divide-y divide-base-200 text-xs"></div>
                    <span class="text-[10px] text-base-content/50 mt-1 block">
                        Auto-resolves to Google Canonical Geotarget (leave blank for national search)
                    </span>
                </div>

                <!-- Search Language (Span 1: matches Country above) -->
                <div class="col-span-1">
                    <label class="label py-1 text-xs font-bold text-base-content flex items-center gap-1.5">
                        <i data-lucide="languages" class="w-3.5 h-3.5 text-indigo-400"></i>
                        <span>Search Language (hl)</span>
                    </label>
                    <select name="language" class="select select-bordered select-sm w-full bg-base-200/50 text-xs font-medium focus:select-primary transition-all">
                        <option value="en" selected>English (en)</option>
                        <option value="es">Spanish (es)</option>
                        <option value="fr">French (fr)</option>
                        <option value="de">German (de)</option>
                        <option value="it">Italian (it)</option>
                        <option value="pt">Portuguese (pt)</option>
                        <option value="ja">Japanese (ja)</option>
                        <option value="hi">Hindi (hi)</option>
                    </select>
                    <span class="text-[10px] text-base-content/50 mt-1 block">
                        SERP user interface language
                    </span>
                </div>

                <!-- Agency Client Assignment (Span 1: matches Device above) -->
                @if($allClients->count() > 0)
                <div class="col-span-1">
                    <label class="label py-1 text-xs font-bold text-base-content flex items-center gap-1.5">
                        <i data-lucide="building-2" class="w-3.5 h-3.5 text-primary"></i>
                        <span>Agency Client</span>
                    </label>
                    <select name="client_id" class="select select-bordered select-sm w-full bg-base-200/50 text-xs font-medium focus:select-primary transition-all">
                        <option value="">None (Generic Run)</option>
                        @foreach($allClients as $client)
                            <option value="{{ $client->id }}" {{ $activeClient && $activeClient->id === $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-base-content/50 mt-1 block">
                        Assign keyword records to client portfolio
                    </span>
                </div>
                @else
                <div class="col-span-1">
                    <label class="label py-1 text-xs font-bold text-base-content flex items-center gap-1.5">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                        <span>Caching TTL</span>
                    </label>
                    <div class="input input-bordered input-sm w-full bg-base-200/30 text-xs font-mono flex items-center justify-between text-base-content/70 cursor-not-allowed">
                        <span>24 Hours (Smart Cache)</span>
                        <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                    </div>
                    <span class="text-[10px] text-base-content/50 mt-1 block">
                        Reduces unnecessary SerpApi credit usage
                    </span>
                </div>
                @endif
            </div>

            <!-- Section 3: Target Keywords Bulk Workspace (Full Width) -->
            <div class="bg-base-200/40 rounded-xl p-3.5 border border-base-300 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="list-filter" class="w-4 h-4 text-primary"></i>
                        <label class="text-xs font-bold text-base-content">
                            Target Keywords (Single or Bulk) <span class="text-error">*</span>
                        </label>
                        <span class="text-[11px] text-base-content/50 hidden sm:inline">• Scans Google Top 50 organic results per query</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="keyword-count-badge" class="badge badge-primary badge-outline badge-xs font-mono font-bold">0 keywords</span>
                        <span class="text-[10px] text-base-content/50 font-mono">Max 25 batch</span>
                    </div>
                </div>
                <textarea name="keywords" id="keywords_input" rows="3"
                          placeholder="Enter search keywords to scan (one keyword per line, or comma-separated)&#10;e.g.&#10;best cardiac surgeon in ahmedabad&#10;top cardiology hospital in ahmedabad&#10;heart specialist near me"
                          class="textarea textarea-bordered w-full bg-base-100 text-xs font-mono focus:textarea-primary leading-relaxed shadow-xs"
                          oninput="updateKeywordCounter(this)" required></textarea>
                <div class="flex flex-wrap items-center justify-between text-[11px] text-base-content/60 pt-0.5">
                    <span>Multi-page crawler automatically traverses Page 1 through Page 5 (Ranks 1 to 50).</span>
                    <span class="font-mono text-[10px] text-base-content/40">Separators: newline or comma</span>
                </div>
            </div>

            <!-- Section 4: Action & Status Footer Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-base-300">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Status Text with animated pulsing indicator -->
                    <div id="check-status-text" class="text-xs text-base-content/70 flex items-center gap-2">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="font-medium">Ready to scan. Fast cached results for repeated queries within 24h.</span>
                    </div>

                    <!-- Force Live Refresh Switch -->
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-base-content/80 hover:text-base-content px-2.5 py-1 rounded-lg bg-base-200/70 border border-base-300 hover:bg-base-200 transition-colors select-none">
                        <input type="checkbox" name="force_refresh" value="1" class="checkbox checkbox-primary checkbox-xs rounded" />
                        <span class="flex items-center gap-1 font-semibold text-[11px]">
                            <i data-lucide="zap" class="w-3.5 h-3.5 text-warning"></i> Force Live Refresh
                        </span>
                    </label>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-auto">
                    <button type="button" onclick="executeRankCheck(this)" class="btn btn-primary btn-sm font-bold shadow-md shadow-primary/25 px-6 gap-2 hover:brightness-110 active:scale-95 transition-all">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        <span>Scan Google Rankings (Top 50)</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card bg-base-100 border border-base-300 shadow-sm rounded-xl p-3">
        <form method="GET" action="{{ route('rank-tracker.index') }}" class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <!-- Search Keyword / Domain -->
                <div class="relative min-w-48 flex-1 max-w-xs">
                    <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-2.5 text-base-content/40"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Filter keyword or domain..."
                           class="input input-bordered input-xs pl-8 w-full bg-base-200/50 text-xs" />
                </div>

                <!-- Rank Position Filter -->
                <select name="rank_filter" onchange="this.form.submit()" class="select select-bordered select-xs bg-base-200/50 text-xs">
                    <option value="" {{ empty($rankFilter) ? 'selected' : '' }}>All Rank Tiers</option>
                    <option value="top3" {{ $rankFilter === 'top3' ? 'selected' : '' }}>Top 3 Podiums (#1-#3)</option>
                    <option value="top10" {{ $rankFilter === 'top10' ? 'selected' : '' }}>Page 1 (#1-#10)</option>
                    <option value="striking" {{ $rankFilter === 'striking' ? 'selected' : '' }}>Striking Distance (#11-#50)</option>
                    <option value="unranked" {{ $rankFilter === 'unranked' ? 'selected' : '' }}>Unranked (> 50)</option>
                </select>

                <!-- Client Filter -->
                @if($allClients->count() > 0)
                    <select name="client_id" onchange="this.form.submit()" class="select select-bordered select-xs bg-base-200/50 text-xs">
                        <option value="" {{ empty($clientId) || $clientId === 'all' ? 'selected' : '' }}>All Clients & Generic</option>
                        <option value="none" {{ $clientId === 'none' ? 'selected' : '' }}>Generic Runs (No Client)</option>
                        @foreach($allClients as $c)
                            <option value="{{ $c->id }}" {{ $clientId == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <!-- User Filter (Admin Only) -->
                @if($isAdmin && $allUsers->count() > 0)
                    <select name="user_id" onchange="this.form.submit()" class="select select-bordered select-xs bg-base-200/50 text-xs">
                        <option value="all" {{ empty($filterUserId) || $filterUserId === 'all' ? 'selected' : '' }}>All Users</option>
                        @foreach($allUsers as $u)
                            <option value="{{ $u->id }}" {{ ($filterUserId ?? '') == $u->id ? 'selected' : '' }}>
                                User: {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <!-- View Mode Segmented Switcher -->
                <div class="join border border-base-300 rounded-lg p-0.5 bg-base-200/50">
                    <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'batches']) }}" class="btn btn-xs join-item {{ ($viewMode ?? 'batches') === 'batches' ? 'btn-primary font-bold shadow-xs' : 'btn-ghost text-base-content/70' }}" title="Group by Scan Runs / Batches">
                        <i data-lucide="layers" class="w-3 h-3"></i> Scan Batches
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'keywords']) }}" class="btn btn-xs join-item {{ ($viewMode ?? 'batches') === 'keywords' ? 'btn-primary font-bold shadow-xs' : 'btn-ghost text-base-content/70' }}" title="View individual keyword checks">
                        <i data-lucide="list" class="w-3 h-3"></i> All Keywords
                    </a>
                </div>

                @if(!empty($search) || !empty($rankFilter) || (!empty($clientId) && $clientId !== 'all') || (!empty($filterUserId) && $filterUserId !== 'all'))
                    <a href="{{ route('rank-tracker.index') }}" class="btn btn-ghost btn-xs text-xs">Reset</a>
                @endif
                <button type="submit" class="btn btn-outline btn-xs gap-1 border-base-300">
                    <i data-lucide="filter" class="w-3 h-3"></i> Apply
                </button>
            </div>
        </form>
    </div>

    <!-- Ranking Results Table -->
    <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            @if(($viewMode ?? 'batches') === 'batches')
                <!-- 1. BATCH-CENTRIC TABLE (1 Row per Scan Run) -->
                <table class="table table-sm w-full" id="batches-table">
                    <thead class="bg-base-200/70 text-[11px] uppercase tracking-wider text-base-content/60 border-b border-base-300 font-bold">
                        <tr>
                            <th class="w-28 text-center">Actions</th>
                            <th>Batch Run</th>
                            <th>Target Domain & Client</th>
                            <th>Keywords Included</th>
                            <th>Geo & Device</th>
                            <th>SERP Breakdown</th>
                            <th>Best Rank</th>
                            @if($isAdmin)
                                <th>Run By</th>
                            @endif
                            <th>Scanned</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-200 text-xs font-medium" id="batches-table-body">
                        @forelse($batches as $b)
                            <tr class="hover:bg-base-200/40 transition-colors" id="batch-row-{{ $b->batch_id }}">
                                <!-- Column 1 Icon-Only Actions (Rule 8 Compliance) -->
                                <td class="text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" onclick="inspectBatch('{{ $b->batch_id }}')" class="btn btn-square btn-ghost btn-xs text-primary" title="Inspect All Keywords in this Batch">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>
                                        @can('export-rank-data')
                                        <a href="{{ route('rank-tracker.export.batch.csv', $b->batch_id) }}" class="btn btn-square btn-ghost btn-xs text-info hover:text-info" title="Download CSV (All {{ $b->total_keywords }} Keywords in this Batch)">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        </a>
                                        @endcan
                                        @can('delete-rank-data')
                                        <button type="button" onclick="deleteBatchRecord('{{ $b->batch_id }}')" class="btn btn-square btn-ghost btn-xs text-error" title="Delete This Entire Batch">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>

                                <!-- Batch Run ID & Count -->
                                <td>
                                    <div class="font-mono font-bold text-xs text-base-content flex items-center gap-1.5">
                                        <span>#{{ $b->batch_id }}</span>
                                    </div>
                                    <span class="badge badge-primary badge-outline badge-xs font-mono font-bold mt-0.5">
                                        {{ $b->total_keywords }} Keyword{{ $b->total_keywords > 1 ? 's' : '' }}
                                    </span>
                                </td>

                                <!-- Target Domain & Client -->
                                <td>
                                    <div class="font-mono text-xs font-semibold text-base-content">{{ $b->target_domain }}</div>
                                    @if($b->client)
                                        <span class="text-[10px] text-base-content/60 font-sans block">{{ $b->client->name }}</span>
                                    @else
                                        <span class="text-[10px] text-base-content/40 font-sans block italic">Generic Run</span>
                                    @endif
                                </td>

                                <!-- Keywords Included Preview -->
                                <td class="max-w-xs">
                                    <span class="text-xs text-base-content/80 line-clamp-1" title="{{ $b->keywords_preview }}">
                                        {{ $b->keywords_preview }}
                                    </span>
                                </td>

                                <!-- Geo & Device -->
                                <td>
                                    <div class="flex items-center gap-1.5 text-xs text-base-content/70">
                                        <span class="badge badge-ghost badge-xs font-mono font-bold uppercase">{{ $b->country }}</span>
                                        <span class="text-[11px] font-mono">{{ $b->device === 'mobile' ? 'Mobile' : 'Desktop' }}</span>
                                    </div>
                                    @if(!empty($b->location))
                                        <span class="text-[10px] text-base-content/70 font-mono block truncate max-w-36">
                                            <i data-lucide="map-pin" class="w-2.5 h-2.5 inline text-emerald-500"></i> {{ $b->location }}
                                        </span>
                                    @endif
                                </td>

                                <!-- SERP Breakdown Pills -->
                                <td>
                                    <div class="flex flex-wrap items-center gap-1 font-mono text-[10px]">
                                        @if($b->top3_count > 0)
                                            <span class="badge badge-success text-white badge-xs font-bold px-1.5 py-0.5" title="Top 3 podium positions">
                                                Top 3: {{ $b->top3_count }}
                                            </span>
                                        @endif
                                        @if($b->top10_count > 0)
                                            <span class="badge badge-info text-white badge-xs font-bold px-1.5 py-0.5" title="Page 1 positions (#4-#10)">
                                                Page 1: {{ $b->top10_count }}
                                            </span>
                                        @endif
                                        @if($b->striking_count > 0)
                                            <span class="badge badge-warning text-white badge-xs font-bold px-1.5 py-0.5" title="Striking distance (#11-#50)">
                                                11-50: {{ $b->striking_count }}
                                            </span>
                                        @endif
                                        @if($b->unranked_count > 0)
                                            <span class="badge badge-ghost text-base-content/60 badge-xs px-1.5 py-0.5" title="Beyond top 50">
                                                > 50: {{ $b->unranked_count }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Best Rank in Batch -->
                                <td>
                                    @if($b->best_position)
                                        <span class="badge badge-primary font-mono font-bold text-xs px-2 py-0.5">
                                            #{{ $b->best_position }}
                                        </span>
                                    @else
                                        <span class="badge badge-ghost text-base-content/50 font-mono text-[10px]">
                                            > 50
                                        </span>
                                    @endif
                                </td>

                                <!-- Run By (Admin only) -->
                                @if($isAdmin)
                                    <td>
                                        <span class="badge badge-ghost badge-sm text-[11px] font-medium">
                                            {{ $b->user?->name ?? '—' }}
                                        </span>
                                    </td>
                                @endif

                                <!-- Scanned At -->
                                <td class="text-base-content/60 text-[11px] font-mono whitespace-nowrap">
                                    {{ $b->checked_at ? $b->checked_at->diffForHumans() : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 9 : 8 }}" class="text-center py-12 text-base-content/50">
                                    <i data-lucide="layers" class="w-8 h-8 mx-auto mb-2 text-base-content/30"></i>
                                    <p class="font-bold text-sm">No Scan Batches Found</p>
                                    <p class="text-xs">Enter your domain and keywords above to run your first batch.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <!-- 2. INDIVIDUAL KEYWORDS TABLE -->
                <table class="table table-sm w-full" id="rankings-table">
                    <thead class="bg-base-200/70 text-[11px] uppercase tracking-wider text-base-content/60 border-b border-base-300 font-bold">
                        <tr>
                            <th class="w-28 text-center">Actions</th>
                            <th>Keyword</th>
                            <th>Target Domain</th>
                            <th>Current Rank</th>
                            <th>Rank Delta</th>
                            <th>Ranking URL & Title</th>
                            <th>Geo / Device</th>
                            <th>Scanned</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-200 text-xs font-medium" id="rankings-table-body">
                        @forelse($rankChecks as $item)
                            <tr class="hover:bg-base-200/40 transition-colors" id="row-{{ $item->id }}">
                                <td class="text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" onclick="inspectSerp({{ $item->id }})" class="btn btn-square btn-ghost btn-xs text-primary" title="View Top 10 Competitor SERP">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>
                                        @can('export-rank-data')
                                        <a href="{{ route('rank-tracker.export.single.csv', $item->id) }}" class="btn btn-square btn-ghost btn-xs text-info hover:text-info" title="Download CSV for this Query">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        </a>
                                        @endcan
                                        @can('delete-rank-data')
                                        <button type="button" onclick="deleteRankRecord({{ $item->id }})" class="btn btn-square btn-ghost btn-xs text-error" title="Delete Record">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                                <td>
                                    <div class="font-bold text-base-content flex items-center gap-1.5">
                                        <span>{{ $item->keyword }}</span>
                                        @if(!empty($item->serp_features) && in_array('featured_snippet', $item->serp_features))
                                            <span class="badge badge-accent badge-xs text-[10px] font-bold">Snippet</span>
                                        @endif
                                    </div>
                                    @if($item->client)
                                        <span class="text-[10px] text-base-content/60 font-sans block">{{ $item->client->name }}</span>
                                    @else
                                        <span class="text-[10px] text-base-content/40 font-sans block italic">Generic Run</span>
                                    @endif
                                    @if($isAdmin && $item->user)
                                        <span class="text-[9px] text-primary/70 font-mono flex items-center gap-0.5 mt-0.5">
                                            <i data-lucide="user" class="w-2.5 h-2.5"></i> {{ $item->user->name }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="font-mono text-xs text-base-content/80">{{ $item->target_domain }}</span>
                                </td>
                                <td>
                                    @if($item->is_ranked && $item->position)
                                        @if($item->position <= 3)
                                            <span class="badge badge-success text-white font-mono font-bold text-xs gap-1 px-2.5 py-1">
                                                <i data-lucide="trophy" class="w-3 h-3"></i> #{{ $item->position }}
                                            </span>
                                        @elseif($item->position <= 10)
                                            <span class="badge badge-info text-white font-mono font-bold text-xs px-2.5 py-1">
                                                #{{ $item->position }}
                                            </span>
                                        @else
                                            <span class="badge badge-warning text-white font-mono font-bold text-xs px-2.5 py-1">
                                                #{{ $item->position }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge badge-ghost text-base-content/60 font-mono text-[11px] px-2 py-0.5">
                                            > 50 (Unranked)
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->rank_change === null || $item->rank_change === 999)
                                        <span class="badge badge-secondary badge-outline badge-xs font-mono font-bold">NEW</span>
                                    @elseif($item->rank_change > 0)
                                        <span class="text-emerald-600 dark:text-emerald-400 font-mono font-bold flex items-center gap-0.5">
                                            <i data-lucide="arrow-up" class="w-3 h-3"></i> +{{ $item->rank_change }}
                                        </span>
                                    @elseif($item->rank_change < 0)
                                        <span class="text-rose-600 dark:text-rose-400 font-mono font-bold flex items-center gap-0.5">
                                            <i data-lucide="arrow-down" class="w-3 h-3"></i> {{ $item->rank_change }}
                                        </span>
                                    @else
                                        <span class="text-base-content/40 font-mono text-xs">= 0</span>
                                    @endif
                                </td>
                                <td class="max-w-xs truncate">
                                    @if(!empty($item->ranking_url))
                                        <a href="{{ $item->ranking_url }}" target="_blank" rel="noopener noreferrer" class="link link-hover text-primary font-medium flex items-center gap-1 truncate" title="{{ $item->ranking_title }}">
                                            <span class="truncate">{{ $item->ranking_title ?? $item->ranking_url }}</span>
                                            <i data-lucide="external-link" class="w-3 h-3 shrink-0"></i>
                                        </a>
                                        <span class="text-[10px] text-base-content/50 font-mono block truncate">{{ $item->ranking_url }}</span>
                                    @else
                                        <span class="text-base-content/40 italic">Not found in top 50</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-1.5 text-xs text-base-content/70">
                                        <span class="badge badge-ghost badge-xs font-mono font-bold uppercase">{{ $item->country }}</span>
                                        <span class="text-[11px] font-mono">{{ $item->device }}</span>
                                    </div>
                                    @if(!empty($item->location))
                                        <span class="text-[10px] text-base-content/70 font-mono block truncate max-w-36">
                                            {{ $item->location }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-base-content/60 text-[11px] font-mono whitespace-nowrap">
                                    {{ $item->checked_at ? $item->checked_at->diffForHumans() : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-12 text-base-content/50">
                                    <p class="font-bold text-sm">No Keyword Checks Found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        @if((($viewMode ?? 'batches') === 'batches' ? $batches->hasPages() : $rankChecks->hasPages()))
            <div class="p-3 border-t border-base-300 flex justify-end">
                {{ ($viewMode ?? 'batches') === 'batches' ? $batches->links() : $rankChecks->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Top 10 SERP Competitor Inspector -->
<dialog id="serp_modal" class="modal">
    <div class="modal-box max-w-3xl border border-base-300 p-0 rounded-2xl overflow-hidden shadow-2xl">
        <!-- Modal Header -->
        <div class="p-5 bg-base-200/80 border-b border-base-300 flex items-center justify-between">
            <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                    <span class="badge badge-primary badge-xs font-mono uppercase">SERP Inspector</span>
                    <span id="modal-geo-badge" class="badge badge-ghost badge-xs font-mono">US</span>
                    <span id="modal-location-badge" class="hidden badge badge-neutral badge-xs font-mono gap-1 text-[11px]">
                        <i data-lucide="map-pin" class="w-3 h-3 text-emerald-400"></i>
                        <span id="modal-location-text"></span>
                    </span>
                </div>
                <h3 class="text-base font-bold text-base-content" id="modal-kw-title">Keyword Competitors</h3>
                <p class="text-xs text-base-content/60">Live Top 10 Google Organic Results</p>
            </div>
            <button type="button" onclick="document.getElementById('serp_modal').close()" class="btn btn-ghost btn-circle btn-sm">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Modal Body: Competitors List -->
        <div class="p-5 max-h-[70vh] overflow-y-auto space-y-3" id="serp-competitors-list">
            <div class="text-center py-8">
                <span class="loading loading-spinner loading-md text-primary"></span>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-base-200/50 border-t border-base-300 flex justify-end">
            <button type="button" onclick="document.getElementById('serp_modal').close()" class="btn btn-ghost btn-sm font-semibold">Close</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<!-- Modal: Scan Batch Keywords Inspector -->
<dialog id="batch_modal" class="modal">
    <div class="modal-box max-w-4xl border border-base-300 p-0 rounded-2xl overflow-hidden shadow-2xl bg-base-100">
        <!-- Modal Header -->
        <div class="p-5 bg-base-200/80 border-b border-base-300 flex items-center justify-between">
            <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                    <span class="badge badge-primary badge-xs font-mono uppercase">Scan Batch Run</span>
                    <span id="batch-modal-id-badge" class="badge badge-ghost badge-xs font-mono font-bold">#BATCH</span>
                    <span id="batch-modal-geo-badge" class="badge badge-neutral badge-xs font-mono">IN • Desktop</span>
                </div>
                <h3 class="text-base font-bold text-base-content" id="batch-modal-title">Batch Keywords</h3>
                <p class="text-xs text-base-content/60" id="batch-modal-subtitle">Target Domain</p>
            </div>
            <div class="flex items-center gap-2">
                @can('export-rank-data')
                <a id="batch-modal-download-btn" href="#" class="btn btn-sm btn-info text-white font-bold gap-1.5 shadow-xs">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>Download Batch CSV</span>
                </a>
                @endcan
                <button type="button" onclick="document.getElementById('batch_modal').close()" class="btn btn-ghost btn-circle btn-sm">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Mini Stats Bar inside Modal -->
        <div class="px-5 py-3 bg-base-200/40 border-b border-base-300 flex flex-wrap items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2">
                <span class="text-base-content/60">Results:</span>
                <span id="batch-modal-top3-pill" class="badge badge-success text-white badge-xs font-mono font-bold">Top 3: 0</span>
                <span id="batch-modal-top10-pill" class="badge badge-info text-white badge-xs font-mono font-bold">Page 1: 0</span>
                <span id="batch-modal-striking-pill" class="badge badge-warning text-white badge-xs font-mono font-bold">11-50: 0</span>
                <span id="batch-modal-unranked-pill" class="badge badge-ghost badge-xs font-mono">> 50: 0</span>
            </div>
            <span id="batch-modal-timestamp" class="text-base-content/50 font-mono text-[11px]"></span>
        </div>

        <!-- Modal Body: Keywords List -->
        <div class="p-5 max-h-[60vh] overflow-y-auto">
            <div class="table-responsive overflow-x-auto">
                <table class="table table-xs w-full">
                    <thead class="bg-base-200/60 font-bold uppercase text-[10px] text-base-content/60">
                        <tr>
                            <th class="w-20">Position</th>
                            <th>Keyword</th>
                            <th class="w-20">Delta</th>
                            <th>Ranking Landing Page URL</th>
                        </tr>
                    </thead>
                    <tbody id="batch-keywords-list" class="divide-y divide-base-200 font-medium">
                        <!-- Loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('scripts')
<script>
    function handleClientQuickSelect(sel) {
        const selectedOption = $(sel).find('option:selected');
        const domain = selectedOption.data('domain');
        const clientId = sel.value;

        if (domain) {
            $('#target_domain_input').val(domain);
            $('#client_quick_select').val(clientId);
            showToast('Auto-filled client domain: ' + domain, 'info');
        } else {
            $('#client_quick_select').val('');
            showToast('Switched to Custom / Generic mode (No client).', 'info');
        }
    }

    function updateKeywordCounter(textarea) {
        const text = textarea.value.trim();
        if (!text) {
            $('#keyword-count-badge').text('0 keywords');
            return;
        }
        const lines = text.split(/[\r\n,]+/).filter(k => k.trim().length > 0);
        $('#keyword-count-badge').text(lines.length + ' keyword' + (lines.length > 1 ? 's' : ''));
    }

    function toggleExactUrlField(mode) {
        if (mode === 'exact_url') {
            $('#exact-url-container').removeClass('hidden');
        } else {
            $('#exact-url-container').addClass('hidden');
        }
    }

    function refreshLiveCredits(btn) {
        $(btn).addClass('animate-spin');
        $.ajax({
            url: "{{ route('rank-tracker.credits') }}",
            type: 'GET',
            success: function(res) {
                $(btn).removeClass('animate-spin');
                if (res.success) {
                    $('#credits-left-val').text(res.searches_left);
                    showToast('SerpApi Credits refreshed: ' + res.searches_left + ' remaining', 'info');
                } else {
                    showToast(res.error || 'Failed to refresh credits.', 'warning');
                }
            },
            error: function() {
                $(btn).removeClass('animate-spin');
                showToast('Could not reach SerpApi credits API.', 'error');
            }
        });
    }

    function executeRankCheck(btn) {
        const form = $('#rank-check-form');
        const domain = $('#target_domain_input').val().trim();
        const keywords = $('#keywords_input').val().trim();

        if (!domain) {
            showToast('Target domain is required.', 'warning');
            $('#target_domain_input').focus();
            return;
        }
        if (!keywords) {
            showToast('Please enter at least one keyword.', 'warning');
            $('#keywords_input').focus();
            return;
        }

        $(btn).attr('disabled', 'disabled');
        const origBtnHtml = $(btn).html();
        $(btn).html('<span class="loading loading-spinner loading-xs me-1"></span> Scanning Google Top 50...');
        $('#check-status-text').html('<span class="loading loading-dots loading-xs text-primary"></span> Querying SerpApi Google Organic results...');

        $.ajax({
            url: "{{ route('rank-tracker.check') }}",
            type: 'POST',
            data: form.serialize(),
            success: function(res) {
                $(btn).removeAttr('disabled').html(origBtnHtml);
                if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
                $('#check-status-text').html('<span class="w-2 h-2 rounded-full bg-emerald-500"></span> ' + (res.message || 'Scan completed!'));

                if (res.success) {
                    showToast(res.message, 'success');
                    if (res.credits && res.credits.searches_left !== undefined) {
                        $('#credits-left-val').text(res.credits.searches_left);
                    }
                    setTimeout(() => {
                        window.location.href = "{{ route('rank-tracker.index') }}";
                    }, 800);
                } else {
                    showToast(res.message || 'Check encountered errors.', 'warning');
                }
            },
            error: function(xhr) {
                $(btn).removeAttr('disabled').html(origBtnHtml);
                if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
                $('#check-status-text').html('<span class="w-2 h-2 rounded-full bg-rose-500"></span> Check failed.');
                const msg = xhr.responseJSON?.message || 'Check failed. Please verify your SerpApi key and try again.';
                showToast(msg, 'error');
            }
        });
    }

    function inspectSerp(id) {
        document.getElementById('serp_modal').showModal();
        $('#serp-competitors-list').html('<div class="text-center py-8"><span class="loading loading-spinner loading-md text-primary"></span><p class="text-xs text-base-content/60 mt-2">Loading live SERP landscape...</p></div>');

        $.ajax({
            url: "/rank-tracker/" + id + "/serp",
            type: 'GET',
            success: function(res) {
                $('#modal-kw-title').text('"' + res.keyword + '" — Target: ' + res.target_domain);
                $('#modal-geo-badge').text(res.country + ' • ' + (res.device || 'desktop'));

                if (res.location) {
                    $('#modal-location-text').text(res.location);
                    $('#modal-location-badge').removeClass('hidden').attr('title', 'Google Canonical Geotarget: ' + res.location);
                } else {
                    $('#modal-location-badge').addClass('hidden');
                }

                if (!res.competitors || res.competitors.length === 0) {
                    $('#serp-competitors-list').html('<div class="text-center py-6 text-base-content/50 text-xs">No competitor snapshot saved for this record.</div>');
                    return;
                }

                let html = '<div class="space-y-2.5">';
                res.competitors.forEach(function(comp) {
                    const isTarget = comp.is_target;
                    const highlightClass = isTarget ? 'border-primary/50 bg-primary/5 shadow-xs ring-1 ring-primary/30' : 'border-base-300 bg-base-100';
                    const posColor = comp.position <= 3 ? 'badge-success text-white' : (comp.position <= 10 ? 'badge-info text-white' : 'badge-ghost');

                    html += `
                        <div class="p-3 rounded-xl border ${highlightClass} transition-all">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="badge ${posColor} font-mono font-bold text-xs px-2">#${comp.position}</span>
                                    <span class="font-mono text-xs font-semibold text-base-content/80">${comp.domain}</span>
                                    ${isTarget ? '<span class="badge badge-primary badge-xs font-bold uppercase tracking-wider">Your Target</span>' : ''}
                                </div>
                                <a href="${comp.link}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-circle btn-xs text-base-content/50 hover:text-primary">
                                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                            <div class="font-bold text-xs text-base-content">
                                <a href="${comp.link}" target="_blank" rel="noopener noreferrer" class="hover:text-primary hover:underline">${comp.title}</a>
                            </div>
                            ${comp.snippet ? `<p class="text-[11px] text-base-content/60 mt-1 leading-relaxed">${comp.snippet}</p>` : ''}
                        </div>
                    `;
                });
                html += '</div>';

                $('#serp-competitors-list').html(html);
                if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
            },
            error: function() {
                $('#serp-competitors-list').html('<div class="text-center py-6 text-error text-xs">Failed to load SERP competitors.</div>');
            }
        });
    }

    function inspectBatch(batchId) {
        document.getElementById('batch_modal').showModal();
        $('#batch-modal-id-badge').text('#' + batchId);
        $('#batch-modal-download-btn').attr('href', '/rank-tracker/batch/' + batchId + '/export-csv');
        $('#batch-keywords-list').html('<tr><td colspan="4" class="text-center py-8"><span class="loading loading-spinner loading-md text-primary"></span><p class="text-xs text-base-content/60 mt-2">Loading batch keywords...</p></td></tr>');

        $.ajax({
            url: "/rank-tracker/batch/" + batchId,
            type: 'GET',
            success: function(res) {
                $('#batch-modal-title').text('Batch: ' + res.target_domain + ' (' + res.total_keywords + ' Keywords)');
                $('#batch-modal-subtitle').text('Client: ' + res.client_name + ' • ' + res.country + ' (' + res.device + ')');
                $('#batch-modal-geo-badge').text(res.country + ' • ' + res.device);

                $('#batch-modal-top3-pill').text('Top 3: ' + res.top3);
                $('#batch-modal-top10-pill').text('Page 1: ' + res.top10);
                $('#batch-modal-striking-pill').text('11-50: ' + res.striking);
                $('#batch-modal-unranked-pill').text('> 50: ' + res.unranked);
                $('#batch-modal-timestamp').text('Scanned: ' + res.scanned_ago);

                if (!res.keywords || res.keywords.length === 0) {
                    $('#batch-keywords-list').html('<tr><td colspan="4" class="text-center py-6 text-base-content/50">No keywords found in this batch.</td></tr>');
                    return;
                }

                let html = '';
                res.keywords.forEach(function(kw) {
                    const posColor = kw.is_ranked && kw.position <= 3 ? 'badge-success text-white' : (kw.is_ranked && kw.position <= 10 ? 'badge-info text-white' : (kw.is_ranked ? 'badge-warning text-white' : 'badge-ghost'));
                    const deltaHtml = kw.rank_change === null || kw.rank_change === 999 
                        ? '<span class="badge badge-secondary badge-outline badge-xs">NEW</span>'
                        : (kw.rank_change > 0 
                            ? '<span class="text-emerald-500 font-bold font-mono">+' + kw.rank_change + '</span>'
                            : (kw.rank_change < 0 
                                ? '<span class="text-rose-500 font-bold font-mono">' + kw.rank_change + '</span>'
                                : '<span class="text-base-content/40 font-mono">= 0</span>'));

                    const urlHtml = kw.ranking_url 
                        ? `<a href="${kw.ranking_url}" target="_blank" rel="noopener noreferrer" class="link link-hover text-primary truncate max-w-xs block font-mono text-[11px]" title="${kw.ranking_title || kw.ranking_url}">
                            ${kw.ranking_title || kw.ranking_url}
                           </a>`
                        : '<span class="text-base-content/40 italic">Not found in top 50</span>';

                    html += `
                        <tr class="hover:bg-base-200/40">
                            <td><span class="badge ${posColor} font-mono font-bold text-xs">${kw.display_rank}</span></td>
                            <td class="font-bold text-base-content text-xs">${escapeHtml(kw.keyword)}</td>
                            <td>${deltaHtml}</td>
                            <td>${urlHtml}</td>
                        </tr>
                    `;
                });

                $('#batch-keywords-list').html(html);
                if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
            },
            error: function() {
                $('#batch-keywords-list').html('<tr><td colspan="4" class="text-center py-6 text-error text-xs">Failed to load batch keywords.</td></tr>');
            }
        });
    }

    function deleteBatchRecord(batchId) {
        if (!confirm('Are you sure you want to delete this entire scan batch? All keyword records in it will be removed.')) return;

        $.ajax({
            url: "/rank-tracker/batch/" + batchId,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                $('#batch-row-' + batchId).fadeOut(300, function() { $(this).remove(); });
                showToast(res.message || 'Batch deleted successfully.', 'info');
            },
            error: function() {
                showToast('Failed to delete batch.', 'error');
            }
        });
    }

    function quickRecheck(keyword, domain, country, device) {
        $('#target_domain_input').val(domain);
        $('#keywords_input').val(keyword);
        $('select[name="country"]').val(country.toLowerCase());
        $('input[name="device"][value="' + device + '"]').prop('checked', true).trigger('change');
        $('input[name="force_refresh"]').prop('checked', true);
        $('html, body').animate({ scrollTop: $('#rank-check-form').offset().top - 80 }, 300);
        showToast('Loaded ' + keyword + ' for re-scan. Click "Scan Google Rankings".', 'info');
    }

    function deleteRankRecord(id) {
        if (!confirm('Are you sure you want to delete this rank check record?')) return;

        $.ajax({
            url: "/rank-tracker/" + id,
            type: 'DELETE',
            success: function(res) {
                $('#row-' + id).fadeOut(300, function() { $(this).remove(); });
                showToast(res.message || 'Record deleted successfully.', 'success');
            },
            error: function() {
                showToast('Failed to delete rank record.', 'error');
            }
        });
    }

    function confirmClearHistory() {
        if (!confirm('Are you sure you want to clear your rank check history?')) return;

        $.ajax({
            url: "{{ route('rank-tracker.clear') }}",
            type: 'POST',
            data: { client_id: "{{ $clientId }}" },
            success: function(res) {
                showToast(res.message, 'success');
                setTimeout(() => window.location.reload(), 400);
            },
            error: function() {
                showToast('Failed to clear history.', 'error');
            }
        });
    }

    let locationDebounceTimer = null;

    function handleLocationInput(input) {
        clearTimeout(locationDebounceTimer);
        const query = input.value.trim();
        const country = $('select[name="country"]').val() || 'us';
        const suggestionsBox = $('#location-suggestions');

        if (query.length < 2) {
            suggestionsBox.addClass('hidden').empty();
            $('#loc-spinner').addClass('hidden');
            $('#canonical-status-pill').addClass('hidden');
            return;
        }

        $('#loc-spinner').removeClass('hidden');

        locationDebounceTimer = setTimeout(function() {
            $.ajax({
                url: "{{ route('rank-tracker.locations') }}",
                type: 'GET',
                data: { q: query, country: country },
                success: function(locations) {
                    $('#loc-spinner').addClass('hidden');
                    if (!locations || locations.length === 0) {
                        suggestionsBox.addClass('hidden').empty();
                        return;
                    }

                    let html = '';
                    locations.forEach(function(loc) {
                        html += `
                            <button type="button" onclick="selectCanonicalLocation('${escapeHtml(loc.canonical_name)}')" class="w-full text-left px-3 py-2 hover:bg-base-200 transition-colors flex items-center justify-between text-xs group">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500 shrink-0"></i>
                                    <div>
                                        <div class="font-bold text-base-content group-hover:text-primary transition-colors">${escapeHtml(loc.canonical_name)}</div>
                                        <div class="text-[10px] text-base-content/50">${loc.target_type} • ${loc.country_code}</div>
                                    </div>
                                </div>
                                ${loc.reach ? `<span class="badge badge-ghost badge-xs font-mono text-[9px]">${loc.reach}</span>` : ''}
                            </button>
                        `;
                    });

                    suggestionsBox.html(html).removeClass('hidden');
                    if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
                },
                error: function() {
                    $('#loc-spinner').addClass('hidden');
                    suggestionsBox.addClass('hidden');
                }
            });
        }, 250);
    }

    function selectCanonicalLocation(canonicalName) {
        $('#location_input').val(canonicalName);
        $('#location-suggestions').addClass('hidden').empty();
        $('#canonical-status-pill').removeClass('hidden');
        showToast('Selected Canonical Location: ' + canonicalName, 'success');
    }

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#location_input, #location-suggestions').length) {
            $('#location-suggestions').addClass('hidden');
        }
    });

    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
</script>
@endpush
