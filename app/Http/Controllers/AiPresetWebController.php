<?php

namespace App\Http\Controllers;

use App\Models\AiPreset;
use App\Models\SyncedModel;
use Illuminate\Http\Request;

class AiPresetWebController extends Controller
{
    /**
     * Display a listing of the user's AI presets with telemetry and filters.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Auto-seed standard presets for new users so they start with working templates
        if ($user->presets()->doesntExist()) {
            $this->seedStandardPresetsForUser($user);
        }

        // 2. Query with search and provider filters
        $query = $user->presets();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('custom_instructions', 'like', "%{$search}%");
            });
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->input('provider'));
        }

        $presets = $query->orderBy('is_active', 'desc')->orderBy('created_at', 'desc')->get();

        // 3. Telemetry KPI Metrics
        $totalPresets = $user->presets()->count();
        $activePreset = $user->presets()->where('is_active', true)->first();
        $avgTemp = round((float) ($user->presets()->avg('temperature') ?? 0.7), 2);
        $avgWorkers = round((float) ($user->presets()->avg('max_workers') ?? 3), 1);

        // 4. Synced Models for provider model select dropdowns
        $syncedModels = SyncedModel::all()->pluck('models', 'provider')->toArray();

        return view('pages.presets.index', compact(
            'presets',
            'totalPresets',
            'activePreset',
            'avgTemp',
            'avgWorkers',
            'syncedModels'
        ));
    }

    /**
     * Store a newly created AI preset.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string|in:gemini,openai,anthropic,deepseek',
            'model' => 'required|string|max:255',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'top_p' => 'nullable|numeric|min:0|max:1',
            'max_workers' => 'nullable|integer|min:1|max:10',
            'custom_instructions' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $user = $request->user();

        // If active, deactivate other presets for this user
        if ($request->boolean('is_active')) {
            $user->presets()->update(['is_active' => false]);
        }

        $preset = $user->presets()->create(array_merge($validated, [
            'temperature' => $validated['temperature'] ?? 0.7,
            'max_workers' => $validated['max_workers'] ?? 3,
            'is_active' => $request->boolean('is_active'),
        ]));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "Preset '{$preset->name}' created successfully.", 'preset' => $preset]);
        }

        return redirect()->route('ai-presets.index')->with('success', "AI Preset '{$preset->name}' created successfully.");
    }

    /**
     * Update an existing AI preset.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $preset = $user->presets()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string|in:gemini,openai,anthropic,deepseek',
            'model' => 'required|string|max:255',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'top_p' => 'nullable|numeric|min:0|max:1',
            'max_workers' => 'nullable|integer|min:1|max:10',
            'custom_instructions' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->boolean('is_active')) {
            $user->presets()->where('id', '!=', $preset->id)->update(['is_active' => false]);
        }

        $preset->update(array_merge($validated, [
            'temperature' => $validated['temperature'] ?? $preset->temperature,
            'max_workers' => $validated['max_workers'] ?? $preset->max_workers,
            'is_active' => $request->boolean('is_active') ? true : $preset->is_active,
        ]));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "Preset '{$preset->name}' updated successfully.", 'preset' => $preset]);
        }

        return redirect()->route('ai-presets.index')->with('success', "AI Preset '{$preset->name}' updated successfully.");
    }

    /**
     * Remove the specified AI preset.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $preset = $user->presets()->findOrFail($id);
        $wasActive = $preset->is_active;
        $name = $preset->name;

        $preset->delete();

        // If the deleted preset was active, promote another preset to active
        if ($wasActive) {
            $next = $user->presets()->first();
            if ($next) {
                $next->update(['is_active' => true]);
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "Preset '{$name}' deleted successfully."]);
        }

        return redirect()->route('ai-presets.index')->with('success', "AI Preset '{$name}' deleted successfully.");
    }

    /**
     * Activate the specified AI preset for the user.
     */
    public function activate(Request $request, $id)
    {
        $user = $request->user();
        $preset = $user->presets()->findOrFail($id);

        $user->presets()->where('id', '!=', $preset->id)->update(['is_active' => false]);
        $preset->update(['is_active' => true]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "Activated preset: {$preset->name}", 'preset' => $preset]);
        }

        return redirect()->route('ai-presets.index')->with('success', "Activated AI Preset: {$preset->name}");
    }

    /**
     * Clone an existing AI preset for fast tweaking.
     */
    public function clone(Request $request, $id)
    {
        $user = $request->user();
        $preset = $user->presets()->findOrFail($id);

        $cloned = $user->presets()->create([
            'name' => $preset->name . ' (Copy)',
            'provider' => $preset->provider,
            'model' => $preset->model,
            'temperature' => $preset->temperature,
            'top_p' => $preset->top_p,
            'max_workers' => $preset->max_workers,
            'custom_instructions' => $preset->custom_instructions,
            'is_active' => false,
        ]);

        return redirect()->route('ai-presets.index')->with('success', "Cloned preset as '{$cloned->name}'.");
    }

    /**
     * Seed standard default presets for a user.
     */
    protected function seedStandardPresetsForUser($user): void
    {
        $standards = [
            [
                'name' => 'Google E-E-A-T Standard',
                'provider' => 'gemini',
                'model' => 'gemini-2.0-flash',
                'max_workers' => 3,
                'temperature' => 0.7,
                'custom_instructions' => 'Focus on Google E-E-A-T standards. Provide deep first-hand experience insights, expert breakdown, actionable steps, and clear bullet points.',
                'is_active' => true,
            ],
            [
                'name' => 'Affiliate Buyer Guide',
                'provider' => 'gemini',
                'model' => 'gemini-2.0-flash',
                'max_workers' => 3,
                'temperature' => 0.7,
                'custom_instructions' => 'Focus on commercial intent. Compare features, highlight pros & cons, present clear buyer recommendations, and end with a strong purchasing verdict CTA.',
                'is_active' => false,
            ],
            [
                'name' => 'B2B Whitepaper / Executive',
                'provider' => 'gemini',
                'model' => 'gemini-2.0-flash',
                'max_workers' => 3,
                'temperature' => 0.6,
                'custom_instructions' => 'Write in an authoritative, data-backed corporate tone suitable for enterprise executives, software architects, and decision-makers.',
                'is_active' => false,
            ],
            [
                'name' => 'Viral Blog Post / Engaging',
                'provider' => 'gemini',
                'model' => 'gemini-2.0-flash',
                'max_workers' => 3,
                'temperature' => 0.8,
                'custom_instructions' => 'Write in a highly engaging, conversational tone with storytelling hooks, short snappy paragraphs, and interactive sub-headings.',
                'is_active' => false,
            ],
        ];

        foreach ($standards as $data) {
            $user->presets()->create($data);
        }
    }
}
