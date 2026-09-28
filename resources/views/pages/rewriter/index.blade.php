@extends('layouts.app')

@section('title', 'Rewriter Studio')
@section('page_title', 'Rewriter Studio')
@section('page_badge', 'Layout-Preserving URL Rewriter')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="badge badge-primary badge-outline badge-sm font-mono">Live Scraper</span>
                <span class="text-xs text-base-content/60">DOM Structure & CSS Layout Preservation</span>
            </div>
            <h1 class="text-xl font-black text-base-content mt-1">Web Page Rewriter Studio</h1>
        </div>
    </div>

    <!-- Scraper Form Card -->
    <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-5">
        <h2 class="text-sm font-bold text-base-content mb-1">Scrape & Rewrite Web Pages</h2>
        <p class="text-xs text-base-content/60 mb-4">Provide any public URL to extract the main article and rewrite it while retaining headings, bullet points, and layout.</p>

        <form id="rewriter-form" class="space-y-4">
            @csrf
            <div>
                <label class="label py-0.5 text-xs font-semibold">Target Web Page URL(s) <span class="text-error">*</span></label>
                <textarea id="source_url" name="source_url" rows="2" required
                          placeholder="https://example.com/blog/great-article"
                          class="textarea textarea-bordered textarea-sm w-full bg-base-200/50 text-xs font-mono"></textarea>
                <span class="text-[10px] text-base-content/50">Enter one or more URLs separated by line breaks.</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label py-0.5 text-xs font-semibold">Rewrite Mode</label>
                    <select id="rewriter_mode" name="rewriter_mode" class="select select-bordered select-sm w-full bg-base-200/50 text-xs">
                        <option value="layout-preserving" selected>Layout-Preserving (Retain DOM structure, headings, tables)</option>
                        <option value="semantic-clean">Semantic Clean (Extract raw text and re-architect entirely)</option>
                    </select>
                </div>
                <div>
                    <label class="label py-0.5 text-xs font-semibold">Custom Rewrite Instructions</label>
                    <input type="text" id="custom_instructions" name="custom_instructions"
                           placeholder="e.g. Elevate vocabulary, update statistics for 2026, add clinical citations"
                           class="input input-bordered input-sm w-full bg-base-200/50 text-xs" />
                </div>
            </div>

            <div class="flex items-center justify-end pt-2">
                <button type="button" onclick="startRewrite(this)" class="btn btn-primary btn-sm gap-2 font-bold shadow-xs">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i> Start Rewrite Execution
                </button>
            </div>
        </form>
    </div>

    <!-- Rewriter Jobs Data Table -->
    <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
        <div class="p-4 border-b border-base-300 flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-base-content/70">Recent Rewrite Jobs</h3>
            <button type="button" onclick="window.location.reload()" class="btn btn-ghost btn-xs gap-1">
                <i data-lucide="refresh-cw" class="w-3 h-3"></i> Refresh
            </button>
        </div>

        <div class="table-responsive overflow-x-auto">
            <table class="table table-hover align-middle mb-0 text-xs border-top w-full">
                <thead class="bg-base-200 text-base-content/70 border-b border-base-300 font-mono uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="w-24 text-nowrap">Actions</th>
                        <th class="text-nowrap">ID</th>
                        <th class="text-nowrap">Source URL</th>
                        <th class="text-nowrap">Created By</th>
                        <th class="text-nowrap">Mode</th>
                        <th class="text-nowrap">Status</th>
                        <th class="text-nowrap">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-300/50">
                    @forelse($jobs as $job)
                    <tr class="hover:bg-base-200/40 transition-colors">
                        <td class="whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                @if($job->status === 'completed')
                                <button type="button" onclick="viewRewrittenContent({{ $job->id }})" class="btn btn-xs btn-square btn-outline btn-primary rounded-lg" title="View Result">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                </button>
                                <a href="{{ route('rewriter.download', ['id' => $job->id, 'format' => 'html']) }}" class="btn btn-xs btn-square btn-outline btn-info rounded-lg" title="Download HTML">
                                    <i data-lucide="code" class="w-3.5 h-3.5"></i>
                                </a>
                                <a href="{{ route('rewriter.download', ['id' => $job->id, 'format' => 'docx']) }}" class="btn btn-xs btn-square btn-outline btn-secondary rounded-lg" title="Download DOCX">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                </a>
                                @endif
                                @hasanyrole('admin|super_admin')
                                <button type="button" onclick="deleteRewriterJob({{ $job->id }})" class="btn btn-xs btn-square btn-outline btn-error rounded-lg" title="Delete">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                                @endhasanyrole
                            </div>
                        </td>
                        <td class="font-mono whitespace-nowrap">#{{ $job->id }}</td>
                        <td class="whitespace-nowrap">
                            <a href="{{ $job->source_url }}" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline flex items-center gap-1 truncate max-w-sm">
                                <span>{{ $job->source_url }}</span>
                                <i data-lucide="external-link" class="w-3 h-3 shrink-0"></i>
                            </a>
                        </td>
                        <td class="whitespace-nowrap">
                            <div class="flex items-center gap-1.5 font-medium text-base-content">
                                <span class="w-5 h-5 rounded-full bg-primary/10 text-primary text-[10px] font-bold flex items-center justify-center shrink-0 border border-primary/20">
                                    {{ strtoupper(substr($job->user?->name ?? 'U', 0, 1)) }}
                                </span>
                                <span class="font-semibold text-xs">{{ $job->user?->name ?? 'System' }}</span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap font-mono text-[11px]">{{ $job->rewriter_mode }}</td>
                        <td class="whitespace-nowrap">
                            @if($job->status === 'completed')
                                <span class="badge badge-sm badge-success">Completed</span>
                            @elseif($job->status === 'failed')
                                <span class="badge badge-sm badge-error">Failed</span>
                            @else
                                <span class="badge badge-sm badge-warning animate-pulse">Processing</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap font-mono text-base-content/60">{{ $job->created_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-base-content/50">
                            No rewriter jobs executed yet. Paste a URL above to begin.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-base-300">
            {{ $jobs->links() }}
        </div>
    </div>
</div>

<!-- View Content Modal -->
<dialog id="view_rewriter_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-5xl bg-base-100 border border-base-300 text-base-content p-0 shadow-2xl rounded-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b border-base-300 bg-base-200/50 flex items-center justify-between">
            <h3 class="font-bold text-sm">Rewritten Article Result</h3>
            <form method="dialog"><button class="btn btn-xs btn-circle btn-ghost">✕</button></form>
        </div>
        <div id="rewritten-modal-body" class="p-6 overflow-y-auto flex-1 prose max-w-none text-base-content">
            Loading...
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    function startRewrite(btn) {
        const url = $('#source_url').val().trim();
        if (!url) {
            showToast('Please enter a target URL.', 'warning');
            return;
        }

        $(btn).attr('disabled', 'disabled').addClass('opacity-75');
        const orig = $(btn).html();
        $(btn).html('<span class="loading loading-spinner loading-xs me-1"></span> Dispatching...');

        $.ajax({
            url: "{{ route('rewriter.create') }}",
            type: 'POST',
            data: $('#rewriter-form').serialize(),
            success: function(res) {
                showToast(res.message || 'Rewriter jobs dispatched successfully!', 'success');
                setTimeout(() => window.location.reload(), 500);
            },
            error: function(xhr) {
                $(btn).removeAttr('disabled').removeClass('opacity-75').html(orig);
                showToast(xhr.responseJSON?.message || 'Failed to start rewrite job.', 'error');
            }
        });
    }

    function viewRewrittenContent(id) {
        document.getElementById('view_rewriter_modal').showModal();
        $('#rewritten-modal-body').html('<span class="loading loading-spinner loading-sm"></span> Loading content...');

        $.ajax({
            url: "/rewriter/jobs/" + id + "/status",
            type: 'GET',
            success: function(res) {
                $('#rewritten-modal-body').html(res.rewritten_html || '<p class="text-base-content/50 italic">No content available.</p>');
            },
            error: function() {
                $('#rewritten-modal-body').html('<p class="text-error">Failed to load content.</p>');
            }
        });
    }

    function deleteRewriterJob(id) {
        if (!confirm('Are you sure you want to delete this rewriter record?')) return;

        $.ajax({
            url: "/rewriter/jobs/" + id,
            type: 'DELETE',
            success: function(res) {
                showToast('Job deleted successfully.', 'success');
                setTimeout(() => window.location.reload(), 300);
            },
            error: function() {
                showToast('Failed to delete job.', 'error');
            }
        });
    }
</script>
@endpush
