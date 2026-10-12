@extends('layouts.app')

@section('title', 'Bulk CSV Rank Scanner - SERP Intelligence')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-base-200">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="badge badge-warning badge-outline badge-sm font-mono gap-1">
                    <i data-lucide="layers" class="w-3 h-3"></i> Asynchronous Queue
                </span>
                <span class="text-xs text-base-content/60 font-medium">Batch Keyword Audit</span>
            </div>
            <h1 class="text-2xl font-black text-base-content tracking-tight flex items-center gap-2">
                <i data-lucide="file-spreadsheet" class="w-7 h-7 text-amber-500"></i>
                Bulk CSV Rank Scanner
            </h1>
            <p class="text-sm text-base-content/70">
                Upload hundreds of search keywords via CSV to crawl Google Top 50 rankings in the background without browser timeouts.
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('rank-tracker.sample-csv') }}" class="btn btn-outline btn-sm gap-2 border-base-300 font-semibold shadow-xs hover:border-amber-500 hover:text-amber-500">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Download Sample CSV</span>
            </a>
            <a href="{{ route('rank-tracker.database') }}" class="btn btn-ghost btn-sm gap-2 text-base-content/75 hover:bg-base-200 font-semibold">
                <i data-lucide="database" class="w-4 h-4 text-emerald-500"></i>
                <span>Rank Database</span>
            </a>
            <a href="{{ route('rank-tracker.index') }}" class="btn btn-ghost btn-sm gap-2 text-base-content/75 hover:bg-base-200 font-semibold">
                <i data-lucide="trending-up" class="w-4 h-4 text-rose-500"></i>
                <span>Live Studio</span>
            </a>
        </div>
    </div>

    {{-- Alert Container --}}
    <div id="bulk-alert-container">
        @if(session('success'))
            <div class="alert alert-success shadow-xs rounded-xl mb-4">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error') || $errors->any())
            <div class="alert alert-error shadow-xs rounded-xl mb-4">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <div>
                    <div class="font-bold">Validation Error</div>
                    <ul class="text-xs list-disc list-inside mt-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                        @if(session('error'))
                            <li>{{ session('error') }}</li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif
    </div>

    {{-- Main 2-Column Bulk Studio --}}
    <form id="bulk-upload-form" method="POST" action="{{ route('rank-tracker.bulk.process') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- Left Column: File Upload Dropzone (5 Columns) --}}
            <div class="lg:col-span-5 space-y-4">
                <div class="card bg-base-100 border border-base-300/80 shadow-xs rounded-2xl overflow-hidden">
                    <div class="card-body p-5 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-base-200">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-base-content flex items-center gap-2">
                                <i data-lucide="upload-cloud" class="w-4 h-4 text-amber-500"></i>
                                Step 1: Upload Keyword CSV
                            </h2>
                            <span class="badge badge-neutral badge-xs font-mono">.csv, .txt</span>
                        </div>

                        {{-- Dropzone Area --}}
                        <div id="csv-dropzone" class="border-2 border-dashed border-base-300 hover:border-amber-500 bg-base-200/40 hover:bg-amber-500/5 rounded-2xl p-6 text-center transition-all cursor-pointer group relative">
                            <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required />
                            <div class="space-y-2 pointer-events-none">
                                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center mx-auto group-hover:scale-110 transition-transform">
                                    <i data-lucide="file-up" class="w-6 h-6"></i>
                                </div>
                                <div class="font-bold text-sm text-base-content group-hover:text-amber-500 transition-colors">
                                    Drop your CSV file here, or <span class="text-amber-500 underline">browse</span>
                                </div>
                                <p class="text-xs text-base-content/60">
                                    Supports up to 500 keywords per upload (Max file size: 10MB)
                                </p>
                            </div>
                        </div>

                        {{-- File Selected Card Preview --}}
                        <div id="file-preview-card" class="hidden p-3.5 bg-base-200/70 border border-base-300 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5 truncate">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-500/15 text-emerald-500 flex items-center justify-center shrink-0">
                                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                                    </div>
                                    <div class="truncate">
                                        <div id="preview-file-name" class="text-xs font-bold text-base-content truncate">filename.csv</div>
                                        <div id="preview-file-size" class="text-[11px] text-base-content/60">0 KB</div>
                                    </div>
                                </div>
                                <button type="button" id="remove-file-btn" class="btn btn-ghost btn-xs btn-square text-error hover:bg-error/15" title="Remove file">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        {{-- CSV Format Guidelines & Sample Helper --}}
                        <div class="p-3.5 bg-base-200/40 border border-base-300/60 rounded-xl space-y-2 text-xs">
                            <div class="font-bold text-base-content flex items-center justify-between">
                                <span>CSV Format Structure</span>
                                <a href="{{ route('rank-tracker.sample-csv') }}" class="text-amber-500 hover:underline flex items-center gap-1 font-semibold">
                                    <i data-lucide="download" class="w-3 h-3"></i> Get Sample
                                </a>
                            </div>
                            <p class="text-base-content/70 text-[11px] leading-relaxed">
                                Header columns supported: <code class="bg-base-300 px-1 py-0.5 rounded font-mono">keyword</code>, <code class="bg-base-300 px-1 py-0.5 rounded font-mono">target_domain</code>, <code class="bg-base-300 px-1 py-0.5 rounded font-mono">location</code>, <code class="bg-base-300 px-1 py-0.5 rounded font-mono">target_url</code>.
                            </p>
                            <p class="text-base-content/60 text-[11px] italic">
                                * Missing column values automatically inherit the defaults specified on the right.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Default Parameters & Settings (7 Columns) --}}
            <div class="lg:col-span-7 space-y-4">
                <div class="card bg-base-100 border border-base-300/80 shadow-xs rounded-2xl overflow-hidden">
                    <div class="card-body p-5 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-base-200">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-base-content flex items-center gap-2">
                                <i data-lucide="sliders" class="w-4 h-4 text-amber-500"></i>
                                Step 2: Batch Defaults & Geo-Targeting
                            </h2>
                            <span class="badge badge-ghost badge-xs">Default Parameters</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Target Domain & Quick Client Selector --}}
                            <div class="space-y-1.5 sm:col-span-2">
                                <div class="flex items-center justify-between mb-0.5">
                                    <label class="text-xs font-bold text-base-content flex items-center gap-1.5">
                                        <i data-lucide="globe" class="w-3.5 h-3.5 text-primary"></i>
                                        <span>Target Domain <span class="text-error">*</span></span>
                                    </label>
                                    @if($allClients->count() > 0)
                                        <select id="client_quick_select" onchange="handleDomainQuickSelect(this)" class="select select-bordered select-xs text-[10px] h-6 min-h-6 bg-base-200/80 border-base-300 max-w-[160px] font-semibold" title="Select domain from existing client">
                                            <option value="" data-domain="">Custom / Blank</option>
                                            @foreach($allClients as $c)
                                                @php
                                                    $cleanUrl = preg_replace('#^https?://#i', '', rtrim($c->website_url, '/'));
                                                    $cleanUrl = preg_replace('#^www\.#i', '', $cleanUrl);
                                                @endphp
                                                <option value="{{ $c->id }}" data-domain="{{ $cleanUrl }}">
                                                    {{ $c->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                                <div class="relative">
                                    <i data-lucide="globe" class="w-4 h-4 absolute left-3 top-3 text-base-content/40 pointer-events-none"></i>
                                    <input type="text" id="target_domain" name="target_domain" value="{{ old('target_domain', '') }}" placeholder="e.g. sterlinghospitals.com" class="input input-sm input-bordered w-full pl-9 font-mono text-xs rounded-xl" required />
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-base-content/50">
                                    <span>Primary website domain to track ranks for if not provided in CSV row.</span>
                                    <span id="domain-clean-preview" class="font-mono">Clean: —</span>
                                </div>
                            </div>

                            {{-- Target Location with Autocomplete --}}
                            <div class="space-y-1.5 sm:col-span-2 relative">
                                <div class="flex items-center justify-between mb-0.5">
                                    <label class="text-xs font-bold text-base-content flex items-center gap-1.5">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-500"></i>
                                        <span>Default Location (Geo-Targeting)</span>
                                    </label>
                                    <span id="canonical-status-pill" class="hidden badge badge-success badge-xs font-mono text-[9px] gap-1">
                                        <i data-lucide="check-check" class="w-2.5 h-2.5"></i> Canonical
                                    </span>
                                </div>
                                <div class="relative">
                                    <i data-lucide="map-pin" class="w-4 h-4 absolute left-3 top-3 text-base-content/40 pointer-events-none"></i>
                                    <input type="text" id="location" name="location" value="{{ old('location', '') }}" autocomplete="off" placeholder="e.g. Ahmedabad, Gujarat, India (Type 2+ letters for auto-fill...)" class="input input-sm input-bordered w-full pl-9 pr-8 text-xs rounded-xl" oninput="handleLocationInput(this)" />
                                    <span id="loc-spinner" class="hidden loading loading-spinner loading-xs absolute right-3 top-3 text-amber-500"></span>
                                </div>
                                <!-- Dropdown suggestions popup -->
                                <div id="location-suggestions" class="hidden absolute z-50 left-0 right-0 mt-1 bg-base-100 border border-base-300 rounded-xl shadow-2xl max-h-52 overflow-y-auto divide-y divide-base-200 text-xs"></div>
                                <span class="text-[11px] text-base-content/50">Simulates Google local search from this specific city (leave blank for national search).</span>
                            </div>

                            {{-- Client Assignment --}}
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-base-content flex items-center gap-1.5">
                                    <i data-lucide="briefcase" class="w-3.5 h-3.5 text-base-content/60"></i>
                                    <span>Assign to Client</span>
                                </label>
                                <select id="client_id" name="client_id" class="select select-sm select-bordered w-full text-xs rounded-xl">
                                    <option value="">Generic / No Client</option>
                                    @foreach($allClients as $c)
                                        <option value="{{ $c->id }}" {{ (old('client_id', $activeClient?->id) == $c->id) ? 'selected' : '' }}>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Target Country --}}
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-base-content flex items-center gap-1.5">
                                    <i data-lucide="flag" class="w-3.5 h-3.5 text-base-content/60"></i>
                                    <span>Country</span>
                                </label>
                                <select id="country" name="country" class="select select-sm select-bordered w-full text-xs rounded-xl">
                                    <option value="in" {{ old('country', 'in') === 'in' ? 'selected' : '' }}>India (google.co.in)</option>
                                    <option value="us" {{ old('country') === 'us' ? 'selected' : '' }}>United States (google.com)</option>
                                    <option value="uk" {{ old('country') === 'uk' ? 'selected' : '' }}>United Kingdom (google.co.uk)</option>
                                    <option value="ae" {{ old('country') === 'ae' ? 'selected' : '' }}>United Arab Emirates (google.ae)</option>
                                    <option value="ca" {{ old('country') === 'ca' ? 'selected' : '' }}>Canada (google.ca)</option>
                                    <option value="au" {{ old('country') === 'au' ? 'selected' : '' }}>Australia (google.com.au)</option>
                                    <option value="sg" {{ old('country') === 'sg' ? 'selected' : '' }}>Singapore (google.com.sg)</option>
                                </select>
                            </div>

                            {{-- Search Depth --}}
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-base-content flex items-center gap-1.5">
                                    <i data-lucide="layers" class="w-3.5 h-3.5 text-base-content/60"></i>
                                    <span>Search Depth</span>
                                </label>
                                <select id="depth" name="depth" class="select select-sm select-bordered w-full text-xs rounded-xl">
                                    <option value="50" selected>Top 50 (5 Pages, Comprehensive)</option>
                                    <option value="30">Top 30 (3 Pages)</option>
                                    <option value="20">Top 20 (2 Pages)</option>
                                    <option value="10">Top 10 (Page 1 Only, Fast)</option>
                                </select>
                            </div>

                            {{-- Device --}}
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-base-content flex items-center gap-1.5">
                                    <i data-lucide="monitor" class="w-3.5 h-3.5 text-base-content/60"></i>
                                    <span>Device</span>
                                </label>
                                <select id="device" name="device" class="select select-sm select-bordered w-full text-xs rounded-xl">
                                    <option value="desktop" selected>Desktop</option>
                                    <option value="mobile">Mobile</option>
                                </select>
                            </div>

                            {{-- Match Type & Exact Target URL --}}
                            <div class="space-y-1.5 sm:col-span-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-base-content">Match Strategy</label>
                                    <div class="flex items-center gap-3 text-xs">
                                        <label class="flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" name="match_type" value="domain" class="radio radio-xs radio-primary" checked />
                                            <span class="font-medium">Entire Domain</span>
                                        </label>
                                        <label class="flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" name="match_type" value="exact_url" class="radio radio-xs radio-primary" />
                                            <span class="font-medium">Exact Landing Page</span>
                                        </label>
                                    </div>
                                </div>
                                <div id="target-url-container" class="hidden pt-1">
                                    <input type="url" id="target_url" name="target_url" placeholder="https://www.sterlinghospitals.com/specialities/ophthalmology" class="input input-sm input-bordered w-full font-mono text-xs rounded-xl" />
                                    <span class="text-[11px] text-base-content/50">Only counts as ranked if the exact URL matches.</span>
                                </div>
                            </div>
                        </div>

                        {{-- Form Submit Button --}}
                        <div class="pt-3 border-t border-base-200 flex items-center justify-between">
                            <div class="text-xs text-base-content/60 flex items-center gap-1.5">
                                <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i>
                                <span>Zero-Timeout Queue Processing</span>
                            </div>
                            <button type="submit" id="start-bulk-btn" class="btn btn-warning btn-sm font-bold gap-2 rounded-xl shadow-xs px-5">
                                <i data-lucide="play" class="w-4 h-4"></i>
                                <span>Start Bulk Scan</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

    {{-- Recent Bulk Batches Card --}}
    <div class="card bg-base-100 border border-base-300/80 shadow-xs rounded-2xl overflow-hidden">
        <div class="card-body p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-base-200">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-base-content flex items-center gap-2">
                        <i data-lucide="history" class="w-4 h-4 text-amber-500"></i>
                        Recent Bulk Scan Batches
                    </h2>
                    <p class="text-xs text-base-content/60">Live progress and historical audit batches initiated from CSV uploads.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="window.location.reload()" class="btn btn-ghost btn-xs gap-1 text-xs">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Refresh
                    </button>
                    <a href="{{ route('rank-tracker.database', ['view_mode' => 'batches']) }}" class="btn btn-outline btn-xs gap-1 font-semibold">
                        <i data-lucide="external-link" class="w-3 h-3"></i> View in Database
                    </a>
                </div>
            </div>

            @if($recentBatches->isEmpty())
                <div class="py-12 text-center space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-base-200 flex items-center justify-center mx-auto text-base-content/40">
                        <i data-lucide="inbox" class="w-6 h-6"></i>
                    </div>
                    <div class="text-sm font-bold text-base-content">No Bulk Batches Dispatched Yet</div>
                    <p class="text-xs text-base-content/60 max-w-sm mx-auto">
                        Upload your first keyword CSV above to kick off high-volume SERP tracking in the background.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table table-hover align-middle mb-0 text-xs">
                        <thead class="bg-base-200/60 text-base-content/70 border-b border-base-200 text-uppercase tracking-wider text-[11px] font-bold">
                            <tr>
                                <th class="text-nowrap" style="width: 90px;">Actions</th>
                                <th class="text-nowrap">Batch ID</th>
                                <th class="text-nowrap">Domain</th>
                                <th class="text-nowrap">Source File</th>
                                <th class="text-nowrap">Keywords</th>
                                <th class="text-nowrap">Top 3</th>
                                <th class="text-nowrap">Page 1</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Dispatched</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200">
                            @foreach($recentBatches as $batch)
                                <tr class="hover:bg-base-200/30 transition-colors" id="batch-row-{{ $batch->batch_id }}">
                                    {{-- Column 1: Action Buttons per Rule 8 --}}
                                    <td>
                                        <div class="d-inline-flex align-items-center" style="gap: 6px;">
                                            @if($batch->status === 'completed' || $batch->completed_keywords > 0)
                                                <a href="{{ route('rank-tracker.export.batch.csv', $batch->batch_id) }}" class="btn btn-xs btn-square btn-outline btn-info rounded-lg" title="Download CSV">
                                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                                </a>
                                            @endif
                                            <button type="button" onclick="inspectBatchProgress('{{ $batch->batch_id }}')" class="btn btn-xs btn-square btn-outline btn-warning rounded-lg" title="Live Telemetry & Status">
                                                <i data-lucide="activity" class="w-3.5 h-3.5"></i>
                                            </button>
                                            <a href="{{ route('rank-tracker.database', ['search' => $batch->batch_id]) }}" class="btn btn-xs btn-square btn-outline btn-primary rounded-lg" title="View in Rank Database">
                                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="font-mono font-bold text-base-content">
                                        {{ $batch->batch_id }}
                                        @if($batch->client)
                                            <div class="text-[10px] text-base-content/50 font-sans">{{ $batch->client->name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-bold text-base-content">{{ $batch->target_domain }}</span>
                                        @if($batch->location)
                                            <div class="text-[10px] text-base-content/50 flex items-center gap-1">
                                                <i data-lucide="map-pin" class="w-2.5 h-2.5"></i> {{ Str::limit($batch->location, 25) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-base-content/75 truncate max-w-[160px]" title="{{ $batch->filename }}">
                                        {{ $batch->filename ?: 'Manual Batch' }}
                                    </td>
                                    <td>
                                        <span class="font-bold text-base-content">{{ $batch->completed_keywords }}</span>
                                        <span class="text-base-content/50">/ {{ $batch->total_keywords }}</span>
                                    </td>
                                    <td>
                                        @if($batch->top_3_count > 0)
                                            <span class="badge badge-success badge-xs font-bold">{{ $batch->top_3_count }}</span>
                                        @else
                                            <span class="text-base-content/40">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($batch->page_1_count > 0)
                                            <span class="badge badge-info badge-xs font-bold">{{ $batch->page_1_count }}</span>
                                        @else
                                            <span class="text-base-content/40">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($batch->status === 'completed')
                                            <span class="badge badge-success badge-outline badge-xs font-semibold gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-success"></span> Completed
                                            </span>
                                        @elseif($batch->status === 'processing')
                                            <span class="badge badge-warning badge-outline badge-xs font-semibold gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-warning animate-pulse"></span> Scanning ({{ $batch->progress_percentage }}%)
                                            </span>
                                        @elseif($batch->status === 'pending')
                                            <span class="badge badge-ghost badge-outline badge-xs font-semibold gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-base-content/40"></span> Queued
                                            </span>
                                        @elseif($batch->status === 'failed')
                                            <span class="badge badge-error badge-outline badge-xs font-semibold gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-error"></span> Failed
                                            </span>
                                        @else
                                            <span class="badge badge-neutral badge-xs">{{ ucfirst($batch->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-base-content/60 text-[11px] whitespace-nowrap">
                                        {{ $batch->created_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>

{{-- Live Background Progress Modal (DaisyUI Standard) --}}
<dialog id="bulk_progress_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box max-w-2xl bg-base-100 border border-base-300 p-6 space-y-5 rounded-2xl shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-base-200">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-amber-500/15 text-amber-500 flex items-center justify-center">
                    <i data-lucide="activity" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-base-content flex items-center gap-2">
                        <span>Background Scan Progress</span>
                        <span id="modal-status-badge" class="badge badge-warning badge-xs">Queued</span>
                    </h3>
                    <div id="modal-batch-id" class="text-[11px] font-mono text-base-content/60">Batch: —</div>
                </div>
            </div>
            <button type="button" onclick="closeProgressModal()" class="btn btn-ghost btn-xs btn-square">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        {{-- Progress Bar & Metrics --}}
        <div class="space-y-2">
            <div class="flex items-center justify-between text-xs font-bold text-base-content">
                <span id="modal-progress-label">Processing Keywords...</span>
                <span id="modal-progress-percent" class="font-mono text-amber-500">0%</span>
            </div>
            <progress id="modal-progress-bar" class="progress progress-warning w-full h-3" value="0" max="100"></progress>
            <div class="flex items-center justify-between text-[11px] text-base-content/60">
                <span id="modal-counts-label">0 of 0 scanned</span>
                <span id="modal-elapsed-label">Live Google crawl</span>
            </div>
        </div>

        {{-- Mini KPI Badges --}}
        <div class="grid grid-cols-4 gap-2 pt-1 text-center">
            <div class="p-2.5 bg-base-200/50 rounded-xl border border-base-300/50">
                <div class="text-[10px] uppercase font-bold text-base-content/50">Top 3</div>
                <div id="modal-top3-count" class="text-base font-black text-emerald-500">0</div>
            </div>
            <div class="p-2.5 bg-base-200/50 rounded-xl border border-base-300/50">
                <div class="text-[10px] uppercase font-bold text-base-content/50">Page 1</div>
                <div id="modal-page1-count" class="text-base font-black text-info">0</div>
            </div>
            <div class="p-2.5 bg-base-200/50 rounded-xl border border-base-300/50">
                <div class="text-[10px] uppercase font-bold text-base-content/50">Unranked</div>
                <div id="modal-unranked-count" class="text-base font-black text-base-content/60">0</div>
            </div>
            <div class="p-2.5 bg-base-200/50 rounded-xl border border-base-300/50">
                <div class="text-[10px] uppercase font-bold text-base-content/50">Errors</div>
                <div id="modal-error-count" class="text-base font-black text-error">0</div>
            </div>
        </div>

        {{-- Live Stream of Recent Keyword Checks --}}
        <div class="space-y-2">
            <div class="text-xs font-bold uppercase tracking-wider text-base-content/60 flex items-center justify-between">
                <span>Recent Live Checks in this Batch</span>
                <span class="text-[10px] text-base-content/40">Updated every 2.5s</span>
            </div>
            <div id="modal-recent-checks-list" class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                <div class="text-center py-4 text-xs text-base-content/50 italic">
                    Waiting for worker to start scanning keywords...
                </div>
            </div>
        </div>

        {{-- Modal Actions --}}
        <div class="pt-3 border-t border-base-200 flex flex-wrap items-center justify-between gap-2">
            <button type="button" onclick="closeProgressModal()" class="btn btn-ghost btn-sm font-semibold text-xs">
                Keep Running in Background
            </button>
            <div class="flex items-center gap-2">
                <a id="modal-view-database-btn" href="#" class="btn btn-outline btn-sm font-semibold text-xs gap-1">
                    <i data-lucide="database" class="w-3.5 h-3.5"></i> View Database
                </a>
                <a id="modal-download-csv-btn" href="#" class="btn btn-warning btn-sm font-bold text-xs gap-1 hidden">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i> Download CSV
                </a>
            </div>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    lucide.createIcons();

    const fileInput = document.getElementById('csv_file');
    const previewCard = document.getElementById('file-preview-card');
    const fileNameEl = document.getElementById('preview-file-name');
    const fileSizeEl = document.getElementById('preview-file-size');
    const removeBtn = document.getElementById('remove-file-btn');
    const dropzone = document.getElementById('csv-dropzone');
    const domainInput = document.getElementById('target_domain');
    const cleanPreview = document.getElementById('domain-clean-preview');
    const form = document.getElementById('bulk-upload-form');
    const submitBtn = document.getElementById('start-bulk-btn');

    // Match type radio toggle
    const matchRadios = document.querySelectorAll('input[name="match_type"]');
    const targetUrlContainer = document.getElementById('target-url-container');
    matchRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            if (this.value === 'exact_url') {
                targetUrlContainer.classList.remove('hidden');
            } else {
                targetUrlContainer.classList.add('hidden');
            }
        });
    });

    // Domain Normalizer Live Preview
    domainInput.addEventListener('input', function () {
        let val = this.value.trim().toLowerCase();
        val = val.replace(/^https?:\/\//, '').replace(/^www\./, '').split('/')[0];
        cleanPreview.textContent = val ? 'Clean: ' + val : 'Clean: —';
    });
    if (domainInput.value) {
        domainInput.dispatchEvent(new Event('input'));
    }

    // File Drag & Drop Handlers
    fileInput.addEventListener('change', function (e) {
        if (this.files && this.files[0]) {
            showFilePreview(this.files[0]);
        }
    });

    removeBtn.addEventListener('click', function () {
        fileInput.value = '';
        previewCard.classList.add('hidden');
        dropzone.classList.remove('hidden');
    });

    function showFilePreview(file) {
        fileNameEl.textContent = file.name;
        const kb = (file.size / 1024).toFixed(1);
        fileSizeEl.textContent = (kb > 1024) ? (kb / 1024).toFixed(2) + ' MB' : kb + ' KB';
        previewCard.classList.remove('hidden');
        dropzone.classList.add('hidden');
        lucide.createIcons();
    }

    // Form Submission & Queue Dispatch via AJAX
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(form);
        const originalBtnHtml = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading loading-spinner loading-xs"></span> Processing CSV...';

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(res => res.json().then(data => ({ status: res.status, body: data })))
        .then(({ status, body }) => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
            lucide.createIcons();

            if (status === 200 && body.success) {
                // Open Live Progress Modal
                inspectBatchProgress(body.batch_id);
            } else {
                showAlert(body.message || 'An error occurred while uploading CSV.', 'error');
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
            lucide.createIcons();
            showAlert('Network error: ' + err.message, 'error');
        });
    });

    function showAlert(msg, type = 'error') {
        const container = document.getElementById('bulk-alert-container');
        const alertClass = (type === 'success') ? 'alert-success' : 'alert-error';
        const icon = (type === 'success') ? 'check-circle' : 'alert-circle';
        container.innerHTML = `
            <div class="alert ${alertClass} shadow-xs rounded-xl mb-4 transition-all">
                <i data-lucide="${icon}" class="w-5 h-5"></i>
                <span class="text-xs font-semibold">${msg}</span>
            </div>
        `;
        lucide.createIcons();
    }
});

let progressPollInterval = null;
let currentInspectingBatchId = null;

function inspectBatchProgress(batchId) {
    currentInspectingBatchId = batchId;
    const modal = document.getElementById('bulk_progress_modal');
    modal.showModal();

    document.getElementById('modal-batch-id').textContent = 'Batch: ' + batchId;
    document.getElementById('modal-view-database-btn').href = '/rank-database?search=' + encodeURIComponent(batchId);
    document.getElementById('modal-download-csv-btn').href = '/rank-tracker/batch/' + encodeURIComponent(batchId) + '/export-csv';

    // Immediate first fetch
    pollBatchStatus(batchId);

    // Continuous polling every 2.5s
    if (progressPollInterval) {
        clearInterval(progressPollInterval);
    }
    progressPollInterval = setInterval(() => {
        pollBatchStatus(batchId);
    }, 2500);
}

function pollBatchStatus(batchId) {
    fetch('/rank-tracker/batch/' + encodeURIComponent(batchId) + '/progress', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        if (!data || data.message) return;

        const percent = data.progress_percentage || 0;
        document.getElementById('modal-progress-bar').value = percent;
        document.getElementById('modal-progress-percent').textContent = percent + '%';
        document.getElementById('modal-counts-label').textContent = `${data.completed_keywords} of ${data.total_keywords} scanned`;

        // Update KPIs
        document.getElementById('modal-top3-count').textContent = data.top_3_count || 0;
        document.getElementById('modal-page1-count').textContent = data.page_1_count || 0;
        document.getElementById('modal-unranked-count').textContent = data.unranked_count || 0;
        document.getElementById('modal-error-count').textContent = data.failed_keywords || 0;

        // Badge & Labels
        const badge = document.getElementById('modal-status-badge');
        const dlBtn = document.getElementById('modal-download-csv-btn');

        if (data.status === 'completed') {
            badge.className = 'badge badge-success badge-xs';
            badge.textContent = 'Completed';
            document.getElementById('modal-progress-label').textContent = 'All Keywords Scanned Successfully';
            dlBtn.classList.remove('hidden');
            if (progressPollInterval) clearInterval(progressPollInterval);
        } else if (data.status === 'processing') {
            badge.className = 'badge badge-warning badge-xs';
            badge.textContent = 'Processing (' + percent + '%)';
            document.getElementById('modal-progress-label').textContent = 'Scanning Live Rankings...';
            dlBtn.classList.remove('hidden');
        } else if (data.status === 'failed') {
            badge.className = 'badge badge-error badge-xs';
            badge.textContent = 'Failed';
            document.getElementById('modal-progress-label').textContent = 'Execution Error';
            if (progressPollInterval) clearInterval(progressPollInterval);
        } else if (data.status === 'cancelled') {
            badge.className = 'badge badge-neutral badge-xs';
            badge.textContent = 'Cancelled';
            document.getElementById('modal-progress-label').textContent = 'Scan Cancelled by User';
            if (progressPollInterval) clearInterval(progressPollInterval);
        } else {
            badge.className = 'badge badge-ghost badge-xs';
            badge.textContent = 'Queued';
        }

        // Render recent live checks
        const list = document.getElementById('modal-recent-checks-list');
        if (data.recent_checks && data.recent_checks.length > 0) {
            list.innerHTML = data.recent_checks.map(c => `
                <div class="flex items-center justify-between p-2 bg-base-200/50 rounded-lg text-xs">
                    <div class="truncate max-w-[320px]">
                        <span class="font-bold text-base-content">${escapeHtml(c.keyword)}</span>
                        ${c.ranking_url ? `<div class="text-[10px] text-base-content/50 truncate font-mono">${escapeHtml(c.ranking_url)}</div>` : ''}
                    </div>
                    <div class="shrink-0 font-mono font-bold ${c.is_ranked ? (c.position <= 3 ? 'text-emerald-500' : 'text-info') : 'text-base-content/50'}">
                        ${c.display_rank}
                    </div>
                </div>
            `).join('');
        }

        lucide.createIcons();
    })
    .catch(err => {
        console.error('Progress poll error:', err);
    });
}

function closeProgressModal() {
    if (progressPollInterval) {
        clearInterval(progressPollInterval);
    }
    const modal = document.getElementById('bulk_progress_modal');
    modal.close();
}

function handleDomainQuickSelect(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const domain = selectedOption.getAttribute('data-domain') || '';
    const clientId = selectEl.value;

    const domainInput = document.getElementById('target_domain');
    domainInput.value = domain;
    domainInput.dispatchEvent(new Event('input'));

    const clientSelect = document.getElementById('client_id');
    if (clientSelect) {
        clientSelect.value = clientId;
    }
}

let locationDebounceTimer = null;

function handleLocationInput(input) {
    clearTimeout(locationDebounceTimer);
    const query = input.value.trim();
    const country = (document.getElementById('country') ? document.getElementById('country').value : 'in') || 'in';
    const suggestionsBox = document.getElementById('location-suggestions');
    const spinner = document.getElementById('loc-spinner');
    const pill = document.getElementById('canonical-status-pill');

    if (query.length < 2) {
        suggestionsBox.classList.add('hidden');
        suggestionsBox.innerHTML = '';
        spinner.classList.add('hidden');
        pill.classList.add('hidden');
        return;
    }

    spinner.classList.remove('hidden');

    locationDebounceTimer = setTimeout(function () {
        fetch(`{{ route('rank-tracker.locations') }}?q=${encodeURIComponent(query)}&country=${encodeURIComponent(country)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(locations => {
            spinner.classList.add('hidden');
            if (!locations || locations.length === 0) {
                suggestionsBox.classList.add('hidden');
                suggestionsBox.innerHTML = '';
                return;
            }

            let html = '';
            locations.forEach(loc => {
                html += `
                    <button type="button" onclick="selectCanonicalLocation('${escapeHtml(loc.canonical_name)}')" class="w-full text-left px-3.5 py-2.5 hover:bg-base-200 transition-colors flex items-center justify-between text-xs group">
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

            suggestionsBox.innerHTML = html;
            suggestionsBox.classList.remove('hidden');
            if (window.lucide && window.lucide.createIcons) {
                window.lucide.createIcons();
            }
        })
        .catch(err => {
            spinner.classList.add('hidden');
            suggestionsBox.classList.add('hidden');
        });
    }, 250);
}

function selectCanonicalLocation(canonicalName) {
    const formatted = (canonicalName || '').replace(/\s*,\s*/g, ', ');
    const locInput = document.getElementById('location');
    locInput.value = formatted;
    document.getElementById('location-suggestions').classList.add('hidden');
    document.getElementById('location-suggestions').innerHTML = '';
    document.getElementById('canonical-status-pill').classList.remove('hidden');
}

document.addEventListener('click', function (e) {
    const suggestionsBox = document.getElementById('location-suggestions');
    const locInput = document.getElementById('location');
    if (suggestionsBox && !suggestionsBox.contains(e.target) && e.target !== locInput) {
        suggestionsBox.classList.add('hidden');
    }
});

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>"']/g, function (m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[m];
    });
}
</script>
@endpush
