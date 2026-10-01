@extends('layouts.app')

@section('title', 'Content Database')
@section('page_title', 'Content Database')
@section('page_badge', 'Historical Articles Archive')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="badge badge-primary badge-outline badge-sm font-mono">Content Repository</span>
                <span class="text-xs text-base-content/60">SEO-Optimized Long-form Articles</span>
            </div>
            <h1 class="text-xl font-black text-base-content mt-1">Generated Articles & Performance Audits</h1>
        </div>
        <a href="{{ route('blog.creator') }}" class="btn btn-primary btn-sm gap-2 font-bold shadow-xs">
            <i data-lucide="pen-tool" class="w-4 h-4"></i> Create New Article
        </a>
    </div>

    <!-- KPI Metric Cards (Rule 13 Blueprint) -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
        <!-- 1. Total Articles -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-primary/10 text-primary border border-primary/20 font-mono font-semibold rounded-full px-2 py-0.5">Archive</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Total Articles</div>
                    <div class="text-2xl font-black text-base-content mt-1">{{ $metrics['total_articles'] ?? 0 }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-primary"></div>
        </div>

        <!-- 2. Avg SEO Score -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Rank Ready</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Avg SEO Score</div>
                    <div class="text-2xl font-black text-emerald-500 mt-1">{{ $metrics['avg_seo_score'] ?? 0 }}<span class="text-xs font-normal text-base-content/50">/100</span></div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0">
                    <i data-lucide="award" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-emerald-500"></div>
        </div>

        <!-- 3. Avg Reading Ease -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-indigo-500/10 text-indigo-500 border border-indigo-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Flesch Scale</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Avg Reading Ease</div>
                    <div class="text-2xl font-black text-indigo-500 mt-1">{{ $metrics['avg_flesch_score'] ?? 0 }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center shrink-0">
                    <i data-lucide="book-open" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-indigo-500"></div>
        </div>

        <!-- 4. Words Generated -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-cyan-500/10 text-cyan-500 border border-cyan-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Volume</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Words Generated</div>
                    <div class="text-2xl font-black text-cyan-500 mt-1">{{ number_format($metrics['total_words'] ?? 0) }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center shrink-0">
                    <i data-lucide="align-left" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-cyan-500"></div>
        </div>

        <!-- 5. WordPress Published -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-amber-500/10 text-amber-500 border border-amber-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Live Sync</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">WordPress Published</div>
                    <div class="text-2xl font-black text-amber-500 mt-1">{{ $metrics['total_published'] ?? 0 }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0">
                    <i data-lucide="globe" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-amber-500"></div>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-3.5">
        <form method="GET" action="{{ route('articles.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 text-base-content/40 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or meta keywords..." class="input input-bordered input-sm w-full pl-9 bg-base-200/50 text-xs rounded-lg focus:outline-none focus:border-primary" />
            </div>
            <div class="w-44">
                <select name="client_id" class="select select-bordered select-sm w-full bg-base-200/50 text-xs rounded-lg focus:outline-none focus:border-primary">
                    <option value="">All Clients</option>
                    <option value="none" {{ request('client_id') === 'none' ? 'selected' : '' }}>Independent (No Client)</option>
                    @if(isset($clients))
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="w-44">
                <select name="prompt_template" class="select select-bordered select-sm w-full bg-base-200/50 text-xs rounded-lg focus:outline-none focus:border-primary">
                    <option value="">All Prompt Blueprints</option>
                    <option value="master" {{ request('prompt_template') === 'master' ? 'selected' : '' }}>Comprehensive Master</option>
                    @foreach($promptTemplates as $pt)
                        <option value="{{ $pt->id }}" {{ request('prompt_template') == $pt->id ? 'selected' : '' }}>{{ $pt->archetype_name }}</option>
                    @endforeach
                </select>
            </div>
            @if($isAdmin && $users->count() > 0)
            <div class="w-40">
                <select name="user_id" class="select select-bordered select-sm w-full bg-base-200/50 text-xs rounded-lg focus:outline-none focus:border-primary">
                    <option value="">All Authors</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <button type="submit" class="btn btn-sm btn-primary px-4 rounded-lg font-semibold shadow-xs flex items-center gap-1.5">
                <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                <span>Filter</span>
            </button>
            <a href="{{ route('articles.index') }}" class="btn btn-sm btn-ghost text-base-content/70 hover:bg-base-200 rounded-lg">Reset</a>
        </form>
    </div>

    <!-- Articles Data Table -->
    <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
        <div class="table-responsive overflow-x-auto">
            <table class="table table-hover align-middle mb-0 text-xs border-top w-full">
                <!-- Standardized compact header (Rule 14) -->
                <thead class="bg-base-200/80 text-base-content/70 border-b border-base-300 font-mono uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="w-44 text-nowrap py-3 px-3.5">Actions</th>
                        <th class="text-nowrap py-3 px-3.5">Article Title</th>
                        <th class="text-nowrap py-3 px-3.5">Client</th>
                        <th class="text-nowrap py-3 px-3.5">Prompt Blueprint</th>
                        <th class="text-nowrap py-3 px-3.5">Created By</th>
                        <th class="text-nowrap py-3 px-3.5">Words</th>
                        <th class="text-nowrap py-3 px-3.5">WordPress</th>
                        <th class="text-nowrap py-3 px-3.5">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-300/50">
                    @forelse($articles as $a)
                    <tr class="hover:bg-base-200/40 transition-colors">
                        <!-- Column 1 Icon-Only Actions (Rule 8 Compliance) -->
                        <td class="whitespace-nowrap py-3 px-3.5">
                            <div class="inline-flex items-center gap-1.5">
                                <button type="button" onclick="viewArticleDetails({{ $a->id }})" class="btn btn-xs btn-square btn-outline btn-primary rounded-lg" title="View Article & Schema">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                </button>
                                <button type="button" onclick="viewArticlePrompt({{ $a->id }})" class="btn btn-xs btn-square btn-outline btn-warning rounded-lg" title="Inspect LLM Generation Prompt">
                                    <i data-lucide="terminal" class="w-3.5 h-3.5"></i>
                                </button>
                                <a href="{{ route('articles.download', ['id' => $a->id, 'format' => 'html']) }}" class="btn btn-xs btn-square btn-outline btn-info rounded-lg" title="Download HTML">
                                    <i data-lucide="code" class="w-3.5 h-3.5"></i>
                                </a>
                                <a href="{{ route('articles.download', ['id' => $a->id, 'format' => 'docx']) }}" class="btn btn-xs btn-square btn-outline btn-secondary rounded-lg" title="Download Word Document">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                </a>
                                <button type="button" onclick="publishArticleToWP({{ $a->id }}, this)" class="btn btn-xs btn-square btn-outline btn-success rounded-lg" title="Publish to Client's WordPress">
                                    <i data-lucide="share-2" class="w-3.5 h-3.5"></i>
                                </button>
                                @hasanyrole('admin|super_admin')
                                <button type="button" onclick="deleteArticleRecord({{ $a->id }})" class="btn btn-xs btn-square btn-outline btn-error rounded-lg" title="Delete Article">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                                @endhasanyrole
                            </div>
                        </td>
                        <td class="font-bold text-base-content whitespace-nowrap py-3 px-3.5">
                            <div class="hover:text-primary transition-colors cursor-pointer" onclick="viewArticleDetails({{ $a->id }})">{{ Str::limit($a->title, 50) }}</div>
                            <div class="text-[10px] text-base-content/50 font-normal font-mono mt-0.5">{{ $a->slug }}</div>
                        </td>
                        <td class="whitespace-nowrap py-3 px-3.5">
                            @if($a->client)
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-primary/10 border border-primary/20 text-primary font-medium text-xs">
                                    <i data-lucide="building-2" class="w-3.5 h-3.5 shrink-0"></i>
                                    <span class="max-w-[130px] truncate" title="{{ $a->client->name }}">{{ $a->client->name }}</span>
                                </div>
                            @elseif($a->client_name && $a->client_name !== 'Independent')
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-primary/10 border border-primary/20 text-primary font-medium text-xs">
                                    <i data-lucide="building-2" class="w-3.5 h-3.5 shrink-0"></i>
                                    <span class="max-w-[130px] truncate" title="{{ $a->client_name }}">{{ $a->client_name }}</span>
                                </div>
                            @else
                                <span class="badge badge-sm badge-ghost text-base-content/50 font-mono">Independent</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap py-3 px-3.5">
                            <button type="button" onclick="viewArticlePrompt({{ $a->id }})" class="group inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-base-200/70 hover:bg-warning/10 border border-base-300 hover:border-warning/40 transition-all text-left cursor-pointer" title="Click to inspect LLM generation prompt">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5 text-warning shrink-0 group-hover:scale-110 transition-transform"></i>
                                <span class="font-medium text-xs text-base-content/90 group-hover:text-warning transition-colors max-w-[160px] truncate block">
                                    {{ $a->prompt_template_info['name'] }}
                                </span>
                                @if($a->prompt_template_info['is_custom'])
                                    <span class="badge badge-xs badge-secondary font-mono text-[9px]">Custom</span>
                                @endif
                            </button>
                        </td>
                        <td class="whitespace-nowrap py-3 px-3.5">
                            <div class="flex items-center gap-2 font-medium text-base-content">
                                <span class="w-6 h-6 rounded-full bg-primary/10 text-primary text-[10px] font-bold flex items-center justify-center shrink-0 border border-primary/20">
                                    {{ strtoupper(substr($a->user?->name ?? 'U', 0, 1)) }}
                                </span>
                                <div>
                                    <span class="font-semibold text-xs text-base-content block">{{ $a->user?->name ?? 'System' }}</span>
                                    @if($a->user?->email)
                                    <span class="text-[10px] text-base-content/50 font-mono block">{{ Str::limit($a->user->email, 20) }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap font-mono text-base-content/80 py-3 px-3.5">{{ number_format($a->word_count) }}</td>
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
                        <td colspan="8" class="text-center py-8 text-base-content/50">
                            No articles found. Try adjusting your filters or create your first piece in the <a href="{{ route('blog.creator') }}" class="text-primary hover:underline">SEO Blog Creator</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-base-300">
            {{ $articles->links() }}
        </div>
    </div>
</div>

<!-- Article Detail Modal -->
<dialog id="article_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-5xl bg-base-100 border border-base-300 text-base-content p-0 shadow-2xl rounded-2xl overflow-hidden max-h-[92vh] flex flex-col">
        <div class="px-6 py-4 border-b border-base-300 bg-base-200/50 flex items-center justify-between">
            <div>
                <h3 id="modal-article-title" class="font-bold text-sm">Article Preview</h3>
                <div id="modal-article-meta" class="text-xs text-base-content/60 font-mono mt-0.5"></div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" id="modal-view-prompt-btn" onclick="openCurrentArticlePrompt()" class="btn btn-xs btn-outline btn-warning gap-1 font-semibold" title="Inspect generation prompt used for this article">
                    <i data-lucide="terminal" class="w-3 h-3"></i> View Prompt
                </button>
                <form method="dialog"><button class="btn btn-xs btn-circle btn-ghost">✕</button></form>
            </div>
        </div>
        <div id="modal-article-body" class="p-6 overflow-y-auto flex-1 prose max-w-none text-base-content">
            Loading...
        </div>
    </div>
</dialog>

<!-- ========================================================================= -->
<!-- Complete LLM Prompt Inspector Modal (DaisyUI 5 Modal Standard) -->
<!-- ========================================================================= -->
<dialog id="article_prompt_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-5xl bg-base-100 border border-base-300 text-base-content p-0 shadow-2xl overflow-hidden max-h-[92vh] flex flex-col rounded-2xl">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-base-300 bg-base-200/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-warning/10 border border-warning/20 flex items-center justify-center text-warning shrink-0">
                    <i data-lucide="terminal" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-bold text-sm text-base-content">LLM Generation Prompt Inspector</h3>
                        <span id="prompt-template-badge" class="badge badge-sm badge-warning badge-outline font-mono">Master Prompt</span>
                        <span id="prompt-model-badge" class="badge badge-sm badge-outline badge-primary font-mono">gemini-3.5-flash</span>
                        <span id="prompt-format-badge" class="badge badge-sm badge-ghost font-mono">Ultimate Guide</span>
                    </div>
                    <p id="prompt-article-title-bar" class="text-[11px] text-base-content/60 mt-0.5">Exact unredacted system directives and user prompts compiled for this article.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="refreshCurrentArticlePrompt()" class="btn btn-xs btn-ghost gap-1 border border-base-300">
                    <i data-lucide="refresh-cw" class="w-3 h-3"></i> Refresh
                </button>
                <form method="dialog">
                    <button class="btn btn-xs btn-circle btn-ghost">✕</button>
                </form>
            </div>
        </div>

        <!-- Tabs Header -->
        <div class="tabs tabs-border bg-base-200/30 px-6 border-b border-base-300 text-xs flex items-center gap-1 overflow-x-auto">
            <button type="button" onclick="switchArticlePromptTab('tab-p-master', this)" class="tab prompt-modal-tab tab-active font-semibold py-2.5 transition-all cursor-pointer border-b-2 border-primary text-primary" data-target="tab-p-master">
                <i data-lucide="sparkles" class="w-3.5 h-3.5 mr-1.5"></i> Master Article Prompt
            </button>
            <button type="button" onclick="switchArticlePromptTab('tab-p-outline', this)" class="tab prompt-modal-tab font-semibold py-2.5 transition-all cursor-pointer text-base-content/70 hover:text-base-content" data-target="tab-p-outline">
                <i data-lucide="layers" class="w-3.5 h-3.5 mr-1.5"></i> Pass 1: Outline
            </button>
            <button type="button" onclick="switchArticlePromptTab('tab-p-sections', this)" class="tab prompt-modal-tab font-semibold py-2.5 transition-all cursor-pointer text-base-content/70 hover:text-base-content" data-target="tab-p-sections">
                <i data-lucide="code" class="w-3.5 h-3.5 mr-1.5"></i> Pass 2: Section Writing
            </button>
            <button type="button" onclick="switchArticlePromptTab('tab-p-metadata', this)" class="tab prompt-modal-tab font-semibold py-2.5 transition-all cursor-pointer text-base-content/70 hover:text-base-content" data-target="tab-p-metadata">
                <i data-lucide="file-text" class="w-3.5 h-3.5 mr-1.5"></i> Pass 3: Metadata
            </button>
            <button type="button" onclick="switchArticlePromptTab('tab-p-params', this)" class="tab prompt-modal-tab font-semibold py-2.5 transition-all cursor-pointer text-base-content/70 hover:text-base-content" data-target="tab-p-params">
                <i data-lucide="sliders" class="w-3.5 h-3.5 mr-1.5"></i> Input Parameters & Context
            </button>
        </div>

        <!-- Section Sub-Tabs (Shown when Pass 2 is active) -->
        <div id="article-section-sub-tabs" class="hidden items-center gap-1.5 px-6 py-2 bg-base-200/80 border-b border-base-300 overflow-x-auto text-xs">
            <span class="text-[11px] text-base-content/60 mr-2 shrink-0 font-semibold">Section:</span>
            <button type="button" onclick="switchArticleSectionPrompt('intro', this)" class="btn btn-xs btn-primary art-sec-tab">Intro</button>
            <button type="button" onclick="switchArticleSectionPrompt('standard', this)" class="btn btn-xs btn-ghost art-sec-tab">Standard Body</button>
            <button type="button" onclick="switchArticleSectionPrompt('comparison-table', this)" class="btn btn-xs btn-ghost art-sec-tab">Comparison Table</button>
            <button type="button" onclick="switchArticleSectionPrompt('faq', this)" class="btn btn-xs btn-ghost art-sec-tab">FAQ</button>
            <button type="button" onclick="switchArticleSectionPrompt('conclusion', this)" class="btn btn-xs btn-ghost art-sec-tab">Conclusion</button>
            <button type="button" onclick="switchArticleSectionPrompt('cta', this)" class="btn btn-xs btn-ghost art-sec-tab">Client CTA</button>
        </div>

        <!-- Tab Content & Previews -->
        <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-base-100">
            <!-- Custom Template Directive Banner (Conditional) -->
            <div id="custom-directive-banner" class="hidden alert alert-warning/15 border border-warning/30 p-3 rounded-xl text-xs flex items-start gap-2.5">
                <i data-lucide="alert-circle" class="w-4 h-4 text-warning shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <span class="font-bold text-warning block" id="custom-directive-title">Custom Blueprint Directives Active</span>
                    <p class="text-base-content/80 text-[11px] mt-0.5" id="custom-directive-desc">Custom system directives and style constraints were injected into this article's prompt.</p>
                </div>
            </div>

            <!-- Stats & Copy Action Bar (For Prompts) -->
            <div id="prompt-action-bar" class="flex items-center justify-between p-3 bg-base-200/60 border border-base-300 rounded-xl">
                <div id="article-prompt-stats" class="text-xs text-base-content/70 font-mono">Master Prompt: Complete Single-Pass Directives</div>
                <div class="flex gap-2">
                    <button type="button" onclick="copyArticlePromptSection('article-pre-sys-prompt', this)" class="btn btn-xs btn-ghost border border-base-300">Copy System</button>
                    <button type="button" onclick="copyArticlePromptSection('article-pre-user-prompt', this)" class="btn btn-xs btn-ghost border border-base-300">Copy User</button>
                    <button type="button" onclick="copyCompleteArticlePrompt(this)" class="btn btn-xs btn-warning font-bold">Copy Complete</button>
                </div>
            </div>

            <!-- Prompt Code Panels (Shown for Tabs 1-4) -->
            <div id="prompt-panels-container" class="space-y-4">
                <!-- System Prompt Card -->
                <div class="card bg-base-200/40 border border-base-300 shadow-xs">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="text-xs font-mono font-bold text-emerald-500 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> <span id="art-sys-prompt-header-title">System Directives & Editorial Rules</span>
                            </h4>
                            <span id="art-sys-prompt-count" class="text-[10px] font-mono text-base-content/50"></span>
                        </div>
                        <pre id="article-pre-sys-prompt" class="text-xs font-mono text-base-content/90 whitespace-pre-wrap leading-relaxed max-h-64 overflow-y-auto bg-base-300/40 p-3.5 rounded-lg border border-base-300/50"></pre>
                    </div>
                </div>

                <!-- User Prompt Card -->
                <div class="card bg-base-200/40 border border-base-300 shadow-xs">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="text-xs font-mono font-bold text-indigo-500 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span> <span id="art-user-prompt-header-title">Compiled User Prompt Context</span>
                            </h4>
                            <span id="art-user-prompt-count" class="text-[10px] font-mono text-base-content/50"></span>
                        </div>
                        <pre id="article-pre-user-prompt" class="text-xs font-mono text-base-content/90 whitespace-pre-wrap leading-relaxed max-h-64 overflow-y-auto bg-base-300/40 p-3.5 rounded-lg border border-base-300/50"></pre>
                    </div>
                </div>
            </div>

            <!-- Parameters Grid Panel (Shown for Tab 5) -->
            <div id="parameters-panel" class="hidden space-y-4">
                <div class="card bg-base-200/40 border border-base-300 shadow-xs">
                    <div class="card-body p-4">
                        <h4 class="text-xs font-mono font-bold text-primary uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <i data-lucide="sliders" class="w-3.5 h-3.5 text-primary"></i> Generation Parameters & Context Metadata
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3" id="params-grid-content">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    let currentArticleId = null;
    let currentPromptArticleId = null;
    let currentPromptData = null;
    let activePromptTab = 'tab-p-master';
    let activeSectionSubTab = 'intro';

    function viewArticleDetails(id) {
        currentArticleId = id;
        document.getElementById('article_modal').showModal();
        $('#modal-article-body').html('<span class="loading loading-spinner loading-sm"></span> Loading article...');
        $('#modal-article-meta').text('');

        $.ajax({
            url: "/articles/" + id,
            type: 'GET',
            success: function(res) {
                const article = res.article || res;
                $('#modal-article-title').text(article.title);

                const authorName = (article.user && article.user.name) ? article.user.name : 'Unknown';
                const createdDate = article.created_at ? new Date(article.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
                const promptName = article.prompt_template_name || (article.prompt_template_info ? article.prompt_template_info.name : 'Master Directive');
                $('#modal-article-meta').text('Prompt: ' + promptName + ' • Created by ' + authorName + (createdDate ? ' on ' + createdDate : '') + ' • ' + (article.word_count || 0).toLocaleString() + ' words • SEO: ' + (article.seo_score || 0) + '/100');

                $('#modal-article-body').html(article.html_content);
                window.refreshIcons();
            },
            error: function() {
                $('#modal-article-body').html('<p class="text-error">Failed to load article content.</p>');
            }
        });
    }

    function openCurrentArticlePrompt() {
        if (currentArticleId) {
            viewArticlePrompt(currentArticleId);
        }
    }

    function viewArticlePrompt(id) {
        currentPromptArticleId = id;
        document.getElementById('article_prompt_modal').showModal();
        $('#article-pre-sys-prompt').text('Loading compiled prompt directives from server...');
        $('#article-pre-user-prompt').text('Loading user context...');
        $('#article-prompt-stats').text('Loading prompt metadata...');
        $('#custom-directive-banner').addClass('hidden');

        $.ajax({
            url: "/articles/" + id + "/prompt",
            type: 'GET',
            success: function(res) {
                currentPromptData = res;
                $('#prompt-article-title-bar').text('Directives compiled for: "' + (res.article_title || '') + '"');
                $('#prompt-template-badge').text(res.template_name || 'Comprehensive Master Prompt');
                if (res.is_custom) {
                    $('#prompt-template-badge').removeClass('badge-warning').addClass('badge-secondary').text((res.template_name || 'Custom') + ' (Custom)');
                    $('#custom-directive-banner').removeClass('hidden');
                    $('#custom-directive-title').text('Custom Blueprint: ' + res.template_name);
                    $('#custom-directive-desc').text('This article was generated with custom prompt instructions tailoring structure, style, and tone.');
                } else {
                    $('#prompt-template-badge').removeClass('badge-secondary').addClass('badge-warning');
                    $('#custom-directive-banner').addClass('hidden');
                }

                $('#prompt-model-badge').text(res.parameters?.model || 'gemini-3.5-flash');
                $('#prompt-format-badge').text(res.parameters?.format || 'Ultimate Guide');

                renderActiveArticlePromptView();
                renderParametersView(res.parameters, res);
                window.refreshIcons();
            },
            error: function(xhr) {
                $('#article-pre-sys-prompt').text('Failed to load prompt: ' + (xhr.responseJSON?.message || 'Server error'));
                $('#article-pre-user-prompt').text('');
                $('#article-prompt-stats').text('Error loading prompt.');
            }
        });
    }

    function refreshCurrentArticlePrompt() {
        if (currentPromptArticleId) {
            viewArticlePrompt(currentPromptArticleId);
        }
    }

    function switchArticlePromptTab(tabId, el) {
        activePromptTab = tabId;

        $('.prompt-modal-tab')
            .removeClass('tab-active border-b-2 border-primary text-primary font-bold')
            .addClass('text-base-content/70');

        if (el) {
            $(el).removeClass('text-base-content/70')
                .addClass('tab-active border-b-2 border-primary text-primary font-bold');
        } else {
            $('.prompt-modal-tab[data-target="' + tabId + '"]')
                .removeClass('text-base-content/70')
                .addClass('tab-active border-b-2 border-primary text-primary font-bold');
        }

        if (tabId === 'tab-p-sections') {
            $('#article-section-sub-tabs').removeClass('hidden').addClass('flex');
        } else {
            $('#article-section-sub-tabs').addClass('hidden').removeClass('flex');
        }

        if (tabId === 'tab-p-params') {
            $('#prompt-action-bar').addClass('hidden');
            $('#prompt-panels-container').addClass('hidden');
            $('#parameters-panel').removeClass('hidden');
        } else {
            $('#prompt-action-bar').removeClass('hidden');
            $('#prompt-panels-container').removeClass('hidden');
            $('#parameters-panel').addClass('hidden');
            renderActiveArticlePromptView();
        }
    }

    function switchArticleSectionPrompt(secKey, el) {
        activeSectionSubTab = secKey;
        $('.art-sec-tab').removeClass('btn-primary').addClass('btn-ghost');
        if (el) {
            $(el).removeClass('btn-ghost').addClass('btn-primary');
        }
        renderActiveArticlePromptView();
    }

    function renderActiveArticlePromptView() {
        if (!currentPromptData) return;

        let sys = '';
        let usr = '';

        if (activePromptTab === 'tab-p-master') {
            sys = currentPromptData.master_prompt?.system || 'No master system directives found.';
            usr = currentPromptData.master_prompt?.user || 'No master user prompt found.';
            $('#art-sys-prompt-header-title').text('Master System Directives & Editorial Rules');
            $('#art-user-prompt-header-title').text('Compiled User Prompt Context');
            $('#article-prompt-stats').text('Master Prompt: Complete Single-Pass Directives (' + sys.length.toLocaleString() + ' chars sys / ' + usr.length.toLocaleString() + ' chars user)');
        } else if (activePromptTab === 'tab-p-outline') {
            sys = currentPromptData.outline_prompt?.system || 'No outline system directives found.';
            usr = currentPromptData.outline_prompt?.user || 'No outline user prompt found.';
            $('#art-sys-prompt-header-title').text('Pass 1: Outline Architecture System Directives');
            $('#art-user-prompt-header-title').text('Pass 1: Outline Schema & Talking Points');
            $('#article-prompt-stats').text('Pass 1: Outline Architecture Engine (' + sys.length.toLocaleString() + ' chars sys / ' + usr.length.toLocaleString() + ' chars user)');
        } else if (activePromptTab === 'tab-p-sections') {
            const sec = currentPromptData.section_prompts?.[activeSectionSubTab];
            sys = sec?.system || 'No section system directives found.';
            usr = sec?.user || 'No section user prompt found.';
            const label = activeSectionSubTab.replace('-', ' ').toUpperCase();
            $('#art-sys-prompt-header-title').text('Pass 2: ' + label + ' System Directives');
            $('#art-user-prompt-header-title').text('Pass 2: ' + label + ' Section Context & Prompts');
            $('#article-prompt-stats').text('Pass 2: Section Copywriting [' + label + '] (' + sys.length.toLocaleString() + ' chars sys / ' + usr.length.toLocaleString() + ' chars user)');
        } else if (activePromptTab === 'tab-p-metadata') {
            sys = currentPromptData.metadata_prompt?.system || 'No metadata system directives found.';
            usr = currentPromptData.metadata_prompt?.user || 'No metadata user prompt found.';
            $('#art-sys-prompt-header-title').text('Pass 3: Meta & Schema System Directives');
            $('#art-user-prompt-header-title').text('Pass 3: Metadata Synthesis Payload');
            $('#article-prompt-stats').text('Pass 3: Meta Title, Meta Description & JSON-LD FAQ Schema');
        }

        $('#article-pre-sys-prompt').text(sys);
        $('#article-pre-user-prompt').text(usr);
        $('#art-sys-prompt-count').text(sys.length.toLocaleString() + ' characters');
        $('#art-user-prompt-count').text(usr.length.toLocaleString() + ' characters');
    }

    function renderParametersView(params, res) {
        if (!params) return;

        const items = [
            { label: 'Prompt Blueprint', value: res.template_name, badge: res.is_custom ? 'Custom' : 'System' },
            { label: 'Article Topic', value: params.topic },
            { label: 'Focus Keyword', value: params.primary_keyword, highlight: true },
            { label: 'Secondary Keywords', value: params.secondary_keywords },
            { label: 'Article Format', value: params.format },
            { label: 'Target Length', value: params.word_count },
            { label: 'Brand / Tone', value: params.tone },
            { label: 'Point of View', value: params.pov },
            { label: 'Industry / Domain', value: params.industry },
            { label: 'Target Audience', value: params.target_audience },
            { label: 'Agency Client Context', value: params.client_name },
            { label: 'AI Model Dispatched', value: params.model || 'gemini-3.5-flash' },
            { label: 'AI Humanizer Active', value: params.humanizer_active ? 'Yes (Post-processing polish)' : 'No' },
            { label: 'SERP Competitor Scrape', value: params.serp_crawler_active ? 'Yes (Competitor Outlines Injected)' : 'No' },
            { label: 'Prompt Tokens / Completion', value: (params.prompt_tokens || 0).toLocaleString() + ' in / ' + (params.completion_tokens || 0).toLocaleString() + ' out' }
        ];

        let html = '';
        items.forEach(item => {
            html += `
                <div class="p-3 bg-base-100/70 border border-base-300 rounded-xl space-y-1">
                    <div class="text-[10px] font-mono text-base-content/50 uppercase tracking-wider">${item.label}</div>
                    <div class="text-xs font-semibold text-base-content flex items-center justify-between gap-1">
                        <span class="${item.highlight ? 'text-primary font-mono' : ''}">${item.value || 'Not specified'}</span>
                        ${item.badge ? `<span class="badge badge-xs badge-secondary">${item.badge}</span>` : ''}
                    </div>
                </div>
            `;
        });

        $('#params-grid-content').html(html);
    }

    function copyArticlePromptSection(elemId, btn) {
        const text = $('#' + elemId).text();
        navigator.clipboard.writeText(text).then(() => {
            const orig = $(btn).text();
            $(btn).text('Copied!').addClass('btn-success text-white');
            setTimeout(() => {
                $(btn).text(orig).removeClass('btn-success text-white');
            }, 1500);
        });
    }

    function copyCompleteArticlePrompt(btn) {
        const sys = $('#article-pre-sys-prompt').text();
        const usr = $('#article-pre-user-prompt').text();
        const complete = "=== SYSTEM DIRECTIVES ===\n" + sys + "\n\n=== USER PROMPT CONTEXT ===\n" + usr;
        navigator.clipboard.writeText(complete).then(() => {
            const orig = $(btn).html();
            $(btn).text('Copied!').addClass('btn-success text-white');
            setTimeout(() => {
                $(btn).html(orig).removeClass('btn-success text-white');
            }, 1500);
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

    function deleteArticleRecord(id) {
        if (!confirm('Are you sure you want to delete this article?')) return;

        $.ajax({
            url: "/articles/" + id,
            type: 'DELETE',
            success: function(res) {
                showToast(res.message || 'Article deleted successfully.', 'success');
                setTimeout(() => window.location.reload(), 300);
            },
            error: function() {
                showToast('Failed to delete article.', 'error');
            }
        });
    }

    window.refreshIcons = function() {
        if (typeof window.createIcons === 'function') {
            window.createIcons();
        } else if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons();
        }
    };
</script>
@endpush

