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
            <div class="relative flex-1 min-w-[220px]">
                <i data-lucide="search" class="w-4 h-4 text-base-content/40 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or meta keywords..." class="input input-bordered input-sm w-full pl-9 bg-base-200/50 text-xs rounded-lg focus:outline-none focus:border-primary" />
            </div>
            <div class="w-40">
                <select name="min_score" class="select select-bordered select-sm w-full bg-base-200/50 text-xs rounded-lg focus:outline-none focus:border-primary">
                    <option value="">Any SEO Score</option>
                    <option value="80" {{ request('min_score') == '80' ? 'selected' : '' }}>80+ High Score</option>
                    <option value="60" {{ request('min_score') == '60' ? 'selected' : '' }}>60+ Passing Score</option>
                </select>
            </div>
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
                        <th class="w-36 text-nowrap py-3 px-3.5">Actions</th>
                        <th class="text-nowrap py-3 px-3.5">Article Title</th>
                        <th class="text-nowrap py-3 px-3.5">Words</th>
                        <th class="text-nowrap py-3 px-3.5">SEO Score</th>
                        <th class="text-nowrap py-3 px-3.5">Reading Ease</th>
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
                                <a href="{{ route('articles.download', ['id' => $a->id, 'format' => 'html']) }}" class="btn btn-xs btn-square btn-outline btn-info rounded-lg" title="Download HTML">
                                    <i data-lucide="code" class="w-3.5 h-3.5"></i>
                                </a>
                                <a href="{{ route('articles.download', ['id' => $a->id, 'format' => 'docx']) }}" class="btn btn-xs btn-square btn-outline btn-secondary rounded-lg" title="Download Word Document">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                </a>
                                <button type="button" onclick="publishArticleToWP({{ $a->id }}, this)" class="btn btn-xs btn-square btn-outline btn-success rounded-lg" title="Publish to Client's WordPress">
                                    <i data-lucide="share-2" class="w-3.5 h-3.5"></i>
                                </button>
                                <button type="button" onclick="deleteArticleRecord({{ $a->id }})" class="btn btn-xs btn-square btn-outline btn-error rounded-lg" title="Delete Article">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </td>
                        <td class="font-bold text-base-content whitespace-nowrap py-3 px-3.5">
                            <div class="hover:text-primary transition-colors cursor-pointer" onclick="viewArticleDetails({{ $a->id }})">{{ Str::limit($a->title, 65) }}</div>
                            <div class="text-[10px] text-base-content/50 font-normal font-mono mt-0.5">{{ $a->slug }}</div>
                        </td>
                        <td class="whitespace-nowrap font-mono text-base-content/80 py-3 px-3.5">{{ number_format($a->word_count) }}</td>
                        <td class="whitespace-nowrap py-3 px-3.5">
                            <span class="badge badge-sm badge-success font-mono font-bold">{{ $a->seo_score }}/100</span>
                        </td>
                        <td class="whitespace-nowrap py-3 px-3.5">
                            <span class="badge badge-sm badge-info font-mono font-bold">{{ $a->flesch_reading_ease }}</span>
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
                        <td colspan="7" class="text-center py-8 text-base-content/50">
                            No articles generated yet. Create your first piece in the <a href="{{ route('blog.creator') }}" class="text-primary hover:underline">SEO Blog Creator</a>.
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
</script>
@endpush
