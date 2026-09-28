@extends('layouts.app')

@section('title', 'My AI Presets')
@section('page_title', 'AI Presets')
@section('page_badge', 'Model Tuning & Directives')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="badge badge-primary badge-outline badge-sm font-mono">
                    <i data-lucide="sliders" class="w-3 h-3 mr-1"></i> LLM Directives & Tuning
                </span>
                <span class="text-xs text-base-content/60">Multi-Model Persona Configs</span>
            </div>
            <h1 class="text-xl font-black text-base-content mt-1">My AI Presets</h1>
            <p class="text-xs text-base-content/60">Customize and tune LLM models, temperature directives, and worker concurrency.</p>
        </div>
        <button type="button" onclick="openCreatePresetModal()" class="btn btn-primary btn-sm gap-2 font-bold shadow-xs">
            <i data-lucide="plus" class="w-4 h-4"></i> Create Preset
        </button>
    </div>

    <!-- Telemetry KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Presets -->
        <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-4 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Total Presets</span>
                    <h3 class="text-2xl font-black text-base-content mt-0.5">{{ $totalPresets }}</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                    <i data-lucide="layers" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-primary"></div>
        </div>

        <!-- 2. Active Default -->
        <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-4 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div class="min-w-0 pr-2">
                    <span class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Active Preset</span>
                    <h3 class="text-sm font-black text-base-content mt-1 truncate" title="{{ $activePreset ? $activePreset->name : 'None' }}">
                        {{ $activePreset ? $activePreset->name : 'None' }}
                    </h3>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="badge badge-xs badge-success font-mono">ACTIVE</span>
                        <span class="text-[10px] font-mono text-base-content/60 truncate">{{ $activePreset ? $activePreset->model : '-' }}</span>
                    </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-bold shrink-0">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-emerald-500"></div>
        </div>

        <!-- 3. Avg Temperature -->
        <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-4 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Avg Temperature</span>
                    <h3 class="text-2xl font-black text-base-content mt-0.5">{{ $avgTemp }}</h3>
                    <span class="text-[10px] text-base-content/50">Creativity Index</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold">
                    <i data-lucide="thermometer" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-amber-500"></div>
        </div>

        <!-- 4. Avg Workers -->
        <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-4 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-semibold text-base-content/60 uppercase tracking-wider">Concurrency</span>
                    <h3 class="text-2xl font-black text-base-content mt-0.5">{{ $avgWorkers }} <span class="text-xs font-normal text-base-content/60">workers</span></h3>
                    <span class="text-[10px] text-base-content/50">Parallel Section Writers</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center font-bold">
                    <i data-lucide="cpu" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-indigo-500"></div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-4">
        <form method="GET" action="{{ route('ai-presets.index') }}" class="flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto flex-1">
                <!-- Search Input -->
                <div class="relative w-full sm:w-72">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-base-content/40"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search preset name or prompt..." class="input input-sm input-bordered w-full pl-9 bg-base-200/50 text-xs">
                </div>

                <!-- Provider Filter -->
                <select name="provider" class="select select-sm select-bordered w-full sm:w-44 bg-base-200/50 text-xs" onchange="this.form.submit()">
                    <option value="">All Providers</option>
                    <option value="gemini" {{ request('provider') === 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                    <option value="openai" {{ request('provider') === 'openai' ? 'selected' : '' }}>OpenAI</option>
                    <option value="anthropic" {{ request('provider') === 'anthropic' ? 'selected' : '' }}>Anthropic Claude</option>
                    <option value="deepseek" {{ request('provider') === 'deepseek' ? 'selected' : '' }}>DeepSeek</option>
                </select>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'provider']))
                <a href="{{ route('ai-presets.index') }}" class="btn btn-ghost btn-sm px-3 text-xs">
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Presets Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($presets as $p)
        <div class="card bg-base-100 border {{ $p->is_active ? 'border-primary ring-1 ring-primary shadow-md' : 'border-base-300 shadow-sm' }} rounded-2xl p-5 flex flex-col justify-between hover:border-primary/50 transition-all">
            <div>
                <!-- Top Row: Name, Provider, Model, Active Badge -->
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-lg {{ $p->is_active ? 'bg-primary text-primary-content' : 'bg-primary/10 text-primary' }} flex items-center justify-center font-bold border border-primary/20 shrink-0">
                            <i data-lucide="sliders" class="w-4 h-4"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-bold text-sm text-base-content leading-tight truncate" title="{{ $p->name }}">{{ $p->name }}</h3>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="badge badge-xs badge-neutral font-mono">{{ $p->provider }}</span>
                                <span class="badge badge-xs badge-ghost font-mono truncate max-w-[130px]" title="{{ $p->model }}">{{ $p->model }}</span>
                            </div>
                        </div>
                    </div>

                    @if($p->is_active)
                        <span class="badge badge-sm badge-primary font-mono text-[10px] shrink-0 font-bold">
                            <i data-lucide="check" class="w-3 h-3 mr-0.5"></i> ACTIVE
                        </span>
                    @endif
                </div>

                <!-- Custom Instructions / System Directives -->
                <div class="my-3">
                    <p class="text-xs text-base-content/70 line-clamp-3 leading-relaxed bg-base-200/50 p-2.5 rounded-xl border border-base-300/50">
                        {{ $p->custom_instructions ?: 'No custom system prompt tuning provided. Standard model defaults will apply.' }}
                    </p>
                </div>

                <!-- Tuning Parameters Badges -->
                <div class="flex flex-wrap items-center gap-2 mb-4 font-mono text-[11px] text-base-content/60">
                    <span class="badge badge-sm badge-ghost border-base-300 gap-1">
                        <i data-lucide="thermometer" class="w-3 h-3 text-amber-500"></i> Temp: {{ $p->temperature }}
                    </span>
                    <span class="badge badge-sm badge-ghost border-base-300 gap-1">
                        <i data-lucide="cpu" class="w-3 h-3 text-indigo-500"></i> Workers: {{ $p->max_workers }}
                    </span>
                    @if($p->top_p)
                    <span class="badge badge-sm badge-ghost border-base-300 gap-1">
                        Top P: {{ $p->top_p }}
                    </span>
                    @endif
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="pt-3 border-t border-base-300/70 flex items-center justify-between gap-2">
                <div>
                    @if(!$p->is_active)
                    <button type="button" onclick="activatePreset({{ $p->id }})" class="btn btn-xs btn-outline btn-primary px-3 rounded-lg gap-1 font-semibold">
                        <i data-lucide="check" class="w-3 h-3"></i> Make Active
                    </button>
                    @else
                    <span class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> In Use
                    </span>
                    @endif
                </div>

                <div class="flex items-center gap-1.5">
                    <!-- Clone Button -->
                    <button type="button" onclick="clonePreset({{ $p->id }})" class="btn btn-xs btn-square btn-ghost text-base-content/70 hover:text-primary rounded-lg" title="Duplicate / Clone Preset">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    </button>
                    <!-- Edit Button -->
                    <button type="button" onclick="editPreset({{ $p->toJson() }})" class="btn btn-xs btn-square btn-outline btn-warning rounded-lg" title="Edit Preset">
                        <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                    </button>
                    <!-- Delete Button -->
                    <button type="button" onclick="confirmDeletePreset({{ $p->id }}, '{{ addslashes($p->name) }}')" class="btn btn-xs btn-square btn-outline btn-error rounded-lg" title="Delete Preset">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full py-12 text-center card bg-base-100 border border-dashed border-base-300 rounded-2xl">
            <div class="w-12 h-12 rounded-2xl bg-base-200 text-base-content/40 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="sliders" class="w-6 h-6"></i>
            </div>
            <h4 class="font-bold text-base text-base-content">No AI Presets Found</h4>
            <p class="text-xs text-base-content/60 mt-1 max-w-sm mx-auto">
                {{ request()->hasAny(['search', 'provider']) ? 'No presets match your active search filters.' : 'Get started by creating your first tuned AI preset.' }}
            </p>
            <div class="mt-4">
                <button type="button" onclick="openCreatePresetModal()" class="btn btn-primary btn-sm gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i> Create Preset
                </button>
            </div>
        </div>
        @endforelse
    </div>
</div>

<!-- ========================================== -->
<!-- Create / Edit Preset Modal                 -->
<!-- ========================================== -->
<dialog id="preset_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-2xl bg-base-100 border border-base-300 text-base-content p-0 shadow-2xl rounded-2xl overflow-hidden">
        <!-- Modal Header -->
        <div class="p-5 border-b border-base-300 flex items-center justify-between bg-base-200/50">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                    <i data-lucide="sliders" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 id="modal_title" class="font-bold text-base text-base-content">Create AI Preset</h3>
                    <p class="text-xs text-base-content/60">Tuned persona and concurrency directives</p>
                </div>
            </div>
            <button type="button" onclick="closePresetModal()" class="btn btn-sm btn-circle btn-ghost">✕</button>
        </div>

        <!-- Form Body -->
        <form id="preset_form" method="POST" action="{{ route('ai-presets.store') }}" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="_method" id="form_method" value="POST">

            <!-- Preset Name -->
            <div class="form-control">
                <label class="label py-1">
                    <span class="label-text font-bold text-xs">Preset Name <span class="text-error">*</span></span>
                </label>
                <input type="text" name="name" id="modal_name" required placeholder="e.g., SaaS Authority In-Depth" class="input input-bordered input-sm w-full bg-base-200/40 text-xs">
            </div>

            <!-- Provider & Model Selection -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label py-1">
                        <span class="label-text font-bold text-xs">LLM Provider <span class="text-error">*</span></span>
                    </label>
                    <select name="provider" id="modal_provider" required class="select select-bordered select-sm w-full bg-base-200/40 text-xs" onchange="updateModelDropdown()">
                        <option value="gemini">Google Gemini</option>
                        <option value="openai">OpenAI</option>
                        <option value="anthropic">Anthropic Claude</option>
                        <option value="deepseek">DeepSeek</option>
                    </select>
                </div>

                <div class="form-control">
                    <label class="label py-1">
                        <span class="label-text font-bold text-xs">Model Name <span class="text-error">*</span></span>
                    </label>
                    <div class="relative">
                        <select id="modal_model_select" class="select select-bordered select-sm w-full bg-base-200/40 text-xs mb-1" onchange="syncModelInput(this.value)">
                            <!-- Populated dynamically via JS -->
                        </select>
                        <input type="text" name="model" id="modal_model" required placeholder="or enter custom model identifier" class="input input-bordered input-xs w-full bg-base-200/40 font-mono text-[11px]">
                    </div>
                </div>
            </div>

            <!-- Sliders: Temperature & Concurrency -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-base-200/30 p-4 rounded-xl border border-base-300">
                <!-- Temperature -->
                <div class="form-control">
                    <div class="flex items-center justify-between mb-1">
                        <label class="label-text font-bold text-xs">Creativity (Temperature)</label>
                        <span id="temp_display" class="badge badge-sm badge-neutral font-mono">0.7</span>
                    </div>
                    <input type="range" name="temperature" id="modal_temperature" min="0" max="1.5" step="0.1" value="0.7" class="range range-xs range-primary" oninput="document.getElementById('temp_display').textContent = this.value">
                    <div class="flex justify-between text-[10px] text-base-content/40 px-1 mt-1 font-mono">
                        <span>0.0 (Strict)</span>
                        <span>0.7 (Balanced)</span>
                        <span>1.5 (Creative)</span>
                    </div>
                </div>

                <!-- Concurrency Workers -->
                <div class="form-control">
                    <div class="flex items-center justify-between mb-1">
                        <label class="label-text font-bold text-xs">Parallel Section Workers</label>
                        <span id="workers_display" class="badge badge-sm badge-neutral font-mono">3</span>
                    </div>
                    <input type="range" name="max_workers" id="modal_max_workers" min="1" max="8" step="1" value="3" class="range range-xs range-info" oninput="document.getElementById('workers_display').textContent = this.value">
                    <div class="flex justify-between text-[10px] text-base-content/40 px-1 mt-1 font-mono">
                        <span>1 (Sequential)</span>
                        <span>3 (Standard)</span>
                        <span>8 (Turbo)</span>
                    </div>
                </div>
            </div>

            <!-- Custom System Prompt / Instructions -->
            <div class="form-control">
                <label class="label py-1">
                    <span class="label-text font-bold text-xs">Custom Directives / System Prompt</span>
                    <span class="label-text-alt text-base-content/50 text-[11px]">Appended to LLM system instructions</span>
                </label>
                <textarea name="custom_instructions" id="modal_custom_instructions" rows="4" placeholder="Focus on Google E-E-A-T standards. Provide deep first-hand experience insights, expert breakdowns, actionable steps, and clear bullet points..." class="textarea textarea-bordered w-full bg-base-200/40 text-xs leading-relaxed"></textarea>
            </div>

            <!-- Active Default Switch -->
            <div class="form-control">
                <label class="label cursor-pointer justify-start gap-3 py-1">
                    <input type="checkbox" name="is_active" id="modal_is_active" value="1" class="checkbox checkbox-primary checkbox-sm">
                    <span class="label-text text-xs font-semibold">Make this my active default preset for article generation</span>
                </label>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-base-300 flex items-center justify-end gap-2">
                <button type="button" onclick="closePresetModal()" class="btn btn-ghost btn-sm">Cancel</button>
                <button type="submit" id="modal_submit_btn" onclick="submitWithLoader(this)" class="btn btn-primary btn-sm px-6 font-bold submit-loader">
                    Save AI Preset
                </button>
            </div>
        </form>
    </div>
</dialog>

<!-- ========================================== -->
<!-- Delete Confirmation Modal                  -->
<!-- ========================================== -->
<dialog id="delete_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box bg-base-100 border border-base-300 text-base-content max-w-md">
        <h3 class="font-bold text-base text-error flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i> Confirm Deletion
        </h3>
        <p class="py-3 text-xs text-base-content/80">
            Are you sure you want to delete preset <strong id="delete_preset_name" class="text-base-content"></strong>? This action cannot be undone.
        </p>
        <form id="delete_form" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="modal-action">
                <button type="button" onclick="document.getElementById('delete_modal').close()" class="btn btn-ghost btn-sm">Cancel</button>
                <button type="submit" onclick="submitWithLoader(this)" class="btn btn-error btn-sm submit-loader">Delete Preset</button>
            </div>
        </form>
    </div>
</dialog>

<script>
    // Synced models passed from server
    const syncedModels = @json($syncedModels);

    const defaultProviderModels = {
        'gemini': ['gemini-2.0-flash', 'gemini-1.5-pro', 'gemini-1.5-flash'],
        'openai': ['gpt-4o', 'gpt-4o-mini', 'o1-mini'],
        'anthropic': ['claude-3-5-sonnet-20241022', 'claude-3-5-haiku-20241022'],
        'deepseek': ['deepseek-chat', 'deepseek-reasoner']
    };

    function updateModelDropdown(currentModel = null) {
        const provider = document.getElementById('modal_provider').value;
        const select = document.getElementById('modal_model_select');
        const input = document.getElementById('modal_model');
        
        let models = (syncedModels && syncedModels[provider]) ? syncedModels[provider] : defaultProviderModels[provider] || [];
        if (!Array.isArray(models)) models = [];

        select.innerHTML = '<option value="">-- Choose from known models --</option>';
        models.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m;
            opt.textContent = m;
            if (currentModel && m === currentModel) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });

        if (currentModel) {
            input.value = currentModel;
        } else if (models.length > 0) {
            select.selectedIndex = 1;
            input.value = models[0];
        }
    }

    function syncModelInput(val) {
        if (val) {
            document.getElementById('modal_model').value = val;
        }
    }

    function openCreatePresetModal() {
        document.getElementById('modal_title').textContent = 'Create AI Preset';
        document.getElementById('form_method').value = 'POST';
        document.getElementById('preset_form').action = "{{ route('ai-presets.store') }}";
        document.getElementById('modal_name').value = '';
        document.getElementById('modal_provider').value = 'gemini';
        document.getElementById('modal_temperature').value = 0.7;
        document.getElementById('temp_display').textContent = '0.7';
        document.getElementById('modal_max_workers').value = 3;
        document.getElementById('workers_display').textContent = '3';
        document.getElementById('modal_custom_instructions').value = '';
        document.getElementById('modal_is_active').checked = false;
        document.getElementById('modal_submit_btn').textContent = 'Create AI Preset';

        updateModelDropdown('gemini-2.0-flash');
        document.getElementById('preset_modal').showModal();
    }

    function editPreset(preset) {
        document.getElementById('modal_title').textContent = 'Edit AI Preset';
        document.getElementById('form_method').value = 'PUT';
        document.getElementById('preset_form').action = "{{ url('/ai-presets') }}/" + preset.id;
        document.getElementById('modal_name').value = preset.name;
        document.getElementById('modal_provider').value = preset.provider;
        document.getElementById('modal_temperature').value = preset.temperature || 0.7;
        document.getElementById('temp_display').textContent = preset.temperature || 0.7;
        document.getElementById('modal_max_workers').value = preset.max_workers || 3;
        document.getElementById('workers_display').textContent = preset.max_workers || 3;
        document.getElementById('modal_custom_instructions').value = preset.custom_instructions || '';
        document.getElementById('modal_is_active').checked = !!preset.is_active;
        document.getElementById('modal_submit_btn').textContent = 'Update AI Preset';

        updateModelDropdown(preset.model);
        document.getElementById('preset_modal').showModal();
    }

    function closePresetModal() {
        document.getElementById('preset_modal').close();
    }

    function confirmDeletePreset(id, name) {
        document.getElementById('delete_preset_name').textContent = name;
        document.getElementById('delete_form').action = "{{ url('/ai-presets') }}/" + id;
        document.getElementById('delete_modal').showModal();
    }

    function activatePreset(id) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ url('/ai-presets') }}/" + id + "/activate";
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = "{{ csrf_token() }}";
        form.appendChild(csrf);
        document.body.appendChild(form);
        form.submit();
    }

    function clonePreset(id) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ url('/ai-presets') }}/" + id + "/clone";
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = "{{ csrf_token() }}";
        form.appendChild(csrf);
        document.body.appendChild(form);
        form.submit();
    }

    // Initialize lucide icons on dynamic content
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endsection
