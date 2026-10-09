<?php

namespace App\Http\Controllers;

use App\Models\AiPreset;
use App\Models\Client;
use App\Models\SyncedModel;
use App\Models\SystemSetting;
use App\Services\SerpApiClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $configResponse = $this->getConfig($request);
        $config = $configResponse->getData(true);

        $currentUser = $request->user();
        if ($currentUser && $currentUser->presets()->exists()) {
            $presets = $currentUser->presets()->orderBy('created_at', 'desc')->get();
        } else {
            $presets = AiPreset::where(function ($q) use ($currentUser) {
                if ($currentUser) {
                    $q->where('user_id', $currentUser->id);
                } else {
                    $q->whereNull('user_id');
                }
            })->orderBy('created_at', 'desc')->get();

            if ($presets->isEmpty()) {
                $presets = AiPreset::all()->unique('name');
            }
        }

        $syncedModels = SyncedModel::all()->pluck('models', 'provider')->toArray();
        $serpCredits = app(SerpApiClientService::class)->getAccountCredits();

        return view('pages.settings.index', compact('config', 'presets', 'syncedModels', 'serpCredits'));
    }

    public function saveApiKeys(Request $request)
    {
        $response = $this->updateConfig($request);
        if ($request->expectsJson() || $request->ajax()) {
            return $response;
        }

        return back()->with('success', 'API Keys saved successfully.');
    }

    public function savePreset(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string',
            'model' => 'required|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'top_p' => 'nullable|numeric|min:0|max:1',
            'max_workers' => 'nullable|integer|min:1|max:10',
            'custom_instructions' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $user = $request->user();
        if ($request->boolean('is_active')) {
            if ($user) {
                AiPreset::where('user_id', $user->id)->update(['is_active' => false]);
            } else {
                AiPreset::query()->update(['is_active' => false]);
            }
        }

        $presetId = $request->input('id');
        if ($presetId) {
            $preset = AiPreset::findOrFail($presetId);
            $preset->update($validated);
        } else {
            $preset = AiPreset::create(array_merge($validated, ['user_id' => $user?->id]));
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Preset saved successfully.', 'preset' => $preset]);
        }

        return back()->with('success', 'Preset saved successfully.');
    }

    public function activatePreset(Request $request, $id)
    {
        $user = $request->user();
        if ($user) {
            AiPreset::where('user_id', $user->id)->update(['is_active' => false]);
        } else {
            AiPreset::query()->update(['is_active' => false]);
        }

        $preset = AiPreset::findOrFail($id);
        $preset->update(['is_active' => true]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => "Activated preset: {$preset->name}", 'preset' => $preset]);
        }

        return back()->with('success', "Activated preset: {$preset->name}");
    }

    public function deletePreset(Request $request, $id)
    {
        $preset = AiPreset::findOrFail($id);
        $preset->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Preset deleted successfully.']);
        }

        return back()->with('success', 'Preset deleted successfully.');
    }

    public function getConfig(Request $request)
    {
        $path = 'config/app_config.json';
        if (Storage::disk('local')->exists($path)) {
            $config = json_decode(Storage::disk('local')->get($path), true);
        } else {
            $config = [
                'execution_mode' => 'live',
                'current_provider' => 'gemini',
                'current_model' => 'gemini-3.5-flash',
                'provider_base_urls' => [
                    'ollama' => 'http://localhost:11434/v1',
                ],
                'api_keys' => [
                    'openai' => '',
                    'gemini' => '',
                    'anthropic' => '',
                    'deepseek' => '',
                    'groq' => '',
                    'semrush' => '',
                    'serpapi' => '',
                    'gsc_json' => '',
                    'ga4_property_id' => '',
                    'google_ads' => '',
                    'wordpress_url' => '',
                    'wordpress_app_password' => '',
                ],
            ];
        }

        // Check DB for SerpApi key override
        $dbSerpKey = SystemSetting::get('serpapi_key');
        if (! empty($dbSerpKey)) {
            $config['api_keys']['serpapi'] = $dbSerpKey;
        }

        // Mask keys for client
        if (isset($config['api_keys'])) {
            foreach ($config['api_keys'] as $provider => $key) {
                if ($key) {
                    $config['api_keys'][$provider] = substr($key, 0, 4).'••••••••••••'.substr($key, -4);
                }
            }
        }

        // Attach active preset from DB
        $user = $request->user();
        if ($user) {
            $activePreset = AiPreset::where('user_id', $user->id)->where('is_active', true)->first()
                ?? AiPreset::where('is_active', true)->first();
            $config['active_preset'] = $activePreset;
        }

        // Attach active client specifically for the authenticated user
        $activeClient = null;
        if ($user && ! empty($user->active_client_id)) {
            $activeClient = Client::find($user->active_client_id);
        }
        $config['active_client'] = $activeClient;

        // Fetch synced models from DB and attach to configuration payload
        $synced = SyncedModel::all()->pluck('models', 'provider')->toArray();
        $config['synced_models'] = $synced;

        return response()->json($config);
    }

    public function updateConfig(Request $request)
    {
        $path = 'config/app_config.json';
        $existingConfig = [];
        if (Storage::disk('local')->exists($path)) {
            $existingConfig = json_decode(Storage::disk('local')->get($path), true);
        }

        $newApiKeys = $request->api_keys ?? [];
        $mergedApiKeys = $existingConfig['api_keys'] ?? [];

        foreach ($newApiKeys as $provider => $key) {
            if ($key && ! str_contains($key, '••••')) {
                $mergedApiKeys[$provider] = $key;
                if ($provider === 'serpapi') {
                    SystemSetting::set('serpapi_key', $key, 'api_keys', true, 'SerpApi API key for Google SERP intelligence');
                }
            }
        }

        $executionMode = 'live';
        $currentProvider = $request->input('current_provider', $existingConfig['current_provider'] ?? 'gemini');
        $currentModel = $request->input('current_model', $existingConfig['current_model'] ?? 'gemini-3.5-flash');

        $config = [
            'execution_mode' => $executionMode,
            'current_provider' => $currentProvider,
            'current_model' => $currentModel,
            'provider_base_urls' => $request->provider_base_urls ?? ($existingConfig['provider_base_urls'] ?? []),
            'api_keys' => $mergedApiKeys,
        ];

        Storage::disk('local')->put($path, json_encode($config, JSON_PRETTY_PRINT));

        // Update active client specifically for the authenticated user
        if ($request->has('active_client_id')) {
            $user = $request->user();
            if ($user) {
                $rawId = $request->input('active_client_id');
                $user->active_client_id = (! empty($rawId) && $rawId !== 'none') ? $rawId : null;
                $user->save();
            }
        }

        // Update active preset in DB if active_preset_id passed
        if ($request->has('active_preset_id')) {
            $user = $request->user();
            if ($user) {
                AiPreset::where('user_id', $user->id)->update(['is_active' => false]);
                AiPreset::where('id', $request->input('active_preset_id'))->update(['is_active' => true]);
            }
        }

        return response()->json(['message' => 'Configuration saved successfully', 'config' => $config]);
    }

    public function verifySerpApiKey(Request $request, SerpApiClientService $serpClient)
    {
        $key = $request->input('api_key');
        if (! empty($key) && ! str_contains($key, '••••')) {
            $serpClient->setApiKey($key);
        }

        $credits = $serpClient->getAccountCredits(true);

        return response()->json($credits);
    }

    public function syncModels(Request $request)
    {
        $request->validate([
            'provider' => 'required|string',
        ]);

        $path = 'config/app_config.json';
        $apiKey = '';
        if (Storage::disk('local')->exists($path)) {
            $config = json_decode(Storage::disk('local')->get($path), true);
            $apiKey = $config['api_keys'][$request->provider] ?? '';
        }
        if (! $apiKey) {
            $apiKey = env(strtoupper($request->provider).'_API_KEY', '');
        }

        $models = [];

        if ($request->provider === 'gemini') {
            if (! $apiKey) {
                return response()->json(['message' => 'Gemini API key is not configured.'], 400);
            }
            $response = Http::get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
            if ($response->successful()) {
                $data = $response->json();
                foreach ($data['models'] ?? [] as $m) {
                    $name = str_replace('models/', '', $m['name']);
                    if (str_contains($name, 'gemini')) {
                        $models[] = $name;
                    }
                }
            } else {
                return response()->json(['message' => 'Failed to fetch Gemini models: '.$response->body()], 500);
            }
        } elseif ($request->provider === 'openai') {
            if (! $apiKey) {
                return response()->json(['message' => 'OpenAI API key is not configured.'], 400);
            }
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->get('https://api.openai.com/v1/models');
            if ($response->successful()) {
                $data = $response->json();
                foreach ($data['data'] ?? [] as $m) {
                    $id = $m['id'];
                    if (str_starts_with($id, 'gpt') || str_starts_with($id, 'o1')) {
                        $models[] = $id;
                    }
                }
            } else {
                return response()->json(['message' => 'Failed to fetch OpenAI models: '.$response->body()], 500);
            }
        } elseif ($request->provider === 'anthropic') {
            if (! $apiKey) {
                return response()->json(['message' => 'Anthropic API key is not configured.'], 400);
            }
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->get('https://api.anthropic.com/v1/models');
            if ($response->successful()) {
                $data = $response->json();
                foreach ($data['data'] ?? [] as $m) {
                    $models[] = $m['id'];
                }
            } else {
                return response()->json(['message' => 'Failed to fetch Anthropic models: '.$response->body()], 500);
            }
        } else {
            // Fallback for providers without public endpoint mapping
            $models = match ($request->provider) {
                'deepseek' => ['deepseek-chat', 'deepseek-reasoner'],
                default => ['standard-model'],
            };
        }

        if (empty($models)) {
            $models = match ($request->provider) {
                'openai' => ['gpt-4o-mini', 'gpt-4o', 'o1-mini'],
                'gemini' => ['gemini-3.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-1.5-pro'],
                'anthropic' => ['claude-3-5-sonnet-20241022', 'claude-3-5-haiku-20241022'],
                'deepseek' => ['deepseek-chat', 'deepseek-reasoner'],
                default => ['standard-model'],
            };
        }

        // Save synced models in persistent database table
        SyncedModel::updateOrCreate(
            ['provider' => $request->provider],
            ['models' => $models]
        );

        // Also save in JSON config for compatibility fallback
        $existingConfig = [];
        if (Storage::disk('local')->exists($path)) {
            $existingConfig = json_decode(Storage::disk('local')->get($path), true);
        }
        $existingConfig['synced_models'][$request->provider] = $models;
        Storage::disk('local')->put($path, json_encode($existingConfig, JSON_PRETTY_PRINT));

        return response()->json([
            'provider' => $request->provider,
            'synced_models' => $models,
            'status' => 'success',
        ]);
    }

    public function listModels(Request $request)
    {
        $synced = SyncedModel::all()->pluck('models', 'provider')->toArray();

        return response()->json($synced);
    }

    public function addCustomModel(Request $request)
    {
        $request->validate([
            'provider' => 'required|string',
            'model' => 'required|string',
        ]);

        $synced = SyncedModel::firstOrCreate(
            ['provider' => $request->provider],
            ['models' => []]
        );

        $models = $synced->models ?? [];
        if (! in_array($request->model, $models)) {
            $models[] = $request->model;
            $synced->update(['models' => $models]);
        }

        // Also sync to JSON configuration fallback
        $path = 'config/app_config.json';
        if (Storage::disk('local')->exists($path)) {
            $existingConfig = json_decode(Storage::disk('local')->get($path), true);
            $existingConfig['synced_models'][$request->provider] = $models;
            Storage::disk('local')->put($path, json_encode($existingConfig, JSON_PRETTY_PRINT));
        }

        return response()->json(['message' => 'Model added successfully', 'models' => $models]);
    }

    public function deleteSyncedModel(Request $request)
    {
        $request->validate([
            'provider' => 'required|string',
            'model' => 'required|string',
        ]);

        $synced = SyncedModel::where('provider', $request->provider)->first();
        if ($synced) {
            $models = $synced->models ?? [];
            $models = array_values(array_filter($models, fn ($m) => $m !== $request->model));
            $synced->update(['models' => $models]);

            // Also sync to JSON configuration fallback
            $path = 'config/app_config.json';
            if (Storage::disk('local')->exists($path)) {
                $existingConfig = json_decode(Storage::disk('local')->get($path), true);
                $existingConfig['synced_models'][$request->provider] = $models;
                Storage::disk('local')->put($path, json_encode($existingConfig, JSON_PRETTY_PRINT));
            }

            return response()->json(['message' => 'Model removed successfully', 'models' => $models]);
        }

        return response()->json(['message' => 'Provider not found'], 404);
    }
}
