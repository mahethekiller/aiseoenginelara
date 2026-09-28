@extends('layouts.app')

@section('title', $template->archetype_name . ' - Prompt Blueprint')
@section('page_title', 'Prompt Blueprint')
@section('page_badge', $template->is_system ? 'System Default' : 'Custom Blueprint')

@section('content')
<div class="space-y-6">
    <!-- Top Breadcrumb & Navigation -->
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-2 text-xs text-base-content/60 font-medium">
            <a href="{{ route('prompt-templates.index') }}" class="hover:text-primary transition-colors flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Blueprints
            </a>
            <span>/</span>
            <span class="text-base-content font-bold">{{ $template->archetype_name }}</span>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="copyPromptFromPre(this)" class="btn btn-sm btn-outline btn-primary gap-1.5 rounded-lg shadow-xs font-semibold">
                <i data-lucide="copy" class="w-4 h-4"></i> Copy Prompt
            </button>
            <button type="button" onclick="duplicateTemplate({{ $template->id }})" class="btn btn-sm btn-outline btn-info gap-1.5 rounded-lg shadow-xs font-semibold" title="Clone as Custom Blueprint">
                <i data-lucide="copy-plus" class="w-4 h-4"></i> Clone Blueprint
            </button>
            @if($template->is_owner && !$template->is_system)
            <button type="button" onclick="editTemplate(@json($template))" class="btn btn-sm btn-warning gap-1.5 rounded-lg shadow-xs font-semibold">
                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit Blueprint
            </button>
            @endif
        </div>
    </div>

    <!-- Header Banner Card -->
    <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-6 relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-2">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-lg border border-primary/20">
                        <i data-lucide="file-code-2" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-extrabold text-base-content tracking-tight">{{ $template->archetype_name }}</h1>
                            @if($template->is_system)
                                <span class="badge badge-info badge-outline badge-sm font-mono text-[10px]">System Lock</span>
                            @else
                                <span class="badge badge-ghost badge-sm font-mono text-[10px]">Custom Blueprint</span>
                            @endif
                            @if($template->is_active)
                                <span class="badge badge-success badge-sm badge-outline text-[10px]">Active</span>
                            @else
                                <span class="badge badge-error badge-sm badge-outline text-[10px]">Inactive</span>
                            @endif
                        </div>
                        <div class="text-xs font-mono text-base-content/60 mt-0.5">
                            Key: <span class="text-primary font-semibold">{{ $template->archetype_key }}</span>
                        </div>
                    </div>
                </div>

                @if($template->description)
                <p class="text-xs text-base-content/80 max-w-3xl leading-relaxed pt-1">
                    {{ $template->description }}
                </p>
                @endif
            </div>

            <!-- Quick Telemetry Counters -->
            <div class="flex items-center gap-4 bg-base-200/60 p-3 rounded-xl border border-base-300 shrink-0">
                <div class="text-center px-2">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50">Characters</div>
                    <div class="text-sm font-extrabold font-mono text-base-content">{{ number_format(strlen($template->system_prompt_template)) }}</div>
                </div>
                <div class="divider divider-horizontal m-0"></div>
                <div class="text-center px-2">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50">Words</div>
                    <div class="text-sm font-extrabold font-mono text-base-content">{{ number_format(str_word_count($template->system_prompt_template)) }}</div>
                </div>
                <div class="divider divider-horizontal m-0"></div>
                <div class="text-center px-2">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50">Placeholders</div>
                    <div class="text-sm font-extrabold font-mono text-primary">{{ is_array($template->available_placeholders) ? count($template->available_placeholders) : 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Column: Metadata & Placeholders (4 cols) -->
        <div class="lg:col-span-4 space-y-5">
            <!-- Specification Card -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-5 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-base-content/60 flex items-center gap-1.5">
                    <i data-lucide="info" class="w-3.5 h-3.5 text-primary"></i> Blueprint Specifications
                </h3>

                <div class="divide-y divide-base-300/70 text-xs">
                    <div class="py-2 flex items-center justify-between">
                        <span class="text-base-content/60">Archetype Key</span>
                        <span class="font-mono text-base-content font-bold">{{ $template->archetype_key }}</span>
                    </div>
                    <div class="py-2 flex items-center justify-between">
                        <span class="text-base-content/60">Type</span>
                        <span class="badge badge-sm {{ $template->is_system ? 'badge-info badge-outline' : 'badge-ghost' }} font-mono text-[10px]">
                            {{ $template->is_system ? 'System Default' : 'User Custom' }}
                        </span>
                    </div>
                    <div class="py-2 flex items-center justify-between">
                        <span class="text-base-content/60">Agency Client</span>
                        <span class="font-semibold text-base-content">
                            @if($template->client)
                                <span class="badge badge-sm badge-outline badge-primary">{{ $template->client->name }}</span>
                            @else
                                <span class="text-base-content/60 italic">Global (All Clients)</span>
                            @endif
                        </span>
                    </div>
                    <div class="py-2 flex items-center justify-between">
                        <span class="text-base-content/60">Created By</span>
                        <span class="font-semibold text-base-content">
                            {{ $template->user ? $template->user->name : 'System Core' }}
                        </span>
                    </div>
                    <div class="py-2 flex items-center justify-between">
                        <span class="text-base-content/60">Created Date</span>
                        <span class="font-mono text-base-content/80">{{ $template->created_at ? $template->created_at->format('M d, Y') : 'N/A' }}</span>
                    </div>
                    <div class="py-2 flex items-center justify-between">
                        <span class="text-base-content/60">Last Updated</span>
                        <span class="font-mono text-base-content/80">{{ $template->updated_at ? $template->updated_at->format('M d, Y') : 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Dynamic Placeholders Card -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-base-content/60 flex items-center gap-1.5">
                        <i data-lucide="variable" class="w-3.5 h-3.5 text-violet-500"></i> Supported Tokens
                    </h3>
                    <span class="text-[10px] text-base-content/40">Click chip to copy</span>
                </div>

                <div class="flex flex-wrap gap-1.5">
                    @forelse($template->available_placeholders ?? [] as $placeholder)
                        <button type="button" data-token="&#123;&#123;{{ $placeholder }}&#125;&#125;" onclick="copyToken(this.dataset.token, this)" class="badge badge-neutral hover:badge-primary text-[11px] font-mono cursor-pointer transition-colors py-2.5 px-2" title="Click to copy token">
                            <span>&#123;&#123;{{ $placeholder }}&#125;&#125;</span>
                        </button>
                    @empty
                        <span class="text-xs text-base-content/50 italic">No placeholders defined.</span>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Column: System Prompt Code Canvas (8 cols) -->
        <div class="lg:col-span-8">
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
                <!-- Editor Canvas Header -->
                <div class="px-5 py-3.5 bg-base-200/70 border-b border-base-300 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-error/70"></span>
                            <span class="w-3 h-3 rounded-full bg-warning/70"></span>
                            <span class="w-3 h-3 rounded-full bg-success/70"></span>
                        </div>
                        <span class="font-mono text-xs font-bold text-base-content/80 ml-2">system_prompt_template.txt</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="copyPromptFromPre(this)" class="btn btn-xs btn-ghost gap-1 font-mono text-xs hover:bg-base-300/80">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i> Copy Directives
                        </button>
                    </div>
                </div>

                <!-- Monospace Canvas Content -->
                <div class="p-6 bg-base-200/30 overflow-x-auto max-h-[650px] overflow-y-auto">
                    <pre id="system_prompt_pre" class="font-mono text-xs text-base-content leading-relaxed whitespace-pre-wrap select-all">{{ $template->system_prompt_template }}</pre>
                </div>

                <!-- Footer Tip -->
                <div class="px-5 py-3 bg-base-200/50 border-t border-base-300 flex items-center justify-between text-[11px] text-base-content/60">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-primary"></i> Substituted dynamically during AI generation using {{ count($template->available_placeholders ?? []) }} tokens.
                    </span>
                    <span class="font-mono text-[10px]">{{ number_format(strlen($template->system_prompt_template)) }} bytes</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Template Edit Modal (DaisyUI 5 Modal) -->
<!-- ========================================================================= -->
<dialog id="template_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-4xl bg-base-100 border border-base-300 text-base-content p-0 shadow-2xl rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-base-300 bg-base-200/50 flex items-center justify-between">
            <h3 id="template-modal-title" class="font-bold text-sm">Edit Blueprint</h3>
            <form method="dialog"><button class="btn btn-xs btn-circle btn-ghost">✕</button></form>
        </div>

        <form id="template-form" class="p-6 space-y-4">
            @csrf
            <input type="hidden" id="tmpl_id" name="id" value="" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label py-0.5 text-xs font-semibold">Archetype Name <span class="text-error">*</span></label>
                    <input type="text" id="tmpl_name" name="archetype_name" required class="input input-bordered input-sm w-full bg-base-200/50 text-xs" />
                </div>
                <div>
                    <label class="label py-0.5 text-xs font-semibold">Associated Agency Client</label>
                    <select id="tmpl_client_id" name="client_id" class="select select-bordered select-sm w-full bg-base-200/50 text-xs">
                        <option value="">Global (Available to all clients)</option>
                        @foreach(\App\Models\Client::where('is_active', true)->orderBy('name')->get() as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="label py-0.5 text-xs font-semibold">Short Description</label>
                <input type="text" id="tmpl_desc" name="description" class="input input-bordered input-sm w-full bg-base-200/50 text-xs" />
            </div>

            <!-- Dynamic Placeholders Click-to-Add Toolbar -->
            <div class="bg-base-200/60 rounded-xl p-3 border border-base-300 space-y-2">
                <div class="flex items-center justify-between flex-wrap gap-1">
                    <span class="text-xs font-bold text-base-content/80 flex items-center gap-1.5">
                        <i data-lucide="variable" class="w-3.5 h-3.5 text-primary"></i> Dynamic Placeholders
                    </span>
                    <span class="text-[10px] text-base-content/50">Click any chip to insert at cursor position</span>
                </div>

                <!-- Chips Container -->
                <div id="modal_placeholder_chips" class="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto">
                    @php
                        $standardPlaceholders = [
                            'brand_name', 'audience', 'tone', 'content_type_label', 
                            'primary_keyword', 'wireframe_layout', 'word_count', 
                            'reading_level', 'seo_title', 'meta_description', 
                            'slug', 'brand_heading', 'internal_links', 'cta', 'schema_directive'
                        ];
                    @endphp
                    @foreach($standardPlaceholders as $chip)
                        <button type="button" data-chip="{{ $chip }}" onclick="insertPlaceholderAtCursor('{{ $chip }}')" 
                                class="badge badge-neutral hover:badge-primary text-[11px] font-mono cursor-pointer transition-all py-2 px-2 hover:scale-105 active:scale-95 shadow-xs" 
                                title="Click to insert &#123;&#123;{{ $chip }}&#125;&#125;">
                            + &#123;&#123;{{ $chip }}&#125;&#125;
                        </button>
                    @endforeach
                </div>

                <!-- Quick Custom Token Input -->
                <div class="flex items-center gap-2 pt-1 border-t border-base-300/60">
                    <input type="text" id="custom_chip_input" placeholder="Add custom token (e.g. author_bio)" 
                           onkeydown="if(event.key==='Enter'){ event.preventDefault(); addCustomChip(); }"
                           class="input input-bordered input-xs bg-base-100 font-mono text-[11px] w-64" />
                    <button type="button" onclick="addCustomChip()" class="btn btn-xs btn-outline btn-primary gap-1">
                        <i data-lucide="plus" class="w-3 h-3"></i> Add Token
                    </button>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between py-1">
                    <label class="label p-0 text-xs font-semibold">System Prompt Template Directives <span class="text-error">*</span></label>
                    <span class="text-[10px] text-base-content/50 font-mono">Use &#123;&#123;variable&#125;&#125; placeholders</span>
                </div>
                <textarea id="tmpl_system_prompt" name="system_prompt_template" rows="12" required
                          class="textarea textarea-bordered textarea-sm w-full bg-base-200/50 font-mono text-xs leading-relaxed"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-base-300">
                <button type="button" onclick="document.getElementById('template_modal').close()" class="btn btn-ghost btn-sm">Cancel</button>
                <button type="button" onclick="saveTemplate(this)" class="btn btn-primary btn-sm font-bold shadow-xs">Save Changes</button>
            </div>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    function copyPromptFromPre(btn) {
        const text = document.getElementById('system_prompt_pre').innerText;
        navigator.clipboard.writeText(text).then(() => {
            const orig = $(btn).html();
            $(btn).html('<i data-lucide="check" class="w-4 h-4 text-success"></i> Copied!');
            if (window.lucide) lucide.createIcons();
            showToast('Prompt copied to clipboard!', 'success');
            setTimeout(() => {
                $(btn).html(orig);
                if (window.lucide) lucide.createIcons();
            }, 2000);
        }).catch(err => {
            showToast('Unable to copy prompt: ' + err, 'error');
        });
    }

    function copyToken(token, btn) {
        navigator.clipboard.writeText(token).then(() => {
            showToast(`Copied ${token} to clipboard!`, 'success');
        });
    }

    function insertPlaceholderAtCursor(tokenName) {
        const textarea = document.getElementById('tmpl_system_prompt');
        if (!textarea) return;

        const token = '{' + '{' + tokenName + '}' + '}';
        const startPos = textarea.selectionStart;
        const endPos = textarea.selectionEnd;
        const val = textarea.value;

        if (startPos !== undefined && endPos !== undefined) {
            textarea.value = val.substring(0, startPos) + token + val.substring(endPos, val.length);
            const newCursorPos = startPos + token.length;
            textarea.selectionStart = newCursorPos;
            textarea.selectionEnd = newCursorPos;
        } else {
            textarea.value += ' ' + token;
        }

        textarea.focus();
        showToast('Inserted ' + token + ' at cursor', 'info');
    }

    function addCustomChip() {
        const input = document.getElementById('custom_chip_input');
        if (!input) return;
        const raw = input.value.trim().toLowerCase().replace(/[^a-z0-9_-]/g, '_');
        if (!raw) {
            showToast('Please enter a valid token name', 'error');
            return;
        }

        const container = document.getElementById('modal_placeholder_chips');
        if (container && !container.querySelector(`[data-chip="${raw}"]`)) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('data-chip', raw);
            btn.setAttribute('onclick', `insertPlaceholderAtCursor('${raw}')`);
            btn.className = 'badge badge-primary text-[11px] font-mono cursor-pointer transition-all py-2 px-2 hover:scale-105 active:scale-95 shadow-xs';
            btn.title = 'Click to insert {' + '{' + raw + '}' + '}';
            btn.innerHTML = `+ &#123;&#123;${raw}&#125;&#125;`;
            container.appendChild(btn);
        }

        insertPlaceholderAtCursor(raw);
        input.value = '';
    }

    function editTemplate(tmpl) {
        $('#template-modal-title').text('Edit Blueprint: ' + tmpl.archetype_name);
        $('#tmpl_id').val(tmpl.id);
        $('#tmpl_name').val(tmpl.archetype_name);
        $('#tmpl_desc').val(tmpl.description || '');
        $('#tmpl_client_id').val(tmpl.client_id || '');
        $('#tmpl_system_prompt').val(tmpl.system_prompt_template);

        // Dynamically add custom placeholders from template into the toolbar
        if (tmpl.available_placeholders && Array.isArray(tmpl.available_placeholders)) {
            const container = document.getElementById('modal_placeholder_chips');
            if (container) {
                tmpl.available_placeholders.forEach(chip => {
                    if (!container.querySelector(`[data-chip="${chip}"]`)) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.setAttribute('data-chip', chip);
                        btn.setAttribute('onclick', `insertPlaceholderAtCursor('${chip}')`);
                        btn.className = 'badge badge-neutral hover:badge-primary text-[11px] font-mono cursor-pointer transition-all py-2 px-2 hover:scale-105 active:scale-95 shadow-xs';
                        btn.title = 'Click to insert {' + '{' + chip + '}' + '}';
                        btn.innerHTML = `+ &#123;&#123;${chip}&#125;&#125;`;
                        container.appendChild(btn);
                    }
                });
            }
        }

        document.getElementById('template_modal').showModal();
    }

    function saveTemplate(btn) {
        const id = $('#tmpl_id').val();
        const url = "/prompt-templates/" + id;

        $(btn).attr('disabled', 'disabled').addClass('opacity-75');
        const orig = $(btn).html();
        $(btn).html('<span class="loading loading-spinner loading-xs me-1"></span> Saving...');

        $.ajax({
            url: url,
            type: 'PUT',
            data: $('#template-form').serialize(),
            success: function(res) {
                document.getElementById('template_modal').close();
                showToast(res.message || 'Blueprint saved successfully!', 'success');
                setTimeout(() => window.location.reload(), 300);
            },
            error: function(xhr) {
                $(btn).removeAttr('disabled').removeClass('opacity-75').html(orig);
                showToast(xhr.responseJSON?.message || 'Error saving blueprint.', 'error');
            }
        });
    }

    function duplicateTemplate(id) {
        $.ajax({
            url: "/prompt-templates/" + id + "/duplicate",
            type: 'POST',
            success: function(res) {
                showToast(res.message || 'Blueprint cloned successfully!', 'success');
                if (res.template && res.template.id) {
                    setTimeout(() => window.location.href = "/prompt-templates/" + res.template.id, 500);
                } else {
                    setTimeout(() => window.location.reload(), 500);
                }
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Failed to clone blueprint.', 'error');
            }
        });
    }
</script>
@endpush
