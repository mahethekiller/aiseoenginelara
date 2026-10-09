<?php

use App\Http\Controllers\AiPresetController;
use App\Http\Controllers\AiPresetWebController;
use App\Http\Controllers\AiUsageLogController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KeywordRankController;
use App\Http\Controllers\PromptTemplateController;
use App\Http\Controllers\RewriterController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SeoBlogController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Application Routes
Route::middleware(['auth'])->group(function () {
    // Default Root: Redirect to Unified Dashboard
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // 0. Unified Executive & Creator Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Global Client & Preset Switchers
    Route::post('/client/switch', [ClientController::class, 'switchActiveClient'])->name('client.switch');
    Route::post('/preset/switch', [AiPresetController::class, 'switchActivePreset'])->name('preset.switch');

    // 1. SEO Blog Creator (Requires generate-content)
    Route::middleware(['permission:generate-content'])->group(function () {
        Route::get('/blog-creator', [SeoBlogController::class, 'index'])->name('blog.creator');
        Route::post('/blog-creator/generate', [SeoBlogController::class, 'generate'])->name('blog.generate');
        Route::post('/blog-creator/preview-prompt', [SeoBlogController::class, 'previewPrompt'])->name('blog.preview.prompt');
        Route::get('/blog-creator/jobs/{id}/logs', [SeoBlogController::class, 'getJobLogs'])->name('blog.job.logs');
        Route::get('/blog-creator/jobs/{id}/article', [SeoBlogController::class, 'getJobArticle'])->name('blog.job.article');
        Route::post('/blog-creator/articles/{id}/publish-wp', [SeoBlogController::class, 'publishToWordPress'])->name('blog.publish.wp');
    });

    // 2. Agency Clients Management (Requires manage-clients)
    Route::middleware(['permission:manage-clients'])->group(function () {
        Route::resource('clients', ClientController::class);
        Route::post('/clients/{client}/crawl-sitemap', [ClientController::class, 'crawlSitemap'])->name('clients.crawl');
    });

    // 3. Prompt Blueprints Archetypes
    Route::resource('prompt-templates', PromptTemplateController::class);
    Route::post('/prompt-templates/{id}/duplicate', [PromptTemplateController::class, 'duplicate'])->name('prompt-templates.duplicate');
    Route::post('/prompt-templates/{id}/reset', [PromptTemplateController::class, 'reset'])->name('prompt-templates.reset');

    // 3.5. AI Presets & Tuning (Accessible to All Users)
    Route::resource('ai-presets', AiPresetWebController::class)->except(['create', 'edit', 'show']);
    Route::post('/ai-presets/{id}/activate', [AiPresetWebController::class, 'activate'])->name('ai-presets.activate');
    Route::post('/ai-presets/{id}/clone', [AiPresetWebController::class, 'clone'])->name('ai-presets.clone');

    // 4. Rewriter Studio (Requires generate-content)
    Route::middleware(['permission:generate-content'])->group(function () {
        Route::get('/rewriter', [RewriterController::class, 'index'])->name('rewriter.index');
        Route::post('/rewriter/create', [RewriterController::class, 'create'])->name('rewriter.create');
        Route::get('/rewriter/jobs/{id}/status', [RewriterController::class, 'getStatus'])->name('rewriter.status');
        Route::delete('/rewriter/jobs/{id}', [RewriterController::class, 'destroy'])->name('rewriter.destroy');
        Route::get('/rewriter/jobs/{id}/download/{format}', [RewriterController::class, 'download'])->name('rewriter.download');
    });

    // 5. Content Database / Articles Archive (Requires view-content)
    Route::middleware(['permission:view-content'])->group(function () {
        Route::resource('articles', ArticleController::class)->only(['index', 'show', 'destroy']);
        Route::get('/articles/{id}/prompt', [ArticleController::class, 'getPrompt'])->name('articles.prompt');
        Route::get('/articles/{id}/download/{format}', [ArticleController::class, 'download'])->name('articles.download');
        Route::post('/articles/{id}/publish-wp', [ArticleController::class, 'publishToWordPress'])->name('articles.publish.wp');
    });

    // 6. Settings, Multi-Provider Presets, Users & RBAC Permissions (Super Admin & Admin Only)
    Route::middleware(['role:super_admin|admin'])->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/api-keys', [SettingsController::class, 'saveApiKeys'])->name('settings.api_keys');
        Route::post('/settings/presets', [SettingsController::class, 'savePreset'])->name('settings.presets.save');
        Route::post('/settings/presets/{id}/activate', [SettingsController::class, 'activatePreset'])->name('settings.presets.activate');
        Route::delete('/settings/presets/{id}', [SettingsController::class, 'deletePreset'])->name('settings.presets.delete');
        Route::post('/settings/sync-models', [SettingsController::class, 'syncModels'])->name('settings.models.sync');
        Route::post('/settings/custom-model', [SettingsController::class, 'addCustomModel'])->name('settings.models.add');

        Route::resource('users', UserController::class);
        Route::get('/permissions', [RolePermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions/sync', [RolePermissionController::class, 'sync'])->name('permissions.sync');
        Route::post('/settings/serpapi/verify', [SettingsController::class, 'verifySerpApiKey'])->name('settings.serpapi.verify');
    });

    // 7. Keyword Rank Checker (Top 50 SERP Tracker)
    Route::prefix('rank-tracker')->name('rank-tracker.')->group(function () {
        // Tracker Core & Scanning (Requires track-ranks)
        Route::middleware(['permission:track-ranks'])->group(function () {
            Route::get('/', [KeywordRankController::class, 'index'])->name('index');
            Route::post('/check', [KeywordRankController::class, 'check'])->name('check');
            Route::post('/clear-history', [KeywordRankController::class, 'clearHistory'])->name('clear');
            Route::get('/credits', [KeywordRankController::class, 'getLiveCredits'])->name('credits');
            Route::get('/locations', [KeywordRankController::class, 'getLocations'])->name('locations');
        });

        // Shared SERP Analysis & Batch Inspection (Tracker + Database)
        Route::middleware(['permission:track-ranks|view-rank-database'])->group(function () {
            Route::get('/{id}/serp', [KeywordRankController::class, 'getSerpCompetitors'])->name('serp');
            Route::get('/batch/{batchId}', [KeywordRankController::class, 'getBatchDetails'])->name('batch.details');
        });

        // CSV Exports (Requires export-rank-data)
        Route::middleware(['permission:export-rank-data'])->group(function () {
            Route::get('/export/csv', [KeywordRankController::class, 'exportCsv'])->name('export.csv');
            Route::get('/{id}/export-csv', [KeywordRankController::class, 'exportSingleCsv'])->name('export.single.csv');
            Route::get('/batch/{batchId}/export-csv', [KeywordRankController::class, 'exportBatchCsv'])->name('export.batch.csv');
        });

        // Deletions (Requires delete-rank-data)
        Route::middleware(['permission:delete-rank-data'])->group(function () {
            Route::delete('/{id}', [KeywordRankController::class, 'destroy'])->name('destroy');
            Route::delete('/batch/{batchId}', [KeywordRankController::class, 'destroyBatch'])->name('batch.destroy');
        });
    });

    // 8. Dedicated Rank Database / Historical Search Audits Archive (Requires view-rank-database)
    Route::middleware(['permission:view-rank-database'])->group(function () {
        Route::get('/rank-database', [KeywordRankController::class, 'database'])->name('rank-tracker.database');
    });

    // 9. Cost & Usage Reports
    Route::get('/reports/token-usage', [AiUsageLogController::class, 'index'])->name('reports.usage');
});
