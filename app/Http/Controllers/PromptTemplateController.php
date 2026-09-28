<?php

namespace App\Http\Controllers;

use App\Models\AiPromptTemplate;
use App\Models\Client;
use Database\Seeders\AiPromptTemplateSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromptTemplateController extends Controller
{
    /**
     * Display a listing of all prompt templates available to the authenticated user.
     */
    public function index(Request $request)
    {
        $userId = $request->user() ? $request->user()->id : null;
        $clientId = $request->input('client_id');
        $isSuperAdmin = $request->user() && (
            $request->user()->email === 'admin@webaiseo.com' ||
            $request->user()->roles()->whereIn('name', ['admin', 'super_admin'])->exists()
        );

        $templates = AiPromptTemplate::with(['user:id,name,email', 'client:id,name'])
            ->availableFor($userId, $clientId)
            ->orderBy('is_system', 'desc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($t) use ($userId, $isSuperAdmin) {
                $t->is_owner = $isSuperAdmin || ($t->user_id && $t->user_id === $userId);

                return $t;
            });

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'templates' => $templates,
            ]);
        }

        $clients = Client::where('is_active', true)->orderBy('name')->get();

        return view('pages.prompt-templates.index', compact('templates', 'clients'));
    }

    /**
     * Create a new custom prompt template.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'archetype_name' => 'required|string|max:255',
            'system_prompt_template' => 'required|string',
            'description' => 'nullable|string|max:1000',
            'client_id' => 'nullable|exists:clients,id',
            'available_placeholders' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $defaultPlaceholders = [
            'brand_name',
            'audience',
            'tone',
            'content_type_label',
            'primary_keyword',
            'wireframe_layout',
            'word_count',
            'reading_level',
            'seo_title',
            'meta_description',
            'slug',
            'brand_heading',
            'internal_links',
            'cta',
            'schema_directive',
        ];

        $slug = Str::slug($validated['archetype_name']);
        $uniqueKey = 'custom_'.$slug.'_'.substr(md5(uniqid()), 0, 6);

        preg_match_all('/\{\{([a-zA-Z0-9_\-]+)\}\}/', $validated['system_prompt_template'], $extractedMatches);
        $extractedPlaceholders = ! empty($extractedMatches[1]) ? array_values(array_unique($extractedMatches[1])) : [];
        $mergedPlaceholders = array_values(array_unique(array_merge($defaultPlaceholders, $extractedPlaceholders, $validated['available_placeholders'] ?? [])));

        $template = AiPromptTemplate::create([
            'user_id' => $request->user()->id,
            'client_id' => $validated['client_id'] ?? null,
            'archetype_key' => $uniqueKey,
            'archetype_name' => $validated['archetype_name'],
            'description' => $validated['description'] ?? 'User-defined custom prompt template',
            'system_prompt_template' => $validated['system_prompt_template'],
            'available_placeholders' => $mergedPlaceholders,
            'is_active' => $validated['is_active'] ?? true,
            'is_system' => false,
        ]);

        $template->load(['user:id,name,email', 'client:id,name']);
        $template->is_owner = true;

        return response()->json([
            'success' => true,
            'message' => "Successfully created custom prompt '{$template->archetype_name}'.",
            'template' => $template,
        ], 201);
    }

    /**
     * Display the specified prompt template.
     */
    public function show(Request $request, $id)
    {
        $template = AiPromptTemplate::with(['user:id,name,email', 'client:id,name'])->find($id);

        if (! $template) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Prompt template not found.'], 404);
            }

            return redirect()->route('prompt-templates.index')->with('error', 'Prompt template not found.');
        }

        $userId = $request->user() ? $request->user()->id : null;
        $isSuperAdmin = $request->user() && (
            $request->user()->email === 'admin@webaiseo.com' ||
            $request->user()->roles()->whereIn('name', ['admin', 'super_admin'])->exists()
        );

        $template->is_owner = $isSuperAdmin || ($template->user_id && $template->user_id === $userId);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'template' => $template,
            ]);
        }

        return view('pages.prompt-templates.show', compact('template'));
    }

    /**
     * Update the specified prompt template in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $template = AiPromptTemplate::find($id);

        if (! $template) {
            return response()->json(['success' => false, 'message' => 'Prompt template not found.'], 404);
        }

        $userId = $request->user()->id;
        $isSuperAdmin = $request->user()->email === 'admin@webaiseo.com' ||
            $request->user()->roles()->whereIn('name', ['admin', 'super_admin'])->exists();

        // If it's a system template, only super admin can modify it
        if ($template->is_system && ! $isSuperAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'System default templates can only be modified by Super Administrators. Duplicate this template to create your own editable copy.',
            ], 403);
        }

        // If it's a user's custom template, only the owner or super admin can edit it
        if (! $template->is_system && $template->user_id !== $userId && ! $isSuperAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit this custom prompt.',
            ], 403);
        }

        $validated = $request->validate([
            'archetype_name' => 'nullable|string|max:255',
            'system_prompt_template' => 'required|string',
            'description' => 'nullable|string|max:1000',
            'client_id' => 'nullable|exists:clients,id',
            'is_active' => 'nullable|boolean',
        ]);

        if (isset($validated['system_prompt_template'])) {
            preg_match_all('/\{\{([a-zA-Z0-9_\-]+)\}\}/', $validated['system_prompt_template'], $extractedMatches);
            $extracted = ! empty($extractedMatches[1]) ? array_values(array_unique($extractedMatches[1])) : [];
            $existing = is_array($template->available_placeholders) ? $template->available_placeholders : [];
            $validated['available_placeholders'] = array_values(array_unique(array_merge($existing, $extracted)));
        }

        $template->update($validated);
        $template->load(['user:id,name,email', 'client:id,name']);
        $template->is_owner = true;

        return response()->json([
            'success' => true,
            'message' => "Successfully updated prompt template for '{$template->archetype_name}'.",
            'template' => $template,
        ]);
    }

    /**
     * Delete a custom prompt template.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $template = AiPromptTemplate::find($id);

        if (! $template) {
            return response()->json(['success' => false, 'message' => 'Prompt template not found.'], 404);
        }

        if ($template->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'System default archetype templates cannot be deleted.',
            ], 403);
        }

        $userId = $request->user()->id;
        $isSuperAdmin = $request->user()->email === 'admin@webaiseo.com' ||
            $request->user()->roles()->whereIn('name', ['admin', 'super_admin'])->exists();

        if ($template->user_id !== $userId && ! $isSuperAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this custom prompt.',
            ], 403);
        }

        $name = $template->archetype_name;
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted custom prompt '{$name}'.",
        ]);
    }

    /**
     * Duplicate an existing template into a new custom user template.
     */
    public function duplicate(Request $request, $id): JsonResponse
    {
        $source = AiPromptTemplate::find($id);

        if (! $source) {
            return response()->json(['success' => false, 'message' => 'Source prompt template not found.'], 404);
        }

        $baseName = preg_replace('/ \(Custom Copy( \d+)?\)$/', '', $source->archetype_name);
        $newName = $baseName.' (Custom Copy)';
        $slug = Str::slug($newName);
        $uniqueKey = 'custom_'.$slug.'_'.substr(md5(uniqid()), 0, 6);

        $clone = AiPromptTemplate::create([
            'user_id' => $request->user()->id,
            'client_id' => $source->client_id,
            'archetype_key' => $uniqueKey,
            'archetype_name' => $newName,
            'description' => $source->description ? "Copy of {$source->description}" : "Custom clone of {$source->archetype_name}",
            'system_prompt_template' => $source->system_prompt_template,
            'available_placeholders' => $source->available_placeholders,
            'is_active' => true,
            'is_system' => false,
        ]);

        $clone->load(['user:id,name,email', 'client:id,name']);
        $clone->is_owner = true;

        return response()->json([
            'success' => true,
            'message' => "Cloned '{$source->archetype_name}' as a new editable custom prompt.",
            'template' => $clone,
        ], 201);
    }

    /**
     * Reset system template to default seeder template.
     */
    public function reset(Request $request, $id): JsonResponse
    {
        $template = AiPromptTemplate::find($id);

        if (! $template) {
            return response()->json(['success' => false, 'message' => 'Prompt template not found.'], 404);
        }

        if (! $template->is_system) {
            return response()->json(['success' => false, 'message' => 'Only system default templates can be reset.'], 400);
        }

        // Re-run seeder for system templates
        $seeder = new AiPromptTemplateSeeder;
        $seeder->run();

        $freshTemplate = AiPromptTemplate::find($id);

        return response()->json([
            'success' => true,
            'message' => "Successfully reset {$template->archetype_name} template to system default.",
            'template' => $freshTemplate,
        ]);
    }
}
