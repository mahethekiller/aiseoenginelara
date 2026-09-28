<?php

namespace App\Http\Controllers;

use App\Models\AiPreset;
use Illuminate\Http\Request;

class AiPresetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $presets = $user->presets()->orderBy('created_at', 'desc')->get();

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
                $user->presets()->create($presetData);
            }

            $presets = $user->presets()->orderBy('created_at', 'desc')->get();
        }

        return response()->json($presets);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string',
            'model' => 'required|string',
            'max_workers' => 'required|integer|min:1|max:15',
            'temperature' => 'required|numeric|min:0|max:2',
            'custom_instructions' => 'nullable|string',
        ]);

        $preset = $request->user()->presets()->create($request->all());

        return response()->json($preset, 201);
    }

    public function show(Request $request, AiPreset $preset)
    {
        if ($preset->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized Access.'], 403);
        }

        return response()->json($preset);
    }

    public function update(Request $request, AiPreset $preset)
    {
        if ($preset->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized Access.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string',
            'model' => 'required|string',
            'max_workers' => 'required|integer|min:1|max:15',
            'temperature' => 'required|numeric|min:0|max:2',
            'custom_instructions' => 'nullable|string',
        ]);

        $preset->update($request->all());

        return response()->json($preset);
    }

    public function destroy(Request $request, AiPreset $preset)
    {
        if ($preset->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized Access.'], 403);
        }

        $preset->delete();

        return response()->json(['message' => 'Preset deleted successfully']);
    }

    public function activate(Request $request, AiPreset $preset)
    {
        if ($preset->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized Access.'], 403);
        }

        $request->user()->presets()->update(['is_active' => false]);
        $preset->refresh();
        $preset->update(['is_active' => true]);

        return response()->json(['message' => 'Preset activated', 'preset' => $preset]);
    }
}
