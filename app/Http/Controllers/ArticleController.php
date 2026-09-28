<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Client;
use App\Services\ArticleFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleFormatter $formatter
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasAnyRole(['admin', 'super_admin']);

        $query = Article::with(['user', 'generationJob'])->latest();

        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('meta_description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('min_score')) {
            $query->where('seo_score', '>=', (int) $request->input('min_score'));
        }

        $articles = $query->paginate(15)->withQueryString();

        // Telemetry metrics scoped to user permissions
        $metricsQuery = $isAdmin
            ? ($request->filled('user_id') ? Article::where('user_id', $request->input('user_id')) : Article::query())
            : Article::where('user_id', $user->id);

        $metrics = [
            'total_articles' => (clone $metricsQuery)->count(),
            'avg_seo_score' => round((float) (clone $metricsQuery)->avg('seo_score'), 1),
            'avg_flesch_score' => round((float) (clone $metricsQuery)->avg('flesch_reading_ease'), 1),
            'total_words' => (int) (clone $metricsQuery)->sum('word_count'),
            'total_published' => (clone $metricsQuery)->whereNotNull('wordpress_post_id')->count(),
        ];

        $users = $isAdmin ? \App\Models\User::orderBy('name')->get(['id', 'name', 'email']) : collect();

        return view('pages.articles.index', compact('articles', 'metrics', 'users', 'isAdmin'));
    }

    public function show(Request $request, int|string $id)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasAnyRole(['admin', 'super_admin']);

        $article = Article::with(['user', 'generationJob'])->findOrFail($id);

        if (! $isAdmin && $article->user_id !== $user->id) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Unauthorized access to this article.'], 403);
            }
            abort(403, 'Unauthorized access to this article.');
        }

        if ($request->expectsJson() || $request->ajax() || ! view()->exists('pages.articles.show')) {
            return response()->json([
                'success' => true,
                'article' => $article,
            ]);
        }

        return view('pages.articles.show', compact('article'));
    }

    public function destroy(Request $request, int|string $id)
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['admin', 'super_admin'])) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Unauthorized. Only administrators can delete articles.'], 403);
            }
            abort(403, 'Unauthorized. Only administrators can delete articles.');
        }

        $article = Article::findOrFail($id);
        $article->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Article deleted successfully.']);
        }

        return redirect()->route('articles.index')->with('success', 'Article deleted successfully.');
    }

    public function download(Request $request, int|string $id, string $format)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasAnyRole(['admin', 'super_admin']);

        $article = Article::findOrFail($id);

        if (! $isAdmin && $article->user_id !== $user?->id) {
            abort(403, 'Unauthorized access to download this article.');
        }

        if (strtolower($format) === 'docx' || strtolower($format) === 'doc') {
            $content = $this->formatter->buildWordDoc($article);

            return response($content, 200, [
                'Content-Type' => 'application/vnd.ms-word',
                'Content-Disposition' => "attachment; filename=\"{$article->slug}.doc\"",
            ]);
        }

        $html = $this->formatter->buildFullHtml($article);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$article->slug}.html\"",
        ]);
    }

    public function publishToWordPress(Request $request, int|string $id)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasAnyRole(['admin', 'super_admin']);

        $article = Article::findOrFail($id);

        if (! $isAdmin && $article->user_id !== $user?->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to publish this article.',
            ], 403);
        }

        $client = $user?->active_client_id ? Client::find($user->active_client_id) : null;

        if (! $client || empty($client->wordpress_url)) {
            return response()->json([
                'success' => false,
                'message' => 'No active client selected or client does not have WordPress credentials configured.',
            ], 422);
        }

        if (empty($client->wordpress_username) || empty($client->wordpress_app_password)) {
            return response()->json([
                'success' => false,
                'message' => 'WordPress username or application password missing in client settings.',
            ], 422);
        }

        try {
            $endpoint = rtrim($client->wordpress_url, '/').'/wp-json/wp/v2/posts';
            $response = Http::withBasicAuth($client->wordpress_username, $client->wordpress_app_password)
                ->timeout(30)
                ->post($endpoint, [
                    'title' => $article->title,
                    'content' => $article->html_content,
                    'status' => 'draft',
                    'slug' => $article->slug,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $article->update([
                    'wordpress_post_id' => $data['id'] ?? null,
                    'wordpress_post_url' => $data['link'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Article published to WordPress as draft successfully!',
                    'post_url' => $data['link'] ?? null,
                    'post_id' => $data['id'] ?? null,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'WordPress API returned error: '.$response->body(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to WordPress REST API: '.$e->getMessage(),
            ], 500);
        }
    }
}
