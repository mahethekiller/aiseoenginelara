@extends('layouts.app')

@section('title', 'Admin Executive Command Center')
@section('page_title', 'Executive Command Center')
@section('page_badge', 'Admin Telemetry')

@section('content')
<div class="space-y-6">
    <!-- Header Blueprint (Rule 13 Benchmark) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="badge badge-primary badge-outline badge-sm font-mono flex items-center gap-1">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    <span>Executive Command Center</span>
                </span>
                <span class="text-xs text-base-content/60">Live Portal-Wide Operations</span>
            </div>
            <h1 class="text-xl font-black text-base-content mt-1">Agency Analytics & Engine Operations</h1>
            <p class="text-xs text-base-content/60 mt-0.5">High-level system telemetry, multi-client footprint, token compute spend, and provider health.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('dashboard', ['view' => 'user']) }}" class="btn btn-sm btn-ghost border border-base-300 text-xs font-semibold gap-1.5" title="Preview Creator Dashboard perspective">
                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                <span>Creator View</span>
            </a>
            <a href="{{ route('clients.index') }}" class="btn btn-sm btn-ghost border border-base-300 text-xs font-semibold gap-1.5">
                <i data-lucide="building-2" class="w-3.5 h-3.5 text-emerald-500"></i>
                <span>Clients</span>
            </a>
            <a href="{{ route('blog.creator') }}" class="btn btn-sm btn-primary font-bold shadow-xs gap-1.5">
                <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                <span>Create Article</span>
            </a>
        </div>
    </div>

    <!-- Telemetry KPI Metric Cards (Rule 13 5-Column Grid) -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <!-- 1. Total Content Volume -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-primary/10 text-primary border border-primary/20 font-mono font-semibold rounded-full px-2 py-0.5">Content Output</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Total Articles</div>
                    <div class="text-2xl font-black text-base-content mt-1">{{ number_format($metrics['totalArticles']) }}</div>
                    <div class="text-[11px] text-base-content/60 font-mono mt-0.5">{{ number_format($metrics['totalWords']) }} words</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-primary"></div>
        </div>

        <!-- 2. Quality & E-E-A-T Readiness -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Audit Quality</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Avg SEO Score</div>
                    <div class="text-2xl font-black text-emerald-500 mt-1">{{ $metrics['avgSeoScore'] }}<span class="text-xs font-normal text-base-content/50">/100</span></div>
                    <div class="text-[11px] text-base-content/60 font-mono mt-0.5">Flesch Ease: {{ $metrics['avgReadingEase'] }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0">
                    <i data-lucide="award" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-emerald-500"></div>
        </div>

        <!-- 3. Multi-Client Footprint -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-indigo-500/10 text-indigo-500 border border-indigo-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Agency Hub</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Agency Clients</div>
                    <div class="text-2xl font-black text-indigo-500 mt-1">{{ number_format($metrics['totalClients']) }}</div>
                    <div class="text-[11px] text-base-content/60 font-mono mt-0.5">{{ $metrics['activeClients'] }} active brands</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center shrink-0">
                    <i data-lucide="building-2" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-indigo-500"></div>
        </div>

        <!-- 4. LLM Token & Cost Compute -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-amber-500/10 text-amber-500 border border-amber-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Token Spend</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Total AI Cost</div>
                    <div class="text-2xl font-black text-amber-500 mt-1">${{ number_format($metrics['totalCostUsd'], 2) }}</div>
                    <div class="text-[11px] text-base-content/60 font-mono mt-0.5">₹{{ number_format($metrics['totalCostInr'], 2) }} ({{ $metrics['totalLlmCalls'] }} calls)</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0">
                    <i data-lucide="coins" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-amber-500"></div>
        </div>

        <!-- 5. WordPress Live Sync -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-cyan-500/10 text-cyan-500 border border-cyan-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Distribution</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">WP Published</div>
                    <div class="text-2xl font-black text-cyan-500 mt-1">{{ $metrics['wpPublishedCount'] }}</div>
                    <div class="text-[11px] text-base-content/60 font-mono mt-0.5">{{ $metrics['wpPublishRate'] }}% sync rate</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center shrink-0">
                    <i data-lucide="globe" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-cyan-500"></div>
        </div>
    </div>

    <!-- Main 2-Column Command Studio Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Column: Operations Tables (8 Cols) -->
        <div class="lg:col-span-8 space-y-6">
            <!-- 1. Recent Generated Articles Table -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-base-300 flex items-center justify-between">
                    <div>
                        <h2 class="font-extrabold text-sm text-base-content flex items-center gap-2">
                            <i data-lucide="file-check-2" class="w-4 h-4 text-primary"></i>
                            <span>Recent Production Articles</span>
                        </h2>
                        <p class="text-[11px] text-base-content/60 mt-0.5">Latest long-form copy generated across all agency clients</p>
                    </div>
                    <a href="{{ route('articles.index') }}" class="btn btn-xs btn-ghost gap-1 text-primary">
                        <span>View All Articles</span>
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>

                <div class="table-responsive overflow-x-auto">
                    <table class="table table-hover align-middle mb-0 text-xs border-top w-full">
                        <!-- Standardized compact header (Rule 14) -->
                        <thead class="bg-base-200/80 text-base-content/70 border-b border-base-300 font-mono uppercase text-[11px] tracking-wider">
                            <tr>
                                <th class="w-28 text-nowrap py-3 px-3.5">Actions</th>
                                <th class="text-nowrap py-3 px-3.5">Article Title</th>
                                <th class="text-nowrap py-3 px-3.5">Author</th>
                                <th class="text-nowrap py-3 px-3.5">Words</th>
                                <th class="text-nowrap py-3 px-3.5">SEO Score</th>
                                <th class="text-nowrap py-3 px-3.5">WordPress</th>
                                <th class="text-nowrap py-3 px-3.5">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-300/50">
                            @forelse($recentArticles as $a)
                            <tr class="hover:bg-base-200/40 transition-colors">
                                <!-- Column 1 Icon-Only Actions (Rule 8 Compliance) -->
                                <td class="whitespace-nowrap py-3 px-3.5">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" onclick="viewArticleDetails({{ $a->id }})" class="btn btn-xs btn-square btn-outline btn-primary rounded-lg" title="View Article & Schema">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <a href="{{ route('articles.download', ['id' => $a->id, 'format' => 'docx']) }}" class="btn btn-xs btn-square btn-outline btn-secondary rounded-lg" title="Download Word Document">
                                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                        </a>
                                        <button type="button" onclick="publishArticleToWP({{ $a->id }}, this)" class="btn btn-xs btn-square btn-outline btn-success rounded-lg" title="Publish to Client's WordPress">
                                            <i data-lucide="share-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="font-bold text-base-content whitespace-nowrap py-3 px-3.5">
                                    <div class="hover:text-primary transition-colors cursor-pointer" onclick="viewArticleDetails({{ $a->id }})">
                                        {{ Str::limit($a->title, 55) }}
                                    </div>
                                    <div class="text-[10px] text-base-content/50 font-normal font-mono mt-0.5">{{ $a->slug }}</div>
                                </td>
                                <td class="whitespace-nowrap text-base-content/70 py-3 px-3.5 font-medium">
                                    {{ $a->user ? $a->user->name : 'System' }}
                                </td>
                                <td class="whitespace-nowrap font-mono text-base-content/80 py-3 px-3.5">{{ number_format($a->word_count) }}</td>
                                <td class="whitespace-nowrap py-3 px-3.5">
                                    <span class="badge badge-sm badge-success font-mono font-bold">{{ $a->seo_score }}/100</span>
                                </td>
                                <td class="whitespace-nowrap py-3 px-3.5">
                                    @if(!empty($a->wordpress_post_url))
                                        <a href="{{ $a->wordpress_post_url }}" target="_blank" rel="noopener noreferrer" class="badge badge-sm badge-success badge-outline gap-1 font-mono hover:bg-success hover:text-white transition-all">
                                            <span>Live WP</span>
                                            <i data-lucide="external-link" class="w-2.5 h-2.5"></i>
                                        </a>
                                    @else
                                        <span class="badge badge-sm badge-ghost text-base-content/50 font-mono">Draft Only</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap font-mono text-base-content/60 py-3 px-3.5">{{ $a->created_at->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-6 text-base-content/50">
                                    No articles created yet. <a href="{{ route('blog.creator') }}" class="text-primary hover:underline">Start in SEO Blog Creator</a>.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Agency Client Operational Health -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-base-300 flex items-center justify-between">
                    <div>
                        <h2 class="font-extrabold text-sm text-base-content flex items-center gap-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-emerald-500"></i>
                            <span>Active Agency Client Accounts</span>
                        </h2>
                        <p class="text-[11px] text-base-content/60 mt-0.5">Multi-client brand directives, XML sitemap caching, and WordPress integration</p>
                    </div>
                    <a href="{{ route('clients.index') }}" class="btn btn-xs btn-ghost gap-1 text-primary">
                        <span>Manage Clients</span>
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>

                <div class="table-responsive overflow-x-auto">
                    <table class="table table-hover align-middle mb-0 text-xs border-top w-full">
                        <thead class="bg-base-200/80 text-base-content/70 border-b border-base-300 font-mono uppercase text-[11px] tracking-wider">
                            <tr>
                                <th class="text-nowrap py-3 px-3.5">Client Name</th>
                                <th class="text-nowrap py-3 px-3.5">Industry</th>
                                <th class="text-nowrap py-3 px-3.5">Brand Tone Voice</th>
                                <th class="text-nowrap py-3 px-3.5">Sitemap Index</th>
                                <th class="text-nowrap py-3 px-3.5">WordPress Sync</th>
                                <th class="text-nowrap py-3 px-3.5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-300/50">
                            @forelse($clients as $c)
                            <tr class="hover:bg-base-200/40 transition-colors">
                                <td class="font-bold text-base-content whitespace-nowrap py-3 px-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                                            {{ strtoupper(substr($c->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div>{{ $c->name }}</div>
                                            <a href="{{ $c->website_url }}" target="_blank" rel="noopener noreferrer" class="text-[10px] text-base-content/50 hover:text-primary font-mono truncate max-w-xs block">
                                                {{ $c->website_url }}
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap text-base-content/70 py-3 px-3.5">{{ $c->industry ?: 'General' }}</td>
                                <td class="text-base-content/70 py-3 px-3.5 truncate max-w-xs">{{ $c->brand_tone ?: 'Empathetic & Authoritative' }}</td>
                                <td class="whitespace-nowrap py-3 px-3.5">
                                    @php $sitemapCount = is_array($c->sitemap_cache) ? count($c->sitemap_cache) : 0; @endphp
                                    @if($sitemapCount > 0)
                                        <span class="badge badge-sm badge-success badge-outline font-mono gap-1">
                                            <i data-lucide="check" class="w-2.5 h-2.5"></i>
                                            <span>{{ $sitemapCount }} links</span>
                                        </span>
                                    @else
                                        <span class="badge badge-sm badge-ghost text-base-content/50 font-mono">Uncached</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap py-3 px-3.5">
                                    @if(!empty($c->wordpress_url))
                                        <span class="badge badge-sm badge-info font-mono gap-1">Connected</span>
                                    @else
                                        <span class="badge badge-sm badge-ghost text-base-content/50 font-mono">No WP</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap py-3 px-3.5">
                                    @if($c->is_active)
                                        <span class="badge badge-sm badge-success font-mono">Active</span>
                                    @else
                                        <span class="badge badge-sm badge-neutral font-mono">Paused</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-6 text-base-content/50">
                                    No agency clients configured yet. <a href="{{ route('clients.index') }}" class="text-primary hover:underline">Add your first agency client</a>.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: Provider Health, Token Cost Breakdown & Quick Actions (4 Cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- 1. Multi-LLM Provider Engine Status -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-4">
                <div class="flex items-center justify-between pb-3 border-b border-base-300">
                    <h2 class="font-extrabold text-sm text-base-content flex items-center gap-2">
                        <i data-lucide="cpu" class="w-4 h-4 text-indigo-500"></i>
                        <span>LLM Core Providers</span>
                    </h2>
                    <span class="badge badge-success badge-xs font-mono font-bold uppercase">100% Live</span>
                </div>

                <div class="space-y-2.5 mt-3">
                    <!-- Google Gemini -->
                    <div class="p-2.5 rounded-xl bg-base-200/60 border border-base-300 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <div>
                                <div class="font-bold text-xs text-base-content">Google Gemini</div>
                                <div class="text-[10px] text-base-content/60 font-mono">gemini-2.0-flash / 3.1-lite</div>
                            </div>
                        </div>
                        <span class="badge badge-sm badge-primary font-mono font-bold">Active</span>
                    </div>

                    <!-- OpenAI -->
                    <div class="p-2.5 rounded-xl bg-base-200/60 border border-base-300 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <div>
                                <div class="font-bold text-xs text-base-content">OpenAI</div>
                                <div class="text-[10px] text-base-content/60 font-mono">gpt-4o / gpt-4o-mini</div>
                            </div>
                        </div>
                        <span class="badge badge-sm badge-ghost text-base-content/60 font-mono">Sync Ready</span>
                    </div>

                    <!-- Anthropic Claude -->
                    <div class="p-2.5 rounded-xl bg-base-200/60 border border-base-300 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <div>
                                <div class="font-bold text-xs text-base-content">Anthropic Claude</div>
                                <div class="text-[10px] text-base-content/60 font-mono">claude-3.7-sonnet / haiku</div>
                            </div>
                        </div>
                        <span class="badge badge-sm badge-ghost text-base-content/60 font-mono">Sync Ready</span>
                    </div>

                    <!-- DeepSeek -->
                    <div class="p-2.5 rounded-xl bg-base-200/60 border border-base-300 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <div>
                                <div class="font-bold text-xs text-base-content">DeepSeek</div>
                                <div class="text-[10px] text-base-content/60 font-mono">deepseek-chat / reasoner</div>
                            </div>
                        </div>
                        <span class="badge badge-sm badge-ghost text-base-content/60 font-mono">Sync Ready</span>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-base-300 text-center">
                    <a href="{{ route('settings.index') }}" class="text-[11px] font-semibold text-primary hover:underline flex items-center justify-center gap-1">
                        <span>Configure API Keys & Model Sync</span>
                        <i data-lucide="chevron-right" class="w-3 h-3"></i>
                    </a>
                </div>
            </div>

            <!-- 2. Token Spend by Model Breakdown -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-4">
                <div class="flex items-center justify-between pb-3 border-b border-base-300">
                    <h2 class="font-extrabold text-sm text-base-content flex items-center gap-2">
                        <i data-lucide="bar-chart-2" class="w-4 h-4 text-amber-500"></i>
                        <span>Token Consumption by Model</span>
                    </h2>
                    <a href="{{ route('reports.usage') }}" class="text-[11px] text-primary hover:underline font-mono">Audit</a>
                </div>

                <div class="space-y-2 mt-3">
                    @forelse($modelBreakdown as $mb)
                    <div class="p-2.5 rounded-xl bg-base-200/50 border border-base-300 flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-base-content font-mono">{{ $mb->model }}</div>
                            <div class="text-[10px] text-base-content/60 font-mono">{{ $mb->calls_count }} executions &bull; {{ number_format($mb->tokens_sum ?? 0) }} tokens</div>
                        </div>
                        <div class="text-right">
                            <div class="font-black text-amber-500 font-mono">${{ number_format((float)$mb->cost_sum, 4) }}</div>
                            <div class="text-[10px] text-base-content/60 font-mono">₹{{ number_format(((float)$mb->cost_sum) * 87.5, 2) }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-xs text-base-content/50">
                        No AI token usage records logged yet.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- 3. Operational Quick Action Shortcuts -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-4">
                <h2 class="font-extrabold text-sm text-base-content pb-2.5 border-b border-base-300 flex items-center gap-2">
                    <i data-lucide="zap" class="w-4 h-4 text-amber-400"></i>
                    <span>Quick Execution Shortcuts</span>
                </h2>

                <div class="grid grid-cols-2 gap-2 mt-3">
                    <a href="{{ route('blog.creator') }}" class="p-3 rounded-xl bg-base-200/60 border border-base-300 hover:border-primary/50 hover:bg-base-200 transition-all flex flex-col items-center justify-center text-center gap-1.5">
                        <i data-lucide="pen-tool" class="w-5 h-5 text-primary"></i>
                        <span class="text-xs font-bold text-base-content">Blog Creator</span>
                    </a>
                    <a href="{{ route('rewriter.index') }}" class="p-3 rounded-xl bg-base-200/60 border border-base-300 hover:border-primary/50 hover:bg-base-200 transition-all flex flex-col items-center justify-center text-center gap-1.5">
                        <i data-lucide="refresh-cw" class="w-5 h-5 text-amber-500"></i>
                        <span class="text-xs font-bold text-base-content">Rewriter</span>
                    </a>
                    <a href="{{ route('prompt-templates.index') }}" class="p-3 rounded-xl bg-base-200/60 border border-base-300 hover:border-primary/50 hover:bg-base-200 transition-all flex flex-col items-center justify-center text-center gap-1.5">
                        <i data-lucide="file-code-2" class="w-5 h-5 text-violet-500"></i>
                        <span class="text-xs font-bold text-base-content">Blueprints</span>
                    </a>
                    <a href="{{ route('reports.usage') }}" class="p-3 rounded-xl bg-base-200/60 border border-base-300 hover:border-primary/50 hover:bg-base-200 transition-all flex flex-col items-center justify-center text-center gap-1.5">
                        <i data-lucide="bar-chart-3" class="w-5 h-5 text-blue-400"></i>
                        <span class="text-xs font-bold text-base-content">Cost Audit</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Article Detail Modal -->
<dialog id="article_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-5xl bg-base-100 border border-base-300 text-base-content p-0 shadow-2xl rounded-2xl overflow-hidden max-h-[92vh] flex flex-col">
        <div class="px-6 py-4 border-b border-base-300 bg-base-200/50 flex items-center justify-between">
            <h3 id="modal-article-title" class="font-bold text-sm">Article Preview</h3>
            <form method="dialog"><button class="btn btn-xs btn-circle btn-ghost">✕</button></form>
        </div>
        <div id="modal-article-body" class="p-6 overflow-y-auto flex-1 prose max-w-none text-base-content">
            Loading...
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    function viewArticleDetails(id) {
        document.getElementById('article_modal').showModal();
        $('#modal-article-body').html('<span class="loading loading-spinner loading-sm"></span> Loading article...');

        $.ajax({
            url: "/articles/" + id,
            type: 'GET',
            success: function(res) {
                const article = res.article || res;
                $('#modal-article-title').text(article.title);
                $('#modal-article-body').html(article.html_content);
                window.refreshIcons();
            },
            error: function() {
                $('#modal-article-body').html('<p class="text-error">Failed to load article content.</p>');
            }
        });
    }

    function publishArticleToWP(id, btn) {
        $(btn).attr('disabled', 'disabled');
        const orig = $(btn).html();
        $(btn).html('<span class="loading loading-spinner loading-xs"></span>');

        $.ajax({
            url: "/articles/" + id + "/publish-wp",
            type: 'POST',
            success: function(res) {
                $(btn).removeAttr('disabled').html(orig);
                showToast(res.message || 'Published to WordPress successfully!', 'success');
                setTimeout(() => window.location.reload(), 400);
            },
            error: function(xhr) {
                $(btn).removeAttr('disabled').html(orig);
                showToast(xhr.responseJSON?.message || 'Publishing failed. Check Client WP credentials.', 'error');
            }
        });
    }
</script>
@endpush
