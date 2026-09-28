@extends('layouts.app')

@section('title', 'Creator Studio Dashboard')
@section('page_title', 'Creator Studio')
@section('page_badge', 'My Workspace')

@section('content')
<div class="space-y-6">
    <!-- Header Blueprint (Rule 13 Benchmark) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="badge badge-success badge-outline badge-sm font-mono flex items-center gap-1">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    <span>Creator Studio</span>
                </span>
                <span class="text-xs text-base-content/60">Personal Writing Workspace</span>
            </div>
            <h1 class="text-xl font-black text-base-content mt-1">Welcome back, {{ $user->name }}</h1>
            <p class="text-xs text-base-content/60 mt-0.5">Your personal copywriting output, active agency brand directives, and recent drafts.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @role('super_admin|admin')
            <a href="{{ route('dashboard', ['view' => 'admin']) }}" class="btn btn-sm btn-ghost border border-base-300 text-xs font-semibold gap-1.5" title="Switch to Admin Executive Command Center">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-primary"></i>
                <span>Admin View</span>
            </a>
            @endrole
            <a href="{{ route('rewriter.index') }}" class="btn btn-sm btn-ghost border border-base-300 text-xs font-semibold gap-1.5">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-amber-500"></i>
                <span>Rewriter</span>
            </a>
            <a href="{{ route('blog.creator') }}" class="btn btn-sm btn-primary font-bold shadow-xs gap-1.5">
                <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                <span>Write New Article</span>
            </a>
        </div>
    </div>

    <!-- Personal Telemetry KPI Metric Cards (Rule 13 5-Column Grid) -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <!-- 1. My Articles Created -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-primary/10 text-primary border border-primary/20 font-mono font-semibold rounded-full px-2 py-0.5">My Output</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Articles Written</div>
                    <div class="text-2xl font-black text-base-content mt-1">{{ number_format($metrics['myArticlesCount']) }}</div>
                    <div class="text-[11px] text-base-content/60 font-mono mt-0.5">Drafts & final copy</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-primary"></div>
        </div>

        <!-- 2. Words Generated -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-cyan-500/10 text-cyan-500 border border-cyan-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Word Count</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Words Produced</div>
                    <div class="text-2xl font-black text-cyan-500 mt-1">{{ number_format($metrics['myTotalWords']) }}</div>
                    <div class="text-[11px] text-base-content/60 font-mono mt-0.5">Total long-form copy</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-500 flex items-center justify-center shrink-0">
                    <i data-lucide="align-left" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-cyan-500"></div>
        </div>

        <!-- 3. My Average SEO Score -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 font-mono font-semibold rounded-full px-2 py-0.5">SEO Quality</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Avg SEO Score</div>
                    <div class="text-2xl font-black text-emerald-500 mt-1">{{ $metrics['myAvgSeoScore'] }}<span class="text-xs font-normal text-base-content/50">/100</span></div>
                    <div class="text-[11px] text-base-content/60 font-mono mt-0.5">E-E-A-T benchmark</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0">
                    <i data-lucide="award" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-emerald-500"></div>
        </div>

        <!-- 4. Reading Ease -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-indigo-500/10 text-indigo-500 border border-indigo-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Readability</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">Avg Reading Ease</div>
                    <div class="text-2xl font-black text-indigo-500 mt-1">{{ $metrics['myAvgReadingEase'] }}</div>
                    <div class="text-[11px] text-base-content/60 font-mono mt-0.5">Flesch Grade scale</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center shrink-0">
                    <i data-lucide="book-open" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-indigo-500"></div>
        </div>

        <!-- 5. WordPress Sync -->
        <div class="card bg-base-100 border border-base-300 p-4 rounded-2xl shadow-xs relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge badge-xs bg-amber-500/10 text-amber-500 border border-amber-500/20 font-mono font-semibold rounded-full px-2 py-0.5">Distribution</span>
                    <div class="text-xs text-base-content/60 font-semibold mt-1">WP Published</div>
                    <div class="text-2xl font-black text-amber-500 mt-1">{{ $metrics['myWpPublished'] }}</div>
                    <div class="text-[11px] text-base-content/60 font-mono mt-0.5">{{ $metrics['myRewriterJobsCount'] }} web rewrites</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0">
                    <i data-lucide="share-2" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-amber-500"></div>
        </div>
    </div>

    <!-- Active Client Brand Voice Directive Banner -->
    <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-5 relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl {{ $activeClient ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-base-200 text-base-content/50 border border-base-300' }} flex items-center justify-center shrink-0">
                    <i data-lucide="building-2" class="w-6 h-6"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-base-content/60 font-mono">Active Brand Directive:</span>
                        @if($activeClient)
                            <span class="badge badge-sm badge-success font-mono font-bold">{{ $activeClient->name }}</span>
                        @else
                            <span class="badge badge-sm badge-ghost text-base-content/60 font-mono">Generic Mode (Independent)</span>
                        @endif
                    </div>

                    @if($activeClient)
                    <div class="mt-1 text-xs text-base-content/80 flex flex-wrap items-center gap-x-4 gap-y-1">
                        <div><strong class="text-base-content">Industry:</strong> {{ $activeClient->industry ?: 'General' }}</div>
                        <div><strong class="text-base-content">Brand Voice:</strong> <span class="italic text-primary font-medium">"{{ $activeClient->brand_tone ?: 'Authoritative & Empathetic' }}"</span></div>
                        @php $sitemapLinks = is_array($activeClient->sitemap_cache) ? count($activeClient->sitemap_cache) : 0; @endphp
                        <div><strong class="text-base-content">Internal Links:</strong> <span class="font-mono text-emerald-500 font-bold">{{ $sitemapLinks }} links cached</span></div>
                    </div>
                    @if(!empty($activeClient->cta_default))
                        <div class="mt-1.5 text-[11px] text-base-content/60">
                            <strong>Default CTA:</strong> {{ Str::limit($activeClient->cta_default, 100) }}
                        </div>
                    @endif
                    @else
                    <p class="mt-1 text-xs text-base-content/60">
                        Writing without client bias. Articles will use neutral, authoritative tone without company CTAs or internal links.
                    </p>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-sm btn-ghost border border-base-300 text-xs font-semibold gap-1.5">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Switch Client</span>
                    </label>
                    <ul tabindex="0" class="dropdown-content menu p-2 shadow-xl bg-base-100 border border-base-300 rounded-box w-56 z-50 text-xs">
                        <li>
                            <button type="button" onclick="switchGlobalClient('')" class="{{ !$activeClient ? 'active font-bold' : '' }}">
                                Generic Mode (None)
                            </button>
                        </li>
                        <div class="divider my-1"></div>
                        @foreach($allClients as $cl)
                        <li>
                            <button type="button" onclick="switchGlobalClient('{{ $cl->id }}')" class="{{ $activeClient && $activeClient->id === $cl->id ? 'active font-bold' : '' }}">
                                {{ $cl->name }}
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <a href="{{ route('blog.creator') }}" class="btn btn-sm btn-primary font-bold shadow-xs gap-1.5">
                    <i data-lucide="play" class="w-3.5 h-3.5"></i>
                    <span>Start Writing</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main 2-Column Content Studio Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Column: My Recent Articles (8 Cols) -->
        <div class="lg:col-span-8 space-y-6">
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-base-300 flex items-center justify-between">
                    <div>
                        <h2 class="font-extrabold text-sm text-base-content flex items-center gap-2">
                            <i data-lucide="file-text" class="w-4 h-4 text-primary"></i>
                            <span>My Recent Articles</span>
                        </h2>
                        <p class="text-[11px] text-base-content/60 mt-0.5">Quick access to review, export, or publish your latest articles</p>
                    </div>
                    <a href="{{ route('articles.index') }}" class="btn btn-xs btn-ghost gap-1 text-primary">
                        <span>All Articles</span>
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
                                        <button type="button" onclick="viewArticleDetails({{ $a->id }})" class="btn btn-xs btn-square btn-outline btn-primary rounded-lg" title="View Article Preview">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <a href="{{ route('articles.download', ['id' => $a->id, 'format' => 'docx']) }}" class="btn btn-xs btn-square btn-outline btn-secondary rounded-lg" title="Download Word DOCX">
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
                                <td colspan="6" class="text-center py-6 text-base-content/50">
                                    No articles written yet. <a href="{{ route('blog.creator') }}" class="text-primary hover:underline">Write your first SEO article</a>.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: Quick Article Launcher & Rewriter Activity (4 Cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- 1. Quick-Start Article Generator Launcher -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-4">
                <div class="flex items-center justify-between pb-3 border-b border-base-300">
                    <h2 class="font-extrabold text-sm text-base-content flex items-center gap-2">
                        <i data-lucide="zap" class="w-4 h-4 text-amber-500"></i>
                        <span>Quick Article Launcher</span>
                    </h2>
                    <span class="badge badge-primary badge-xs font-mono">Fast Track</span>
                </div>

                <form method="GET" action="{{ route('blog.creator') }}" class="space-y-3 mt-3">
                    <div>
                        <label class="label py-1 text-xs font-bold text-base-content">Article Topic / Keyword</label>
                        <input type="text" name="topic" placeholder="e.g. 10 Proven Strategies for Cloud Cost Optimization" class="input input-bordered input-sm w-full bg-base-200/50 text-xs rounded-lg focus:outline-none focus:border-primary" required />
                    </div>

                    <div>
                        <label class="label py-1 text-xs font-bold text-base-content">Primary Target Keyword</label>
                        <input type="text" name="keyword" placeholder="e.g. cloud cost reduction" class="input input-bordered input-sm w-full bg-base-200/50 text-xs rounded-lg focus:outline-none focus:border-primary" />
                    </div>

                    <button type="submit" class="btn btn-sm btn-primary w-full font-bold shadow-xs gap-1.5 mt-1">
                        <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                        <span>Open in Blog Creator</span>
                    </button>
                </form>
            </div>

            <!-- 2. Recent Rewriter Studio Jobs -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-4">
                <div class="flex items-center justify-between pb-3 border-b border-base-300">
                    <h2 class="font-extrabold text-sm text-base-content flex items-center gap-2">
                        <i data-lucide="refresh-cw" class="w-4 h-4 text-amber-500"></i>
                        <span>Recent URL Rewrites</span>
                    </h2>
                    <a href="{{ route('rewriter.index') }}" class="text-[11px] text-primary hover:underline font-mono">Open</a>
                </div>

                <div class="space-y-2 mt-3">
                    @forelse($recentRewriterJobs as $job)
                    <div class="p-2.5 rounded-xl bg-base-200/50 border border-base-300 flex items-center justify-between text-xs">
                        <div class="truncate max-w-[180px]">
                            <div class="font-bold text-base-content truncate">{{ $job->source_url }}</div>
                            <div class="text-[10px] text-base-content/60 font-mono">{{ ucfirst($job->mode) }} &bull; {{ $job->created_at->diffForHumans() }}</div>
                        </div>
                        <div>
                            @if($job->status === 'completed')
                                <span class="badge badge-xs badge-success font-mono">Ready</span>
                            @elseif($job->status === 'processing')
                                <span class="badge badge-xs badge-warning font-mono">Rewriting</span>
                            @else
                                <span class="badge badge-xs badge-error font-mono">Failed</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-xs text-base-content/50">
                        No URL rewrites initiated yet. <a href="{{ route('rewriter.index') }}" class="text-primary hover:underline">Launch Rewriter</a>.
                    </div>
                    @endforelse
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
