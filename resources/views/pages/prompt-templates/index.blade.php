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
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold">
                            <i data-lucide="file-code-2" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-base-content leading-tight">{{ $tmpl->archetype_name }}</h3>
                            <span class="text-[10px] font-mono text-base-content/50">slug: {{ $tmpl->slug }}</span>
                        </div>
                    </div>
                    @if($tmpl->is_system)
                        <span class="badge badge-sm badge-info badge-outline font-mono text-[10px]">System Lock</span>
                    @else
                        <span class="badge badge-sm badge-ghost font-mono text-[10px]">Custom</span>
                    @endif
                </div>

                <p class="text-xs text-base-content/70 line-clamp-3 mb-4 leading-relaxed">
                    {{ Str::limit($tmpl->description ?? $tmpl->system_prompt_template, 140) }}
                </p>

                <!-- Token Pills -->
                <div class="flex flex-wrap gap-1 mb-4">
                    <span class="badge badge-xs badge-neutral font-mono">{TOPIC}</span>
                    <span class="badge badge-xs badge-neutral font-mono">{KEYWORDS}</span>
                    <span class="badge badge-xs badge-neutral font-mono">{BRAND_VOICE}</span>
                    <span class="badge badge-xs badge-neutral font-mono">{INTERNAL_LINKS}</span>
                </div>
            </div>

            <!-- Card Actions -->
            <div class="flex items-center justify-between pt-3 border-t border-base-300">
                <span class="text-[10px] font-mono text-base-content/50">
                    {{ $tmpl->is_system ? 'Read-only core blueprint' : 'Editable by you' }}
                </span>
                <div class="flex items-center gap-1.5">
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
<!-- Template Create/Edit Modal (DaisyUI 5 Modal) -->
<!-- ========================================================================= -->
<dialog id="template_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-4xl bg-base-100 border border-base-300 text-base-content p-0 shadow-2xl rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-base-300 bg-base-200/50 flex items-center justify-between">
            <h3 id="template-modal-title" class="font-bold text-sm">Create Prompt Blueprint Archetype</h3>
            <form method="dialog"><button class="btn btn-xs btn-circle btn-ghost">✕</button></form>
        </div>

        <form id="template-form" class="p-6 space-y-4">
            @csrf
            <input type="hidden" id="tmpl_id" name="id" value="" />

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

            <div>
                <div class="flex items-center justify-between py-1">
                    <label class="label p-0 text-xs font-semibold">System Prompt Template Directives <span class="text-error">*</span></label>
                    <span class="text-[10px] text-base-content/50 font-mono">Use {TOPIC}, {KEYWORDS}, {BRAND_VOICE}, {POV}</span>
                </div>
                <textarea id="tmpl_system_prompt" name="system_prompt_template" rows="10" required
                          placeholder="You are an elite expert writer specializing in..."
                          class="textarea textarea-bordered textarea-sm w-full bg-base-200/50 font-mono text-xs leading-relaxed"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-base-300">
                <button type="button" onclick="document.getElementById('template_modal').close()" class="btn btn-ghost btn-sm">Cancel</button>
                <button type="button" onclick="saveTemplate(this)" class="btn btn-primary btn-sm font-bold shadow-xs">Save Blueprint</button>
            </div>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    function openCreateTemplateModal() {
        $('#template-modal-title').text('Create Custom Prompt Blueprint');
        $('#tmpl_id').val('');
        $('#template-form')[0].reset();
        document.getElementById('template_modal').showModal();
    }

    function editTemplate(tmpl) {
        $('#template-modal-title').text('Edit Blueprint: ' + tmpl.archetype_name);
        $('#tmpl_id').val(tmpl.id);
        $('#tmpl_name').val(tmpl.archetype_name);
        $('#tmpl_desc').val(tmpl.description || '');
        $('#tmpl_client_id').val(tmpl.client_id || '');
        $('#tmpl_system_prompt').val(tmpl.system_prompt_template);
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
