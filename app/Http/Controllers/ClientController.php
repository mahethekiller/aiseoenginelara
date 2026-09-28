<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\SitemapCrawlerService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Display a listing of agency clients for the current user/admin.
     */
    public function index(Request $request)
    {
        $clients = Client::orderBy('name')->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'clients' => $clients,
            ]);
        }

        $metrics = [
            'total_clients' => $clients->count(),
            'active_clients' => $clients->where('is_active', true)->count(),
            'with_sitemap' => $clients->whereNotNull('sitemap_cache')->count(),
            'with_wordpress' => $clients->whereNotNull('wordpress_url')->count(),
        ];

        return view('pages.clients.index', compact('clients', 'metrics'));
    }

    /**
     * Store a newly created agency client profile.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website_url' => 'required|url|max:255',
            'industry' => 'required|string|max:255',
            'brand_tone' => 'nullable|string|max:255',
            'target_audience' => 'nullable|string',
            'cta_default' => 'nullable|string',
            'competitor_urls' => 'nullable|array',
            'approved_reference_domains' => 'nullable|array',
            'wordpress_url' => 'nullable|url|max:255',
            'wordpress_username' => 'nullable|string|max:255',
            'wordpress_app_password' => 'nullable|string|max:255',
            'sitemap_url' => 'nullable|url|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        $client = Client::create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Client profile created successfully.',
                'client' => $client,
            ], 201);
        }

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    /**
     * Display the specified client profile.
     */
    public function show(Client $client)
    {
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'client' => $client,
            ]);
        }

        return view('pages.clients.show', compact('client'));
    }

    /**
     * Update the specified client profile.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'website_url' => 'sometimes|required|url|max:255',
            'industry' => 'sometimes|required|string|max:255',
            'brand_tone' => 'nullable|string|max:255',
            'target_audience' => 'nullable|string',
            'cta_default' => 'nullable|string',
            'competitor_urls' => 'nullable|array',
            'approved_reference_domains' => 'nullable|array',
            'wordpress_url' => 'nullable|url|max:255',
            'wordpress_username' => 'nullable|string|max:255',
            'wordpress_app_password' => 'nullable|string|max:255',
            'sitemap_url' => 'nullable|url|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $client->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Client profile updated successfully.',
                'client' => $client->fresh(),
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    /**
     * Crawl and sync client XML sitemap.
     */
    public function crawlSitemap(Request $request, Client $client)
    {
        $crawler = app(SitemapCrawlerService::class);
        $urls = $crawler->crawlAndCacheSitemap($client);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Sitemap XML crawled and cached successfully ('.count($urls).' URLs found).',
                'sitemap_url' => $client->sitemap_url,
                'total_urls' => count($urls),
                'urls' => $urls,
                'client' => $client->fresh(),
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Sitemap crawled: '.count($urls).' links cached.');
    }

    /**
     * Switch active agency client for the user.
     */
    public function switchActiveClient(Request $request)
    {
        $clientId = $request->input('client_id');
        $user = $request->user();

        if (! empty($clientId) && $clientId !== 'none') {
            $client = Client::findOrFail($clientId);
            $user->update(['active_client_id' => $client->id]);
            $message = "Switched to client: {$client->name}";
        } else {
            $user->update(['active_client_id' => null]);
            $message = 'Switched to Generic Mode (No client brand voice).';
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'active_client_id' => $user->active_client_id,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Remove the specified client profile.
     */
    public function destroy(Client $client)
    {
        $client->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Client profile deleted successfully.',
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }
}
