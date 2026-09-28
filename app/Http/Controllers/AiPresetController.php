<?php

namespace App\Http\Controllers;

use App\Models\AiPreset;
use Illuminate\Http\Request;

class AiPresetController extends Controller
{
    public function index(Request $request)
    {
        $presets = AiPreset::orderBy('created_at', 'desc')->get();

        if ($presets->isEmpty()) {
            $standardPresets = [
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

            foreach ($standardPresets as $presetData) {
                AiPreset::create(array_merge($presetData, ['user_id' => $request->user()->id]));
            }

            $presets = AiPreset::orderBy('created_at', 'desc')->get();
        }

        return response()->json($presets);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string',
            'model' => 'required|string',
            'max_workers' => 'required|integer|min:1|max:15',
            'temperature' => 'required|numeric|min:0|max:2',
            'custom_instructions' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->boolean('is_active')) {
            AiPreset::query()->update(['is_active' => false]);
        }

        $preset = AiPreset::create(array_merge($validated, ['user_id' => $request->user()->id]));

        return response()->json($preset, 201);
    }

    public function show(Request $request, AiPreset $preset)
    {
        return response()->json($preset);
    }

    public function update(Request $request, AiPreset $preset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string',
            'model' => 'required|string',
            'max_workers' => 'required|integer|min:1|max:15',
            'temperature' => 'required|numeric|min:0|max:2',
            'custom_instructions' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->boolean('is_active')) {
            AiPreset::query()->where('id', '!=', $preset->id)->update(['is_active' => false]);
        }

        $preset->update($validated);

        return response()->json($preset);
    }

    public function destroy(Request $request, AiPreset $preset)
    {
        $preset->delete();

        return response()->json(['message' => 'Preset deleted successfully']);
    }

    public function activate(Request $request, AiPreset $preset)
    {
        AiPreset::query()->update(['is_active' => false]);
        $preset->refresh();
        $preset->update(['is_active' => true]);

        return response()->json(['message' => 'Preset activated', 'preset' => $preset]);
    }

    public function switchActivePreset(Request $request)
    {
        $request->validate([
            'preset_id' => 'required|exists:ai_presets,id',
        ]);

        $user = $request->user();
        $targetPreset = AiPreset::findOrFail($request->preset_id);

        if ($user) {
            AiPreset::where('user_id', $user->id)->update(['is_active' => false]);
            $targetPreset->update(['is_active' => true]);
        } else {
            AiPreset::query()->update(['is_active' => false]);
            $targetPreset->update(['is_active' => true]);
        }

        // Also update JSON configuration fallback
        $path = 'config/app_config.json';
        if (\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            $config = json_decode(\Illuminate\Support\Facades\Storage::disk('local')->get($path), true);
            $config['current_provider'] = $targetPreset->provider;
            $config['current_model'] = $targetPreset->model;
            $config['active_preset_id'] = $targetPreset->id;
            \Illuminate\Support\Facades\Storage::disk('local')->put($path, json_encode($config, JSON_PRETTY_PRINT));
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Switched AI Preset to: {$targetPreset->name} ({$targetPreset->model})",
                'preset' => $targetPreset,
            ]);
        }

        return back()->with('success', "Switched AI Preset to: {$targetPreset->name} ({$targetPreset->model})");
    }
}
