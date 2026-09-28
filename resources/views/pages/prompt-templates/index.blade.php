@extends('layouts.app')

@section('title', 'Prompt Blueprints Archetypes')
@section('page_title', 'Prompt Blueprints')
@section('page_badge', 'System & Custom Archetypes')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="badge badge-primary badge-outline badge-sm font-mono">Archetype Engine</span>
                <span class="text-xs text-base-content/60">System Prompts & Structured Directives</span>
            </div>
            <h1 class="text-xl font-black text-base-content mt-1">Prompt Blueprint Archetypes</h1>
        </div>
        <button type="button" onclick="openCreateTemplateModal()" class="btn btn-primary btn-sm gap-2 font-bold shadow-xs">
            <i data-lucide="plus" class="w-4 h-4"></i> Create Custom Blueprint
        </button>
    </div>

    <!-- Archetype Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($templates as $tmpl)
        <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-5 flex flex-col justify-between hover:border-primary/50 transition-colors">
            <div>
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div class="flex items-center gap-2.5 cursor-pointer group" onclick="viewTemplate({{ $tmpl->toJson() }})">
                        <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold border border-primary/20 group-hover:bg-primary group-hover:text-primary-content transition-colors shrink-0">
                            <i data-lucide="file-code-2" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-base-content leading-tight group-hover:text-primary transition-colors">{{ $tmpl->archetype_name }}</h3>
                            <span class="text-[10px] font-mono text-base-content/50">key: {{ $tmpl->archetype_key }}</span>
                        </div>
                    </div>
                    @if($tmpl->is_system)
                        <span class="badge badge-sm badge-info badge-outline font-mono text-[10px] shrink-0">System Lock</span>
                    @else
                        <span class="badge badge-sm badge-ghost font-mono text-[10px] shrink-0">Custom</span>
                    @endif
                </div>

                <p class="text-xs text-base-content/70 line-clamp-3 mb-3 leading-relaxed">
                    {{ Str::limit($tmpl->description ?? $tmpl->system_prompt_template, 140) }}
                </p>

                <!-- Token Pills -->
                <div class="flex flex-wrap gap-1 mb-4">
                    @forelse(array_slice($tmpl->available_placeholders ?? [], 0, 5) as $ph)
                        <span class="badge badge-xs badge-neutral font-mono">&#123;&#123;{{ $ph }}&#125;&#125;</span>
                    @empty
                        <span class="badge badge-xs badge-ghost font-mono text-[10px]">No tokens</span>
                    @endforelse
                    @if(count($tmpl->available_placeholders ?? []) > 5)
                        <span class="badge badge-xs badge-ghost font-mono text-[10px]">+{{ count($tmpl->available_placeholders) - 5 }} more</span>
                    @endif
                </div>
            </div>

            <!-- Card Actions -->
            <div class="flex items-center justify-between pt-3 border-t border-base-300">
                <span class="text-[10px] font-mono text-base-content/50">
                    {{ $tmpl->is_system ? 'Read-only core blueprint' : 'Editable by you' }}
                </span>
                <div class="flex items-center gap-1.5">
                    <!-- Rule 8: View Action Button First -->
                    <button type="button" onclick="viewTemplate({{ $tmpl->toJson() }})" class="btn btn-xs btn-square btn-outline btn-primary rounded-lg" title="View Prompt Directives">
                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                    </button>
                    @if(!$tmpl->is_system)
                    <button type="button" onclick="editTemplate({{ $tmpl->toJson() }})" class="btn btn-xs btn-square btn-outline btn-warning rounded-lg" title="Edit Blueprint">
                        <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                    </button>
                    @endif
                    <button type="button" onclick="duplicateTemplate({{ $tmpl->id }})" class="btn btn-xs btn-square btn-outline btn-info rounded-lg" title="Clone as Custom Blueprint">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    </button>
                    @if(!$tmpl->is_system)
                    <button type="button" onclick="deleteTemplate({{ $tmpl->id }})" class="btn btn-xs btn-square btn-outline btn-error rounded-lg" title="Delete">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- ========================================================================= -->
<!-- View Prompt Blueprint Modal (Interactive Directives Viewer) -->
<!-- ========================================================================= -->
<dialog id="view_template_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-5xl h-[88vh] max-h-[88vh] flex flex-col p-0 bg-base-100 border border-base-300 text-base-content shadow-2xl rounded-2xl overflow-hidden">
        <!-- Modal Header -->
        <div class="px-6 py-3.5 border-b border-base-300 bg-base-200/50 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold">
                    <i data-lucide="file-code-2" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 id="view_tmpl_name" class="font-extrabold text-sm text-base-content leading-tight">Prompt Blueprint</h3>
                    <span id="view_tmpl_key" class="text-[10px] font-mono text-base-content/60"></span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a id="view_tmpl_permalink" href="#" class="btn btn-xs btn-ghost gap-1 text-[11px] text-primary" title="Open Dedicated Page">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Full Page
                </a>
                <form method="dialog"><button class="btn btn-xs btn-circle btn-ghost">✕</button></form>
            </div>
        </div>

        <!-- Modal Body (Single smooth scrollable container) -->
        <div class="p-6 flex-1 overflow-y-auto min-h-0 space-y-4">
            <!-- 2-Column Side-by-Side Widescreen Studio Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                <!-- Left Column: Metadata & Placeholders (5 cols) -->
                <div class="lg:col-span-5 space-y-4">
                    <!-- Badges & Creator -->
                    <div class="space-y-2 pb-3 border-b border-base-300/70">
                        <div class="flex items-center gap-2 flex-wrap" id="view_tmpl_badges">
                            <!-- Badges injected dynamically -->
                        </div>
                        <div class="text-[11px] text-base-content/60 font-mono" id="view_tmpl_author">
                            <!-- Author info injected dynamically -->
                        </div>
                    </div>

                    <!-- Description -->
                    <div id="view_tmpl_desc_container" class="bg-base-200/50 rounded-xl p-3 border border-base-300/60">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-base-content/50 block mb-0.5">Description</span>
                        <p id="view_tmpl_desc" class="text-xs text-base-content/80 leading-relaxed"></p>
                    </div>

                    <!-- Dynamic Placeholders -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-base-content/50">Dynamic Placeholders</span>
                            <span class="text-[10px] text-base-content/40">Click token to copy</span>
                        </div>
                        <div id="view_tmpl_tokens" class="flex flex-wrap gap-1.5">
                            <!-- Tokens injected dynamically -->
                        </div>
                    </div>

                    <!-- Stats Pill -->
                    <div class="p-3 bg-base-200/40 rounded-xl border border-base-300 flex items-center justify-between text-xs font-mono">
                        <span class="text-base-content/60">Canvas Size</span>
                        <span id="view_tmpl_stats" class="font-bold text-base-content"></span>
                    </div>
                </div>

                <!-- Right Column: System Prompt Code Canvas (7 cols) -->
                <div class="lg:col-span-7 flex flex-col">
                    <div class="card bg-base-100 border border-base-300 rounded-xl overflow-hidden flex flex-col h-full">
                        <div class="px-4 py-2.5 bg-base-200/80 border-b border-base-300 flex items-center justify-between shrink-0">
                            <span class="font-mono text-xs font-bold text-base-content/80 flex items-center gap-1.5">
                                <i data-lucide="terminal" class="w-3.5 h-3.5 text-primary"></i> System Prompt Directive
                            </span>
                            <button type="button" onclick="copyCurrentPrompt(this)" class="btn btn-xs btn-primary gap-1 font-mono text-xs shadow-xs">
                                <i data-lucide="copy" class="w-3.5 h-3.5"></i> Copy Prompt
                            </button>
                        </div>
                        <div class="p-4 bg-base-200/30 overflow-x-auto flex-1 min-h-[300px]">
                            <pre id="view_tmpl_prompt" class="font-mono text-xs text-base-content leading-relaxed whitespace-pre-wrap select-all m-0"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer Actions -->
        <div class="px-6 py-3.5 bg-base-200/50 border-t border-base-300 flex items-center justify-between shrink-0">
            <button type="button" onclick="document.getElementById('view_template_modal').close()" class="btn btn-ghost btn-sm">Close</button>
            <div class="flex items-center gap-2">
                <button type="button" id="view_tmpl_btn_clone" onclick="" class="btn btn-outline btn-info btn-sm gap-1.5 font-bold">
                    <i data-lucide="copy-plus" class="w-4 h-4"></i> Clone Blueprint
                </button>
                <button type="button" id="view_tmpl_btn_edit" onclick="" class="btn btn-warning btn-sm gap-1.5 font-bold hidden">
                    <i data-lucide="edit-2" class="w-4 h-4"></i> Edit Blueprint
                </button>
            </div>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<!-- ========================================================================= -->
<!-- Template Create/Edit Modal (DaisyUI 5 Modal) -->
<!-- ========================================================================= -->
<dialog id="template_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-4xl h-[90vh] max-h-[90vh] flex flex-col p-0 bg-base-100 border border-base-300 text-base-content shadow-2xl rounded-2xl overflow-hidden">
        <!-- Sticky Modal Header -->
        <div class="px-6 py-4 border-b border-base-300 bg-base-200/50 flex items-center justify-between shrink-0">
            <h3 id="template-modal-title" class="font-bold text-sm">Create Prompt Blueprint Archetype</h3>
            <form method="dialog"><button class="btn btn-xs btn-circle btn-ghost">✕</button></form>
        </div>

        <!-- Scrollable Form Body Container -->
        <form id="template-form" class="flex-1 overflow-y-auto min-h-0 flex flex-col">
            @csrf
            <input type="hidden" id="tmpl_id" name="id" value="" />

            <div class="p-6 space-y-4 flex-1">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label py-0.5 text-xs font-semibold">Archetype Name <span class="text-error">*</span></label>
                        <input type="text" id="tmpl_name" name="archetype_name" required placeholder="e.g. Comparison Matrix & Buying Guide" class="input input-bordered input-sm w-full bg-base-200/50 text-xs" />
                    </div>
                    <div>
                        <label class="label py-0.5 text-xs font-semibold">Associated Agency Client (Optional)</label>
                        <select id="tmpl_client_id" name="client_id" class="select select-bordered select-sm w-full bg-base-200/50 text-xs">
                            <option value="">Global (Available to all clients)</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="label py-0.5 text-xs font-semibold">Short Description</label>
                    <input type="text" id="tmpl_desc" name="description" placeholder="Brief explanation of when to use this archetype" class="input input-bordered input-sm w-full bg-base-200/50 text-xs" />
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
                              placeholder="You are an elite expert writer specializing in..."
                              class="textarea textarea-bordered textarea-sm w-full bg-base-200/50 font-mono text-xs leading-relaxed"></textarea>
                </div>
            </div>

            <!-- Sticky Modal Footer -->
            <div class="px-6 py-3.5 bg-base-200/50 border-t border-base-300 flex items-center justify-end gap-2 shrink-0 sticky bottom-0 z-10">
                <button type="button" onclick="document.getElementById('template_modal').close()" class="btn btn-ghost btn-sm">Cancel</button>
                <button type="button" onclick="saveTemplate(this)" class="btn btn-primary btn-sm font-bold shadow-xs">Save Blueprint</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('scripts')
<script>
    let currentViewTemplate = null;

    function viewTemplate(tmpl) {
        if (typeof tmpl === 'number' || typeof tmpl === 'string') {
            $.getJSON('/prompt-templates/' + tmpl, function(res) {
                if (res.success && res.template) {
                    renderViewModal(res.template);
                }
            });
            return;
        }
        renderViewModal(tmpl);
    }

    function renderViewModal(tmpl) {
        currentViewTemplate = tmpl;
        $('#view_tmpl_name').text(tmpl.archetype_name);
        $('#view_tmpl_key').text('Key: ' + (tmpl.archetype_key || tmpl.slug || ''));
        $('#view_tmpl_permalink').attr('href', '/prompt-templates/' + tmpl.id);

        // Badges
        let badgesHtml = '';
        if (tmpl.is_system) {
            badgesHtml += '<span class="badge badge-info badge-outline badge-sm font-mono text-[10px]">System Lock</span>';
        } else {
            badgesHtml += '<span class="badge badge-ghost badge-sm font-mono text-[10px]">Custom Blueprint</span>';
        }
        if (tmpl.is_active) {
            badgesHtml += '<span class="badge badge-success badge-outline badge-sm text-[10px]">Active</span>';
        } else {
            badgesHtml += '<span class="badge badge-error badge-outline badge-sm text-[10px]">Inactive</span>';
        }
        if (tmpl.client && tmpl.client.name) {
            badgesHtml += `<span class="badge badge-primary badge-outline badge-sm text-[10px]">${tmpl.client.name}</span>`;
        } else {
            badgesHtml += '<span class="badge badge-neutral badge-sm text-[10px]">Global</span>';
        }
        $('#view_tmpl_badges').html(badgesHtml);

        // Author
        let authorText = tmpl.is_system ? 'System Default Archetype' : (tmpl.user ? `Created by ${tmpl.user.name}` : 'User Custom');
        $('#view_tmpl_author').text(authorText);

        // Description
        if (tmpl.description) {
            $('#view_tmpl_desc').text(tmpl.description);
            $('#view_tmpl_desc_container').show();
        } else {
            $('#view_tmpl_desc_container').hide();
        }

        // Placeholders
        let tokensHtml = '';
        const placeholders = tmpl.available_placeholders || [];
        if (placeholders.length > 0) {
            placeholders.forEach(token => {
                tokensHtml += `<button type="button" data-token="${token}" onclick="copyTokenName(this)" class="badge badge-neutral hover:badge-primary text-[11px] font-mono cursor-pointer transition-colors py-2 px-2" title="Click to copy">&#123;&#123;${token}&#125;&#125;</button>`;
            });
        } else {
            tokensHtml = '<span class="text-xs text-base-content/50 italic">No placeholders defined.</span>';
        }
        $('#view_tmpl_tokens').html(tokensHtml);

        // Prompt text & stats
        const promptText = tmpl.system_prompt_template || '';
        $('#view_tmpl_prompt').text(promptText);
        const words = promptText.trim().split(/\s+/).filter(Boolean).length;
        $('#view_tmpl_stats').text(`${promptText.length.toLocaleString()} characters • ${words.toLocaleString()} words`);

        // Clone button
        $('#view_tmpl_btn_clone').attr('onclick', `duplicateTemplate(${tmpl.id})`);

        // Edit button
        if (!tmpl.is_system && (tmpl.is_owner !== false)) {
            $('#view_tmpl_btn_edit').removeClass('hidden').attr('onclick', 'openEditFromView()');
        } else {
            $('#view_tmpl_btn_edit').addClass('hidden');
        }

        document.getElementById('view_template_modal').showModal();
        if (window.lucide) lucide.createIcons();
    }

    function openEditFromView() {
        document.getElementById('view_template_modal').close();
        if (currentViewTemplate) {
            editTemplate(currentViewTemplate);
        }
    }

    function copyCurrentPrompt(btn) {
        if (!currentViewTemplate) return;
        navigator.clipboard.writeText(currentViewTemplate.system_prompt_template).then(() => {
            const orig = $(btn).html();
            $(btn).html('<i data-lucide="check" class="w-3.5 h-3.5 text-success"></i> Copied!');
            if (window.lucide) lucide.createIcons();
            showToast('Prompt copied to clipboard!', 'success');
            setTimeout(() => {
                $(btn).html(orig);
                if (window.lucide) lucide.createIcons();
            }, 2000);
        }).catch(err => {
            showToast('Failed to copy: ' + err, 'error');
        });
    }

    function copyTokenName(btn) {
        const raw = btn.getAttribute('data-token');
        const token = '{' + '{' + raw + '}' + '}';
        navigator.clipboard.writeText(token).then(() => {
            showToast('Copied ' + token + ' to clipboard!', 'success');
        });
    }

    function openCreateTemplateModal() {
        $('#template-modal-title').text('Create Custom Prompt Blueprint');
        $('#tmpl_id').val('');
        $('#template-form')[0].reset();
        document.getElementById('template_modal').showModal();
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

        // Dynamically add any custom placeholders from template into the toolbar
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
        const url = id ? "/prompt-templates/" + id : "{{ route('prompt-templates.store') }}";
        const method = id ? 'PUT' : 'POST';

        $(btn).attr('disabled', 'disabled').addClass('opacity-75');
        const orig = $(btn).html();
        $(btn).html('<span class="loading loading-spinner loading-xs me-1"></span> Saving...');

        $.ajax({
            url: url,
            type: method,
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
                setTimeout(() => window.location.reload(), 300);
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Failed to clone blueprint.', 'error');
            }
        });
    }

    function deleteTemplate(id) {
        if (!confirm('Are you sure you want to delete this custom blueprint?')) return;

        $.ajax({
            url: "/prompt-templates/" + id,
            type: 'DELETE',
            success: function(res) {
                showToast(res.message || 'Blueprint deleted successfully.', 'success');
                setTimeout(() => window.location.reload(), 300);
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Error deleting blueprint.', 'error');
            }
        });
    }
</script>
@endpush
