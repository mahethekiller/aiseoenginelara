<?php

namespace App\Http\Controllers;

use App\Jobs\RewriteUrlJob;
use App\Models\RewriterJob;
use App\Services\LayoutPreservingRewriter;
use App\Services\MultiProviderLlmClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RewriterController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $jobs = $user ? $user->rewriterJobs()->latest()->paginate(10) : RewriterJob::latest()->paginate(10);

        $metrics = [
            'total_jobs' => $user ? $user->rewriterJobs()->count() : RewriterJob::count(),
            'completed_jobs' => $user ? $user->rewriterJobs()->where('status', 'completed')->count() : RewriterJob::where('status', 'completed')->count(),
            'failed_jobs' => $user ? $user->rewriterJobs()->where('status', 'failed')->count() : RewriterJob::where('status', 'failed')->count(),
        ];

        return view('pages.rewriter.index', compact('jobs', 'metrics'));
    }

    public function getStatus(Request $request, $id)
    {
        return $this->getJobStatus($request, $id);
    }

    public function download(Request $request, $id, string $format)
    {
        if (strtolower($format) === 'docx' || strtolower($format) === 'doc') {
            return $this->downloadDocx($request, $id);
        }

        return $this->downloadHtml($request, $id);
    }

    public function destroy(Request $request, $id)
    {
        return $this->deleteJob($request, $id);
    }

    public function create(Request $request)
    {
        $request->validate([
            'source_url' => 'required|string',
            'rewriter_mode' => 'required|in:layout-preserving,semantic-clean',
            'custom_instructions' => 'nullable|string',
        ]);

        $urls = preg_split('/[\r\n,]+/', $request->source_url, -1, PREG_SPLIT_NO_EMPTY);
        $urls = array_map('trim', $urls);
        $urls = array_filter($urls, function ($u) {
            return filter_var($u, FILTER_VALIDATE_URL);
        });

        if (empty($urls)) {
            return response()->json([
                'message' => 'Please provide at least one valid URL.',
                'errors' => ['source_url' => ['No valid URLs found.']],
            ], 422);
        }

        $createdJobs = [];
        foreach ($urls as $url) {
            $job = $request->user()->rewriterJobs()->create([
                'rewriter_mode' => $request->rewriter_mode,
                'status' => 'pending',
                'source_url' => $url,
                'custom_instructions' => $request->custom_instructions,
            ]);

            RewriteUrlJob::dispatch($job);
            $createdJobs[] = $job;
        }

        return response()->json([
            'message' => count($createdJobs).' rewriter jobs dispatched successfully.',
            'jobs' => $createdJobs,
            'status' => 'pending',
        ], 202);
    }

    public function listJobs(Request $request)
    {
        $isAdmin = $request->user()->roles()->where('name', 'admin')->exists();
        $query = RewriterJob::query();

        if ($isAdmin) {
            $query->with('user');
            if ($request->has('user_id') && ! empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }
        } else {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function getJobStatus(Request $request, $id)
    {
        $isAdmin = $request->user()->roles()->where('name', 'admin')->exists();
        $query = RewriterJob::query();

        if (! $isAdmin) {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->findOrFail($id));
    }

    public function deleteJob(Request $request, $id)
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['admin', 'super_admin'])) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Unauthorized. Only administrators can delete rewriter tasks.'], 403);
            }
            abort(403, 'Unauthorized. Only administrators can delete rewriter tasks.');
        }

        $job = RewriterJob::findOrFail($id);
        $job->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => 'Rewriter job deleted successfully.']);
        }

        return redirect()->route('rewriter.index')->with('success', 'Rewriter task deleted successfully.');
    }

    protected function processJobInController($job)
    {
        $job->update(['status' => 'processing']);

        try {
            $user = $job->user;
            $preset = $user->presets()->where('is_active', true)->orderBy('updated_at', 'desc')->first();

            $provider = 'gemini';
            $model = 'gemini-3.5-flash';
            $presetInstructions = '';

            if ($preset) {
                $provider = $preset->provider;
                $model = $preset->model;
                $presetInstructions = $preset->custom_instructions ?? '';
            } else {
                $path = 'config/app_config.json';
                if (Storage::disk('local')->exists($path)) {
                    $config = json_decode(Storage::disk('local')->get($path), true);
                    $provider = $config['current_provider'] ?? $provider;
                    $model = $config['current_model'] ?? $model;
                }
            }

            $client = new MultiProviderLlmClient($provider, $model);
            $rewriter = new LayoutPreservingRewriter($client);

            $instructions = trim($presetInstructions."\n".($job->custom_instructions ?? ''));

            $rewrittenHtml = $rewriter->rewriteUrl($job->source_url, $instructions, $job->rewriter_mode ?? 'layout-preserving');

            $job->update([
                'status' => 'completed',
                'original_html' => $rewriter->getCleanOriginalHtml(),
                'rewritten_html' => $rewrittenHtml,
                'prompt_tokens' => $rewriter->getPromptTokens(),
                'completion_tokens' => $rewriter->getCompletionTokens(),
            ]);
        } catch (\Exception $e) {
            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function downloadHtml(Request $request, $id)
    {
        $job = RewriterJob::findOrFail($id);
        $isAdmin = $request->user()->roles()->where('name', 'admin')->exists();

        if (! $isAdmin && $job->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized Access.'], 403);
        }

        $host = parse_url($job->source_url, PHP_URL_HOST) ?: 'rewritten-site';
        $filename = Str::slug($host).'-'.$job->id.'.html';

        return response($job->rewritten_html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function downloadDocx(Request $request, $id)
    {
        $job = RewriterJob::findOrFail($id);
        $isAdmin = $request->user()->roles()->where('name', 'admin')->exists();

        if (! $isAdmin && $job->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized Access.'], 403);
        }

        $host = parse_url($job->source_url, PHP_URL_HOST) ?: 'rewritten-site';
        $filename = Str::slug($host).'-'.$job->id.'.doc';

        $wordHtml = <<<HTML
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="utf-8">
    <title>Rewritten - {$host}</title>
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #1e293b;
            margin: 40px;
        }
    </style>
</head>
<body>
{$job->rewritten_html}
</body>
</html>
HTML;

        return response($wordHtml, 200, [
            'Content-Type' => 'application/vnd.ms-word',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function retry(Request $request, $id)
    {
        $job = RewriterJob::findOrFail($id);
        $isAdmin = $request->user()->roles()->where('name', 'admin')->exists();

        if (! $isAdmin && $job->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized Access.'], 403);
        }

        $job->update([
            'status' => 'pending',
            'error_message' => null,
            'original_html' => null,
            'rewritten_html' => null,
        ]);

        RewriteUrlJob::dispatch($job);

        return response()->json([
            'message' => 'Job successfully retried.',
            'job' => $job,
        ]);
    }
}
