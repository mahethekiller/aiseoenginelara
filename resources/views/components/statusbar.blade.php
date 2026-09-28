@php
    $currentUser = auth()->user();
    $activeClient = $currentUser ? \App\Models\Client::find($currentUser->active_client_id) : null;
    $activePreset = $currentUser
        ? ($currentUser->presets()->where('is_active', true)->first() ?? \App\Models\AiPreset::where('is_active', true)->first())
        : \App\Models\AiPreset::where('is_active', true)->first();
@endphp

<footer class="bg-base-100 border-t border-base-300 px-4 py-2 text-xs flex flex-wrap items-center justify-between gap-2 z-20">
    <div class="flex items-center gap-3">
        <span class="flex items-center gap-1.5 font-medium text-base-content/70">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Engine:</span>
            <span class="font-mono text-base-content font-semibold">{{ $activePreset ? $activePreset->model : 'gemini-2.0-flash' }}</span>
        </span>
        <span class="text-base-content/30">•</span>
        <span class="flex items-center gap-1 text-base-content/70">
            <i data-lucide="building-2" class="w-3.5 h-3.5 text-primary"></i>
            <span>Active Client:</span>
            <span class="font-semibold text-base-content">{{ $activeClient ? $activeClient->name : 'Generic (No Brand Voice)' }}</span>
        </span>
    </div>

    <div class="flex items-center gap-3 text-base-content/60 font-mono text-[11px]">
        <span>Zero Mock / 100% Live API</span>
        <span class="text-base-content/30">•</span>
        <span class="badge badge-ghost badge-xs font-mono">v1.2.0-Phase1</span>
    </div>
</footer>
