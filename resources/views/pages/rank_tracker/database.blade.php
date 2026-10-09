@extends('layouts.app')

@section('title', 'Rank Database')
@section('page_title', 'Rank Database')
@section('page_badge', 'Historical Search Audits')

@section('content')
<div class="space-y-6">
    <!-- Header (matching Content Database layout) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="badge badge-primary badge-outline badge-sm font-mono">SERP Repository</span>
                <span class="text-xs text-base-content/60">Historical Keyword Rankings & Audit Trail</span>
            </div>
            <h1 class="text-xl font-black text-base-content mt-1">Rank Database & Search Audits</h1>
        </div>
        <div class="flex items-center gap-2">
            @can('export-rank-data')
            <a href="{{ route('rank-tracker.export.csv', request()->query()) }}" class="btn btn-outline btn-sm gap-2 border-base-300 font-semibold shadow-xs" title="Export all filtered records to CSV">
                <i data-lucide="download" class="w-4 h-4"></i> Export CSV
            </a>
            @endcan
            @can('track-ranks')
            <a href="{{ route('rank-tracker.index') }}" class="btn btn-primary btn-sm gap-2 font-bold shadow-xs">
                <i data-lucide="search" class="w-4 h-4"></i> New Live Scan
            </a>
            @endcan
        </div>
    </div>

    <!-- 5 KPI Metric Cards (Rule 13 Blueprint matching Content Database) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <!-- 1. Total Queries -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-primary/10 text-primary border border-primary/20 font-mono font-semibold rounded-full px-2 py-0.5">Archive</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Total Queries</div>
                    <div class="text-2xl font-black text-base-content mt-1 font-mono">{{ number_format($metrics['total_queries'] ?? 0) }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <i data-lucide="database" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-primary"></div>
        </div>

        <!-- 2. Top 3 Podiums -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 font-mono font-semibold rounded-full px-2 py-0.5">High CTR</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Top 3 (#1 - #3)</div>
                    <div class="text-2xl font-black text-emerald-500 mt-1 font-mono">{{ number_format($metrics['top3'] ?? 0) }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0">
                    <i data-lucide="trophy" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-emerald-500"></div>
        </div>

        <!-- 3. Page 1 (Top 10) -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-cyan-500/10 text-cyan-500 border border-cyan-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Page 1</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Top 10 (#1 - #10)</div>
                    <div class="text-2xl font-black text-cyan-500 mt-1 font-mono">{{ number_format($metrics['top10'] ?? 0) }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center shrink-0">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-cyan-500"></div>
        </div>

        <!-- 4. Striking Distance (11-50) -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-amber-500/10 text-amber-500 border border-amber-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Striking</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Striking (11 - 50)</div>
                    <div class="text-2xl font-black text-amber-500 mt-1 font-mono">{{ number_format($metrics['striking'] ?? 0) }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0">
                    <i data-lucide="trending-up" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-amber-500"></div>
        </div>

        <!-- 5. Unranked (> 50) -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-slate-500/10 text-slate-500 border border-slate-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Beyond 50</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Unranked (> 50)</div>
                    <div class="text-2xl font-black text-base-content/60 mt-1 font-mono">{{ number_format($metrics['unranked'] ?? 0) }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-slate-500/10 text-slate-500 flex items-center justify-center shrink-0">
                    <i data-lucide="eye-off" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-slate-500"></div>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-3.5">
        <form method="GET" action="{{ route('rank-tracker.database') }}" class="flex flex-wrap items-center justify-between gap-3">
            <input type="hidden" name="view_mode" value="{{ $viewMode ?? 'batches' }}" />

            <div class="flex flex-wrap items-center gap-3 flex-1">
                <!-- Search Keyword / Domain -->
                <div class="relative flex-1 min-w-[200px]">
                    <i data-lucide="search" class="w-4 h-4 text-base-content/40 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search keyword, domain, or landing page..." class="input input-bordered input-sm w-full pl-9 bg-base-200/50 text-xs rounded-lg focus:outline-none focus:border-primary" />
                </div>

                <!-- Client Filter -->
                <div class="w-44">
                    <select name="client_id" class="select select-bordered select-sm w-full bg-base-200/50 text-xs rounded-lg focus:outline-none focus:border-primary">
                        <option value="">All Clients & Generic</option>
                        <option value="none" {{ $clientId === 'none' ? 'selected' : '' }}>Generic Runs (No Client)</option>
                        @foreach($allClients as $c)
                            <option value="{{ $c->id }}" {{ $clientId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Rank Tier Filter -->
                <div class="w-40">
                    <select name="rank_filter" class="select select-bordered select-sm w-full bg-base-200/50 text-xs rounded-lg focus:outline-none focus:border-primary">
                        <option value="">All Rank Tiers</option>
                        <option value="top3" {{ $rankFilter === 'top3' ? 'selected' : '' }}>Top 3 (#1 - #3)</option>
                        <option value="top10" {{ $rankFilter === 'top10' ? 'selected' : '' }}>Page 1 (#1 - #10)</option>
                        <option value="striking" {{ $rankFilter === 'striking' ? 'selected' : '' }}>Striking (11 - 50)</option>
                        <option value="unranked" {{ $rankFilter === 'unranked' ? 'selected' : '' }}>Unranked (> 50)</option>
                    </select>
                </div>

                <!-- Device Filter -->
                <div class="w-32">
                    <select name="device" class="select select-bordered select-sm w-full bg-base-200/50 text-xs rounded-lg focus:outline-none focus:border-primary">
                        <option value="">All Devices</option>
                        <option value="desktop" {{ $device === 'desktop' ? 'selected' : '' }}>Desktop</option>
                        <option value="mobile" {{ $device === 'mobile' ? 'selected' : '' }}>Mobile</option>
                    </select>
                </div>

                <!-- Country Filter -->
                <div class="w-36">
                    <select name="country" class="select select-bordered select-sm w-full bg-base-200/50 text-xs rounded-lg focus:outline-none focus:border-primary">
                        <option value="">All Countries</option>
                        @foreach($countries as $code => $cName)
                            <option value="{{ $code }}" {{ $country === $code ? 'selected' : '' }}>{{ $cName }} ({{ strtoupper($code) }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- User Filter (Admin Only) -->
                @if($isAdmin && $allUsers->count() > 0)
                    <div class="w-40">
                        <select name="user_id" class="select select-bordered select-sm w-full bg-base-200/50 text-xs rounded-lg focus:outline-none focus:border-primary">
                            <option value="">All Authors</option>
                            @foreach($allUsers as $u)
                                <option value="{{ $u->id }}" {{ $filterUserId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <!-- Action buttons & View Switcher -->
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

                <button type="submit" class="btn btn-sm btn-primary px-4 rounded-lg font-semibold shadow-xs flex items-center gap-1.5">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('rank-tracker.database', ['view_mode' => $viewMode ?? 'batches']) }}" class="btn btn-sm btn-ghost text-base-content/70 hover:bg-base-200 rounded-lg">Reset</a>
            </div>
        </form>
    </div>

    <!-- Data Table (Strict Rule 14 & Rule 8 Compliance) -->
    <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
        <div class="table-responsive overflow-x-auto">
            @if(($viewMode ?? 'batches') === 'batches')
                <!-- 1. BATCH-CENTRIC TABLE (1 Row per Scan Run) -->
                <table class="table table-hover align-middle mb-0 text-xs border-top w-full" id="batches-table">
                    <!-- Standardized compact header (Rule 14) -->
                    <thead class="bg-base-200/80 text-base-content/70 border-b border-base-300 font-mono uppercase text-[11px] tracking-wider">
                        <tr>
                            <th class="w-36 text-nowrap py-3 px-3.5">Actions</th>
                            <th class="text-nowrap py-3 px-3.5">Batch Run</th>
                            <th class="text-nowrap py-3 px-3.5">Target Domain & Client</th>
                            <th class="text-nowrap py-3 px-3.5">Keywords Included</th>
                            <th class="text-nowrap py-3 px-3.5">Geo / Device</th>
                            <th class="text-nowrap py-3 px-3.5">SERP Breakdown</th>
                            <th class="text-nowrap py-3 px-3.5">Best Rank</th>
                            @if($isAdmin)
                                <th class="text-nowrap py-3 px-3.5">Run By</th>
                            @endif
                            <th class="text-nowrap py-3 px-3.5">Crawled</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-300/50" id="batches-table-body">
                        @forelse($batches as $b)
                            <tr class="hover:bg-base-200/40 transition-colors" id="batch-row-{{ $b->batch_id }}">
                                <!-- Column 1 Icon-Only Actions (Rule 8 Compliance) -->
                                <td class="whitespace-nowrap py-3 px-3.5">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" onclick="inspectBatch('{{ $b->batch_id }}')" class="btn btn-xs btn-square btn-outline btn-primary rounded-lg" title="Inspect All Keywords in this Batch">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>
                                        @can('export-rank-data')
                                        <a href="{{ route('rank-tracker.export.batch.csv', $b->batch_id) }}" class="btn btn-xs btn-square btn-outline btn-info rounded-lg" title="Download CSV (All {{ $b->total_keywords }} Keywords in this Batch)">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        </a>
                                        @endcan
                                        @can('track-ranks')
                                        <a href="{{ route('rank-tracker.index') }}?prefill_domain={{ urlencode($b->target_domain) }}&prefill_country={{ $b->country }}" class="btn btn-xs btn-square btn-outline btn-warning rounded-lg" title="Re-scan in Live Studio">
                                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                        </a>
                                        @endcan
                                        @can('delete-rank-data')
                                        <button type="button" onclick="deleteBatchRecord('{{ $b->batch_id }}')" class="btn btn-xs btn-square btn-outline btn-error rounded-lg" title="Delete This Entire Batch">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>

                                <!-- Batch Run ID & Count -->
                                <td class="py-3 px-3.5 whitespace-nowrap">
                                    <div class="font-mono font-bold text-xs text-base-content flex items-center gap-1.5">
                                        <span>#{{ $b->batch_id }}</span>
                                    </div>
                                    <span class="badge badge-primary badge-outline badge-xs font-mono font-bold mt-0.5">
                                        {{ $b->total_keywords }} Keyword{{ $b->total_keywords > 1 ? 's' : '' }}
                                    </span>
                                </td>

                                <!-- Target Domain & Client -->
                                <td class="py-3 px-3.5 whitespace-nowrap">
                                    <div class="font-mono text-xs font-semibold text-base-content">{{ $b->target_domain }}</div>
                                    @if($b->client)
                                        <span class="text-[10px] text-base-content/60 font-sans block mt-0.5">
                                            <i data-lucide="building-2" class="w-2.5 h-2.5 inline mr-0.5"></i>{{ $b->client->name }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-base-content/40 font-sans block italic mt-0.5">Generic Run</span>
                                    @endif
                                </td>

                                <!-- Keywords Included Preview -->
                                <td class="py-3 px-3.5 max-w-xs">
                                    <span class="text-xs text-base-content/80 line-clamp-1 truncate block" title="{{ $b->keywords_preview }}">
                                        {{ $b->keywords_preview }}
                                    </span>
                                </td>

                                <!-- Geo & Device -->
                                <td class="py-3 px-3.5 whitespace-nowrap">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-semibold text-xs text-base-content">
                                            {{ $b->location ?: strtoupper($b->country) }}
                                        </span>
                                        <span class="text-[10px] text-base-content/50 uppercase font-mono">
                                            {{ strtoupper($b->country) }} &bull; {{ ucfirst($b->device) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- SERP Breakdown Pills -->
                                <td class="py-3 px-3.5 whitespace-nowrap">
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
                                <td class="py-3 px-3.5 whitespace-nowrap">
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
                                    <td class="py-3 px-3.5 whitespace-nowrap">
                                        @if($b->user)
                                            <span class="badge badge-sm bg-base-200 text-base-content/80 font-medium">
                                                {{ $b->user->name }}
                                            </span>
                                        @else
                                            <span class="text-base-content/40 text-xs">—</span>
                                        @endif
                                    </td>
                                @endif

                                <!-- Crawled Date -->
                                <td class="py-3 px-3.5 whitespace-nowrap text-base-content/70">
                                    <div class="text-xs font-mono">{{ $b->checked_at ? $b->checked_at->format('Y-m-d H:i') : '—' }}</div>
                                    <div class="text-[10px] text-base-content/50">{{ $b->checked_at ? $b->checked_at->diffForHumans() : '—' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 9 : 8 }}" class="text-center py-12 text-base-content/50">
                                    <div class="max-w-xs mx-auto flex flex-col items-center gap-3">
                                        <div class="w-12 h-12 rounded-2xl bg-base-200 flex items-center justify-center text-base-content/40">
                                            <i data-lucide="layers" class="w-6 h-6"></i>
                                        </div>
                                        <p class="font-semibold text-sm text-base-content/70">No scan batches found matching your filters.</p>
                                        <p class="text-xs text-base-content/50">Execute rank scans via Live SERP Scanner or adjust your search filters.</p>
                                        <a href="{{ route('rank-tracker.index') }}" class="btn btn-primary btn-xs mt-1">
                                            <i data-lucide="search" class="w-3 h-3"></i> Go to Live Rank Scanner
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <!-- 2. INDIVIDUAL KEYWORDS TABLE -->
                <table class="table table-hover align-middle mb-0 text-xs border-top w-full" id="rankings-table">
                    <!-- Standardized compact header (Rule 14) -->
                    <thead class="bg-base-200/80 text-base-content/70 border-b border-base-300 font-mono uppercase text-[11px] tracking-wider">
                        <tr>
                            <th class="w-36 text-nowrap py-3 px-3.5">Actions</th>
                            <th class="text-nowrap py-3 px-3.5">Keyword</th>
                            <th class="text-nowrap py-3 px-3.5">Target Domain</th>
                            <th class="text-nowrap py-3 px-3.5">Geo / Device</th>
                            <th class="text-nowrap py-3 px-3.5">Organic Rank</th>
                            <th class="text-nowrap py-3 px-3.5">Delta</th>
                            <th class="text-nowrap py-3 px-3.5">Landing Page URL</th>
                            @if($isAdmin)
                                <th class="text-nowrap py-3 px-3.5">Run By</th>
                            @endif
                            <th class="text-nowrap py-3 px-3.5">Crawled</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-300/50" id="database-table-body">
                        @forelse($rankChecks as $item)
                            <tr class="hover:bg-base-200/40 transition-colors" id="db-row-{{ $item->id }}">
                                <!-- Column 1 Icon-Only Actions (Rule 8 Compliance) -->
                                <td class="whitespace-nowrap py-3 px-3.5">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" onclick="inspectSerp({{ $item->id }})" class="btn btn-xs btn-square btn-outline btn-primary rounded-lg" title="Inspect Top 10 SERP Competitors">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>
                                        @can('export-rank-data')
                                        <a href="{{ route('rank-tracker.export.single.csv', $item->id) }}" class="btn btn-xs btn-square btn-outline btn-info rounded-lg" title="Download CSV for this Query">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        </a>
                                        @endcan
                                        @can('track-ranks')
                                        <a href="{{ route('rank-tracker.index') }}?prefill_kw={{ urlencode($item->keyword) }}&prefill_domain={{ urlencode($item->target_domain) }}&prefill_country={{ $item->country }}" class="btn btn-xs btn-square btn-outline btn-warning rounded-lg" title="Re-scan in Live Studio">
                                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                        </a>
                                        @endcan
                                        @can('delete-rank-data')
                                        <button type="button" onclick="deleteRankRecord({{ $item->id }})" class="btn btn-xs btn-square btn-outline btn-error rounded-lg" title="Delete Audit Record">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>

                                <!-- Keyword -->
                                <td class="py-3 px-3.5 font-bold text-base-content">
                                    <div class="flex items-center gap-1.5">
                                        <span>{{ $item->keyword }}</span>
                                        @if(!empty($item->serp_features) && in_array('featured_snippet', $item->serp_features))
                                            <span class="badge badge-accent badge-xs font-mono font-bold" title="Owns Google Featured Snippet #1">Snippet #1</span>
                                        @endif
                                    </div>
                                    @if($item->client)
                                        <span class="text-[10px] text-base-content/60 font-sans block mt-0.5">
                                            <i data-lucide="building-2" class="w-2.5 h-2.5 inline mr-0.5"></i>{{ $item->client->name }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-base-content/40 font-sans block italic mt-0.5">Generic Run</span>
                                    @endif
                                </td>

                                <!-- Target Domain -->
                                <td class="py-3 px-3.5 whitespace-nowrap">
                                    <span class="font-mono text-xs text-base-content/80">{{ $item->target_domain }}</span>
                                </td>

                                <!-- Geo / Device -->
                                <td class="py-3 px-3.5 whitespace-nowrap">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-semibold text-xs text-base-content">
                                            {{ $item->location ?: strtoupper($item->country) }}
                                        </span>
                                        <span class="text-[10px] text-base-content/50 uppercase font-mono">
                                            {{ strtoupper($item->country) }} &bull; {{ ucfirst($item->device) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Organic Rank -->
                                <td class="py-3 px-3.5 whitespace-nowrap">
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

                                <!-- Rank Delta -->
                                <td class="py-3 px-3.5 whitespace-nowrap font-mono font-bold">
                                    @if($item->rank_change === null || $item->rank_change === 999)
                                        <span class="badge badge-secondary badge-outline badge-xs">NEW</span>
                                    @elseif($item->rank_change > 0)
                                        <span class="text-emerald-600 dark:text-emerald-400 flex items-center gap-0.5">
                                            <i data-lucide="arrow-up" class="w-3 h-3"></i> +{{ $item->rank_change }}
                                        </span>
                                    @elseif($item->rank_change < 0)
                                        <span class="text-rose-600 dark:text-rose-400 flex items-center gap-0.5">
                                            <i data-lucide="arrow-down" class="w-3 h-3"></i> {{ $item->rank_change }}
                                        </span>
                                    @else
                                        <span class="text-base-content/40 text-xs">= 0</span>
                                    @endif
                                </td>

                                <!-- Landing Page URL & Title -->
                                <td class="py-3 px-3.5 max-w-xs truncate">
                                    @if(!empty($item->ranking_url))
                                        <a href="{{ $item->ranking_url }}" target="_blank" rel="noopener noreferrer" class="link link-hover text-primary font-medium flex items-center gap-1 truncate" title="{{ $item->ranking_title }}">
                                            <span class="truncate">{{ $item->ranking_title ?: $item->ranking_url }}</span>
                                            <i data-lucide="external-link" class="w-3 h-3 shrink-0"></i>
                                        </a>
                                        <span class="text-[10px] text-base-content/50 font-mono block truncate">{{ $item->ranking_url }}</span>
                                    @else
                                        <span class="text-base-content/40 italic">Not found in top 50</span>
                                    @endif
                                </td>

                                <!-- Run By (Admin only) -->
                                @if($isAdmin)
                                    <td class="py-3 px-3.5 whitespace-nowrap">
                                        @if($item->user)
                                            <span class="badge badge-sm bg-base-200 text-base-content/80 font-medium">
                                                {{ $item->user->name }}
                                            </span>
                                        @else
                                            <span class="text-base-content/40 text-xs">—</span>
                                        @endif
                                    </td>
                                @endif

                                <!-- Crawled Date -->
                                <td class="py-3 px-3.5 whitespace-nowrap text-base-content/70">
                                    <div class="text-xs font-mono">{{ $item->checked_at ? $item->checked_at->format('Y-m-d H:i') : $item->created_at->format('Y-m-d H:i') }}</div>
                                    <div class="text-[10px] text-base-content/50">{{ $item->checked_at ? $item->checked_at->diffForHumans() : $item->created_at->diffForHumans() }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 9 : 8 }}" class="text-center py-12 text-base-content/50">
                                    <div class="max-w-xs mx-auto flex flex-col items-center gap-3">
                                        <div class="w-12 h-12 rounded-2xl bg-base-200 flex items-center justify-center text-base-content/40">
                                            <i data-lucide="database" class="w-6 h-6"></i>
                                        </div>
                                        <p class="font-semibold text-sm text-base-content/70">No ranking records found matching your filters.</p>
                                        <p class="text-xs text-base-content/50">Execute rank scans via Live SERP Scanner or adjust your search filters.</p>
                                        <a href="{{ route('rank-tracker.index') }}" class="btn btn-primary btn-xs mt-1">
                                            <i data-lucide="search" class="w-3 h-3"></i> Go to Live Rank Scanner
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        @php
            $currentPagination = ($viewMode ?? 'batches') === 'batches' ? $batches : $rankChecks;
            $itemTypeLabel = ($viewMode ?? 'batches') === 'batches' ? 'batches' : 'queries';
        @endphp
        @if($currentPagination->hasPages())
            <div class="p-4 border-t border-base-300 flex items-center justify-between">
                <span class="text-xs text-base-content/60">
                    Showing {{ $currentPagination->firstItem() ?? 0 }} to {{ $currentPagination->lastItem() ?? 0 }} of {{ $currentPagination->total() }} {{ $itemTypeLabel }}
                </span>
                <div>
                    {{ $currentPagination->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Reusable Top 10 SERP Competitors Modal -->
<dialog id="serp_modal" class="modal">
    <div class="modal-box max-w-3xl bg-base-100 border border-base-300 rounded-2xl p-5 shadow-2xl">
        <div class="flex items-start justify-between pb-3 border-b border-base-300 mb-4">
            <div>
                <span class="badge badge-primary badge-outline badge-xs font-mono uppercase">SERP Landscape</span>
                <h3 class="font-bold text-base text-base-content mt-1" id="modal-kw-title">Top 10 Competitors</h3>
                <div class="flex items-center gap-2 mt-1">
                    <span id="modal-geo-badge" class="badge badge-ghost badge-xs font-mono uppercase"></span>
                    <span id="modal-location-badge" class="hidden badge badge-success badge-xs font-mono flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-2.5 h-2.5"></i>
                        <span id="modal-location-text"></span>
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @can('export-rank-data')
                <a id="modal-download-csv-btn" href="#" class="btn btn-xs btn-outline btn-info gap-1 font-semibold" title="Download CSV for this Query">
                    <i data-lucide="download" class="w-3 h-3"></i> Download CSV
                </a>
                @endcan
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost">✕</button>
                </form>
            </div>
        </div>

        <div id="serp-competitors-list" class="space-y-2 max-h-[60vh] overflow-y-auto pr-1">
            <!-- Dynamic SERP Competitor rows injected here -->
        </div>

        <div class="modal-action border-t border-base-300 pt-3 mt-4">
            <form method="dialog">
                <button class="btn btn-sm btn-ghost">Close</button>
            </form>
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

        <div class="p-4 bg-base-200/50 border-t border-base-300 flex justify-end">
            <button type="button" onclick="document.getElementById('batch_modal').close()" class="btn btn-ghost btn-sm font-semibold">Close</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('scripts')
<script>
    function inspectSerp(id) {
        document.getElementById('serp_modal').showModal();
        $('#modal-download-csv-btn').attr('href', '/rank-tracker/' + id + '/export-csv');
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
                    $('#serp-competitors-list').html('<div class="text-center py-6 text-base-content/50 text-xs">No competitor snapshot available for this check.</div>');
                    return;
                }

                let html = '';
                res.competitors.forEach(function(c) {
                    const isTarget = c.is_target;
                    const borderCls = isTarget ? 'border-primary bg-primary/5 dark:bg-primary/10' : 'border-base-300 bg-base-200/40';
                    const badgeCls = isTarget ? 'badge-primary font-bold' : 'badge-ghost';

                    html += `
                        <div class="p-3 rounded-xl border ${borderCls} flex items-start gap-3 transition-colors">
                            <span class="badge ${badgeCls} badge-sm font-mono shrink-0 mt-0.5">#${c.position}</span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <a href="${c.link}" target="_blank" rel="noopener noreferrer" class="link link-hover text-xs font-bold text-base-content truncate hover:text-primary">
                                        ${c.title}
                                    </a>
                                    ${isTarget ? '<span class="badge badge-success badge-xs text-[10px] font-bold">Your Target</span>' : ''}
                                </div>
                                <div class="text-[10px] text-base-content/50 font-mono truncate mt-0.5">${c.domain}</div>
                                ${c.snippet ? `<p class="text-xs text-base-content/70 mt-1 line-clamp-2">${c.snippet}</p>` : ''}
                            </div>
                        </div>
                    `;
                });

                $('#serp-competitors-list').html(html);
                if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
            },
            error: function() {
                $('#serp-competitors-list').html('<div class="text-center py-6 text-error text-xs">Failed to load SERP competitor data.</div>');
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

    function deleteRankRecord(id) {
        if (!confirm('Are you sure you want to delete this search query record?')) return;

        $.ajax({
            url: "/rank-tracker/" + id,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    $('#db-row-' + id).fadeOut(300, function() { $(this).remove(); });
                    showToast('Record deleted successfully.', 'info');
                } else {
                    showToast(res.message || 'Could not delete record.', 'warning');
                }
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Delete operation failed.', 'error');
            }
        });
    }

    function escapeHtml(str) {
        return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
</script>
@endpush
