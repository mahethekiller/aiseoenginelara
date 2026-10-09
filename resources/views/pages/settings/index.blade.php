@extends('layouts.app')

@section('title', 'Settings & Presets')
@section('page_title', 'Settings & Presets')
@section('page_badge', 'Multi-Provider LLM Orchestration')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="badge badge-primary badge-outline badge-sm font-mono">LLM Core</span>
                <span class="text-xs text-base-content/60">Live API Keys & Model Tuning</span>
            </div>
            <h1 class="text-xl font-black text-base-content mt-1">Multi-Provider Presets & API Settings</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: API Keys & Live Sync (5 Cols) -->
        <div class="lg:col-span-5 space-y-6">
            <!-- API Keys Form -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-5">
                <div class="flex items-center gap-2 pb-3 mb-3 border-b border-base-300">
                    <i data-lucide="key" class="w-4 h-4 text-primary"></i>
                    <h2 class="text-sm font-bold text-base-content">Provider API Keys (Encrypted)</h2>
                </div>

                <form id="api-keys-form" class="space-y-3.5">
                    @csrf
                    <div>
                        <label class="label py-0.5 text-xs font-semibold">Google Gemini API Key</label>
                        <input type="password" name="api_keys[gemini]"
                               value="{{ $config['api_keys']['gemini'] ?? '' }}"
                               placeholder="AIzaSy••••••••••••••••••••••••••••••••"
                               class="input input-bordered input-sm w-full bg-base-200/50 text-xs font-mono" />
                    </div>

                    <div>
                        <label class="label py-0.5 text-xs font-semibold">OpenAI API Key</label>
                        <input type="password" name="api_keys[openai]"
                               value="{{ $config['api_keys']['openai'] ?? '' }}"
                               placeholder="sk-proj-••••••••••••••••••••••••••••••••"
                               class="input input-bordered input-sm w-full bg-base-200/50 text-xs font-mono" />
                    </div>

                    <div>
                        <label class="label py-0.5 text-xs font-semibold">Anthropic Claude API Key</label>
                        <input type="password" name="api_keys[anthropic]"
                               value="{{ $config['api_keys']['anthropic'] ?? '' }}"
                               placeholder="sk-ant-••••••••••••••••••••••••••••••••"
                               class="input input-bordered input-sm w-full bg-base-200/50 text-xs font-mono" />
                    </div>

                    <div>
                        <label class="label py-0.5 text-xs font-semibold">DeepSeek API Key</label>
                        <input type="password" name="api_keys[deepseek]"
                               value="{{ $config['api_keys']['deepseek'] ?? '' }}"
                               placeholder="sk-••••••••••••••••••••••••••••••••"
                               class="input input-bordered input-sm w-full bg-base-200/50 text-xs font-mono" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="label py-0.5 text-xs font-semibold">SerpApi Key (Google SERP Intelligence & Rank Tracker)</label>
                            @if(!empty($config['api_keys']['serpapi']))
                                <span class="badge badge-success badge-xs font-mono">DB Active</span>
                            @else
                                <span class="badge badge-ghost badge-xs font-mono">Not Set</span>
                            @endif
                        </div>
                        <input type="password" id="serpapi_key_field" name="api_keys[serpapi]"
                               value="{{ $config['api_keys']['serpapi'] ?? '' }}"
                               placeholder="••••••••••••••••••••••••••••••••"
                               class="input input-bordered input-sm w-full bg-base-200/50 text-xs font-mono" />
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="button" onclick="saveApiKeys(this)" class="btn btn-primary btn-sm font-bold shadow-xs">
                            Save API Credentials
                        </button>
                    </div>
                </form>
            </div>

            <!-- Live SerpApi Credits Card -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-5">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-base-300">
                    <div class="flex items-center gap-2">
                        <i data-lucide="zap" class="w-4 h-4 text-amber-500"></i>
                        <h2 class="text-sm font-bold text-base-content">SerpApi Account & Credits</h2>
                    </div>
                    <button type="button" onclick="verifySerpApiAccount(this)" class="btn btn-ghost btn-circle btn-xs" title="Refresh Live Balance">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    </button>
                </div>

                <div id="serpapi-credits-container" class="space-y-3">
                    @if(!empty($serpCredits['has_key']) && !empty($serpCredits['success']))
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs text-base-content/60 font-medium">Plan:</span>
                                <span class="font-bold text-xs text-base-content ml-1">{{ $serpCredits['plan_name'] ?? 'Production' }}</span>
                            </div>
                            <span class="badge badge-success badge-xs font-mono">Connected</span>
                        </div>

                        <div class="bg-base-200/60 p-3.5 rounded-xl border border-base-300 space-y-2">
                            <div class="flex justify-between items-baseline">
                                <span class="text-xs text-base-content/70 font-semibold">Available Search Balance</span>
                                <span class="font-mono font-bold text-base text-primary">
                                    {{ number_format($serpCredits['searches_left'] ?? 0) }}
                                </span>
                            </div>
                            @if(($serpCredits['extra_credits'] ?? 0) > 0)
                                <div class="flex items-center justify-between text-[11px] bg-base-100/80 px-2.5 py-1.5 rounded-lg border border-base-300/70 font-mono">
                                    <span class="text-base-content/70">Prepaid Extra Credits:</span>
                                    <span class="text-emerald-500 font-bold">+{{ number_format($serpCredits['extra_credits']) }} searches</span>
                                </div>
                            @endif
                            @php
                                $totalLimit = max(1, $serpCredits['searches_per_month'] ?? 250);
                                $usedCount = $serpCredits['this_month_usage'] ?? 0;
                            @endphp
                            <div class="flex justify-between text-[10px] text-base-content/50 font-mono pt-1 border-t border-base-300/50">
                                <span>Monthly Plan Quota: {{ number_format($usedCount) }} / {{ number_format($totalLimit) }} used</span>
                                <span>Account: {{ $serpCredits['account_status'] ?? 'Active' }}</span>
                            </div>
                        </div>

                        <div class="text-[11px] text-base-content/60 flex items-center justify-between">
                            <span>Account: <span class="font-mono">{{ $serpCredits['account_email'] ?? 'Active' }}</span></span>
                            <span class="text-[10px] text-base-content/40">Hourly: {{ $serpCredits['last_hour_searches'] ?? 0 }} searches</span>
                        </div>
                    @else
                        <div class="alert alert-warning py-2 text-xs rounded-xl">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                            <div>
                                <div class="font-bold">SerpApi Not Connected</div>
                                <div class="text-[11px]">{{ $serpCredits['error'] ?? 'Enter your SerpApi key above and click Save API Credentials.' }}</div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="pt-2">
                    <button type="button" onclick="verifySerpApiAccount(this)" class="btn btn-outline btn-xs w-full gap-1.5 border-base-300">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-500"></i>
                        <span>Verify Key & Fetch Live Credits</span>
                    </button>
                </div>
            </div>

            <!-- Live Model Sync Card -->
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-5">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-base-300">
                    <div class="flex items-center gap-2">
                        <i data-lucide="refresh-cw" class="w-4 h-4 text-emerald-500"></i>
                        <h2 class="text-sm font-bold text-base-content">Live Model Discovery</h2>
                    </div>
                </div>
                <p class="text-xs text-base-content/60 mb-3">Sync official model catalogues live directly from provider REST endpoints.</p>

                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="syncProviderModels('gemini', this)" class="btn btn-xs btn-outline border-base-300 gap-1.5 py-1">
                        <span>Sync Gemini</span>
                    </button>
                    <button type="button" onclick="syncProviderModels('openai', this)" class="btn btn-xs btn-outline border-base-300 gap-1.5 py-1">
                        <span>Sync OpenAI</span>
                    </button>
                    <button type="button" onclick="syncProviderModels('anthropic', this)" class="btn btn-xs btn-outline border-base-300 gap-1.5 py-1">
                        <span>Sync Claude</span>
                    </button>
                    <button type="button" onclick="syncProviderModels('deepseek', this)" class="btn btn-xs btn-outline border-base-300 gap-1.5 py-1">
                        <span>Sync DeepSeek</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right: AI Presets Manager (7 Cols) -->
        <div class="lg:col-span-7 space-y-6">
            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-5">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-base-300">
                    <div class="flex items-center gap-2">
                        <i data-lucide="sliders" class="w-4 h-4 text-indigo-500"></i>
                        <h2 class="text-sm font-bold text-base-content">AI Presets & Temperature Directives</h2>
                    </div>
                    <button type="button" onclick="openCreatePresetModal()" class="btn btn-primary btn-xs gap-1">
                        <i data-lucide="plus" class="w-3 h-3"></i> Add Preset
                    </button>
                </div>

                <div class="space-y-3">
                    @forelse($presets as $p)
                    <div class="p-4 rounded-xl border {{ $p->is_active ? 'border-primary bg-primary/5' : 'border-base-300 bg-base-200/30' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-bold text-xs text-base-content">{{ $p->name }}</h3>
                                <span class="badge badge-xs badge-neutral font-mono">{{ $p->provider }}</span>
                                <span class="badge badge-xs badge-ghost font-mono">{{ $p->model }}</span>
                                @if($p->is_active)
                                    <span class="badge badge-xs badge-primary font-mono">ACTIVE DEFAULT</span>
                                @endif
                            </div>
                            <p class="text-[11px] text-base-content/60 mt-1 line-clamp-2">
                                {{ $p->custom_instructions ?: 'No custom system prompt tuning.' }}
                            </p>
                            <div class="text-[10px] font-mono text-base-content/50 mt-1 flex gap-3">
                                <span>Temp: {{ $p->temperature }}</span>
                                <span>Workers: {{ $p->max_workers }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            @if(!$p->is_active)
                            <button type="button" onclick="activatePreset({{ $p->id }})" class="btn btn-xs btn-outline btn-primary px-2.5 rounded-lg">
                                Activate
                            </button>
                            @endif
                            <button type="button" onclick="editPreset({{ $p->toJson() }})" class="btn btn-xs btn-square btn-outline btn-warning rounded-lg" title="Edit Preset">
                                <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                            </button>
                            <button type="button" onclick="deletePreset({{ $p->id }})" class="btn btn-xs btn-square btn-outline btn-error rounded-lg" title="Delete Preset">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-6 text-base-content/50 text-xs">
                        No AI presets created yet.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preset Create / Edit Modal -->
<dialog id="preset_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-2xl max-h-[90vh] flex flex-col bg-base-100 border border-base-300 text-base-content p-0 shadow-2xl rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-base-300 bg-base-200/50 flex items-center justify-between shrink-0">
            <h3 id="preset-modal-title" class="font-bold text-sm">Add AI Preset</h3>
            <form method="dialog"><button class="btn btn-xs btn-circle btn-ghost">✕</button></form>
        </div>

        <form id="preset-form" class="flex-1 overflow-y-auto min-h-0 flex flex-col">
            @csrf
            <input type="hidden" id="p_id" name="id" value="" />

            <div class="p-6 space-y-4 flex-1">
                <div>
                    <label class="label py-0.5 text-xs font-semibold">Preset Name <span class="text-error">*</span></label>
                    <input type="text" id="p_name" name="name" required placeholder="e.g. Gemini 2.0 Flash - High Speed" class="input input-bordered input-sm w-full bg-base-200/50 text-xs" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label py-0.5 text-xs font-semibold">LLM Provider <span class="text-error">*</span></label>
                        <select id="p_provider" name="provider" class="select select-bordered select-sm w-full bg-base-200/50 text-xs">
                            <option value="gemini">Google Gemini</option>
                            <option value="openai">OpenAI</option>
                            <option value="anthropic">Anthropic Claude</option>
                            <option value="deepseek">DeepSeek</option>
                        </select>
                    </div>
                    <div>
                        <label class="label py-0.5 text-xs font-semibold">Model Identifier <span class="text-error">*</span></label>
                        <input type="text" id="p_model" name="model" required placeholder="e.g. gemini-2.0-flash, gpt-4o, claude-3-7-sonnet" class="input input-bordered input-sm w-full bg-base-200/50 text-xs font-mono" />
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="label py-0.5 text-xs font-semibold">Temperature</label>
                        <input type="number" step="0.05" min="0" max="2" id="p_temperature" name="temperature" value="0.70" class="input input-bordered input-sm w-full bg-base-200/50 text-xs font-mono" />
                    </div>
                    <div>
                        <label class="label py-0.5 text-xs font-semibold">Top P</label>
                        <input type="number" step="0.05" min="0" max="1" id="p_top_p" name="top_p" value="0.95" class="input input-bordered input-sm w-full bg-base-200/50 text-xs font-mono" />
                    </div>
                    <div>
                        <label class="label py-0.5 text-xs font-semibold">Max Workers</label>
                        <input type="number" min="1" max="10" id="p_max_workers" name="max_workers" value="4" class="input input-bordered input-sm w-full bg-base-200/50 text-xs font-mono" />
                    </div>
                </div>

                <div>
                    <label class="label py-0.5 text-xs font-semibold">Custom System Instructions / Personality</label>
                    <textarea id="p_custom_instructions" name="custom_instructions" rows="3" placeholder="Additional prompt instructions attached to all generations using this preset..." class="textarea textarea-bordered textarea-sm w-full bg-base-200/50 text-xs"></textarea>
                </div>
            </div>

            <div class="px-6 py-3.5 bg-base-200/50 border-t border-base-300 flex items-center justify-between shrink-0 sticky bottom-0 z-10">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="p_is_active" name="is_active" value="1" class="checkbox checkbox-primary checkbox-xs" />
                    <span class="text-xs font-medium">Set as Default Active Preset</span>
                </label>
                <div class="flex gap-2">
                    <button type="button" onclick="document.getElementById('preset_modal').close()" class="btn btn-ghost btn-sm">Cancel</button>
                    <button type="button" onclick="savePresetRecord(this)" class="btn btn-primary btn-sm font-bold shadow-xs">Save Preset</button>
                </div>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('scripts')
<script>
    function saveApiKeys(btn) {
        $(btn).attr('disabled', 'disabled').addClass('opacity-75');
        const orig = $(btn).html();
        $(btn).html('<span class="loading loading-spinner loading-xs me-1"></span> Saving...');

        $.ajax({
            url: "{{ route('settings.api_keys') }}",
            type: 'POST',
            data: $('#api-keys-form').serialize(),
            success: function(res) {
                $(btn).removeAttr('disabled').removeClass('opacity-75').html(orig);
                showToast(res.message || 'API keys saved successfully!', 'success');
            },
            error: function(xhr) {
                $(btn).removeAttr('disabled').removeClass('opacity-75').html(orig);
                showToast(xhr.responseJSON?.message || 'Failed to save API keys.', 'error');
            }
        });
    }

    function verifySerpApiAccount(btn) {
        $(btn).attr('disabled', 'disabled');
        const orig = $(btn).html();
        $(btn).html('<span class="loading loading-spinner loading-xs me-1"></span> Checking...');

        const currentKeyVal = $('#serpapi_key_field').val();

        $.ajax({
            url: "{{ route('settings.serpapi.verify') }}",
            type: 'POST',
            data: { api_key: currentKeyVal },
            success: function(res) {
                $(btn).removeAttr('disabled').html(orig);
                if (res.success) {
                    showToast('SerpApi connected! Searches remaining: ' + res.searches_left, 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showToast(res.error || 'Failed to verify SerpApi key.', 'error');
                }
            },
            error: function(xhr) {
                $(btn).removeAttr('disabled').html(orig);
                showToast(xhr.responseJSON?.error || 'Verification failed. Please check your SerpApi key.', 'error');
            }
        });
    }

    function syncProviderModels(provider, btn) {
        $(btn).attr('disabled', 'disabled');
        const orig = $(btn).html();
        $(btn).html('<span class="loading loading-spinner loading-xs"></span>');

        $.ajax({
            url: "{{ route('settings.models.sync') }}",
            type: 'POST',
            data: { provider: provider },
            success: function(res) {
                $(btn).removeAttr('disabled').html(orig);
                showToast('Synced ' + (res.synced_models?.length || 0) + ' models for ' + provider + '!', 'success');
            },
            error: function(xhr) {
                $(btn).removeAttr('disabled').html(orig);
                showToast(xhr.responseJSON?.message || 'Sync failed. Verify API key.', 'error');
            }
        });
    }

    function openCreatePresetModal() {
        $('#preset-modal-title').text('Add AI Preset');
        $('#p_id').val('');
        $('#preset-form')[0].reset();
        document.getElementById('preset_modal').showModal();
    }

    function editPreset(p) {
        $('#preset-modal-title').text('Edit Preset: ' + p.name);
        $('#p_id').val(p.id);
        $('#p_name').val(p.name);
        $('#p_provider').val(p.provider);
        $('#p_model').val(p.model);
        $('#p_temperature').val(p.temperature);
        $('#p_top_p').val(p.top_p || 0.95);
        $('#p_max_workers').val(p.max_workers || 4);
        $('#p_custom_instructions').val(p.custom_instructions || '');
        $('#p_is_active').prop('checked', p.is_active == 1);
        document.getElementById('preset_modal').showModal();
    }

    function savePresetRecord(btn) {
        $(btn).attr('disabled', 'disabled').addClass('opacity-75');
        const orig = $(btn).html();
        $(btn).html('<span class="loading loading-spinner loading-xs me-1"></span> Saving...');

        $.ajax({
            url: "{{ route('settings.presets.save') }}",
            type: 'POST',
            data: $('#preset-form').serialize(),
            success: function(res) {
                document.getElementById('preset_modal').close();
                showToast(res.message || 'Preset saved successfully!', 'success');
                setTimeout(() => window.location.reload(), 300);
            },
            error: function(xhr) {
                $(btn).removeAttr('disabled').removeClass('opacity-75').html(orig);
                showToast(xhr.responseJSON?.message || 'Error saving preset.', 'error');
            }
        });
    }

    function activatePreset(id) {
        $.ajax({
            url: "/settings/presets/" + id + "/activate",
            type: 'POST',
            success: function(res) {
                showToast(res.message || 'Preset activated!', 'success');
                setTimeout(() => window.location.reload(), 300);
            },
            error: function() {
                showToast('Failed to activate preset.', 'error');
            }
        });
    }

    function deletePreset(id) {
        if (!confirm('Are you sure you want to delete this preset?')) return;

        $.ajax({
            url: "/settings/presets/" + id,
            type: 'DELETE',
            success: function(res) {
                showToast(res.message || 'Preset deleted successfully.', 'success');
                setTimeout(() => window.location.reload(), 300);
            },
            error: function() {
                showToast('Failed to delete preset.', 'error');
            }
        });
    }
</script>
@endpush
