<?php

use App\Http\Controllers\AiPresetController;
use App\Http\Controllers\AiUsageLogController;
use App\Http\Controllers\ArticleOptionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\PromptTemplateController;
use App\Http\Controllers\RewriterController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SeoBlogController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;

// Public Auth Routes
Route::name('api.')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// Authenticated Routes
Route::middleware('auth:sanctum')->name('api.')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // Article Options for dropdowns (Read access for all auth users)
    Route::get('/article-options', [ArticleOptionController::class, 'index']);

    // Admin & Manage Presets CRUD for Article Options
    Route::middleware(['permission:manage-presets'])->group(function () {
        Route::post('/article-options', [ArticleOptionController::class, 'store']);
        Route::put('/article-options/{id}', [ArticleOptionController::class, 'update']);
        Route::delete('/article-options/{id}', [ArticleOptionController::class, 'destroy']);
    });

    // Public Models Listing (All auth users)
    Route::get('/models', [SettingsController::class, 'listModels']);

    // Admin-only Settings, API Keys, Models CRUD & User CRUD (Super Admin & Admin)
    Route::middleware(['role:super_admin|admin'])->group(function () {
        Route::get('/settings/config', [SettingsController::class, 'getConfig']);
        Route::post('/settings/config', [SettingsController::class, 'updateConfig']);
        Route::post('/settings/sync-models', [SettingsController::class, 'syncModels']);
        Route::post('/settings/models', [SettingsController::class, 'addCustomModel']);
        Route::delete('/settings/models', [SettingsController::class, 'deleteSyncedModel']);

        Route::apiResource('users', UserController::class);

        Route::get('/roles-permissions', [RolePermissionController::class, 'index']);
        Route::post('/roles-permissions/sync', [RolePermissionController::class, 'sync']);
    });

    // Prompt Templates & Blueprints (All authenticated users can view, create, edit, clone, and delete their own custom prompts)
    Route::get('/prompt-templates', [PromptTemplateController::class, 'index']);
    Route::post('/prompt-templates', [PromptTemplateController::class, 'store']);
    Route::get('/prompt-templates/{id}', [PromptTemplateController::class, 'show']);
    Route::put('/prompt-templates/{id}', [PromptTemplateController::class, 'update']);
    Route::delete('/prompt-templates/{id}', [PromptTemplateController::class, 'destroy']);
    Route::post('/prompt-templates/{id}/duplicate', [PromptTemplateController::class, 'duplicate']);
    Route::post('/prompt-templates/{id}/reset', [PromptTemplateController::class, 'reset']);

    // AI Presets Management (All auth users can manage their own presets)
    Route::apiResource('presets', AiPresetController::class);
    Route::post('/presets/{preset}/activate', [AiPresetController::class, 'activate']);

    // SEO Blog Creator (Generation requires Creator/Editor)
    Route::middleware(['permission:generate-content'])->group(function () {
        Route::post('/seo-generation/create', [SeoBlogController::class, 'create']);
        Route::post('/seo-generation/preview-prompt', [SeoBlogController::class, 'previewPrompt']);
        Route::post('/seo-generation/batch-csv', [SeoBlogController::class, 'processBatchCsv']);
        Route::post('/articles/{id}/publish-wp', [SeoBlogController::class, 'publishToWordPress']);
    });

    Route::middleware(['permission:view-content'])->group(function () {
        Route::get('/seo-generation/jobs', [SeoBlogController::class, 'listJobs']);
        Route::get('/seo-generation/jobs/{id}', [SeoBlogController::class, 'getJobStatus']);
        Route::get('/seo-generation/jobs/{id}/logs', [SeoBlogController::class, 'getJobLogs']);
        Route::get('/articles', [SeoBlogController::class, 'listArticles']);
        Route::get('/articles/{id}', [SeoBlogController::class, 'getArticle']);
        Route::delete('/articles/{id}', [SeoBlogController::class, 'deleteArticle']);
        Route::get('/articles/{id}/download-html', [SeoBlogController::class, 'downloadHtml']);
        Route::get('/articles/{id}/download-docx', [SeoBlogController::class, 'downloadDocx']);

        // Web Content Rewriter
        Route::post('/rewriter/create', [RewriterController::class, 'create']);
        Route::get('/rewriter/jobs', [RewriterController::class, 'listJobs']);
        Route::get('/rewriter/jobs/{id}', [RewriterController::class, 'getJobStatus']);
        Route::delete('/rewriter/jobs/{id}', [RewriterController::class, 'deleteJob']);
        Route::get('/rewriter/jobs/{id}/download-html', [RewriterController::class, 'downloadHtml']);
        Route::get('/rewriter/jobs/{id}/download-docx', [RewriterController::class, 'downloadDocx']);
        Route::post('/rewriter/jobs/{id}/retry', [RewriterController::class, 'retry']);

        // Multi-Client Management Routes
        Route::post('/clients/{client}/crawl-sitemap', [ClientController::class, 'crawlSitemap']);
        Route::apiResource('clients', ClientController::class);

        // 11-Step AI SEO Engine Pipeline Routes
        Route::prefix('pipeline')->group(function () {
            Route::get('/history', [PipelineController::class, 'getStepHistory']);
            Route::post('/step-1/research', [PipelineController::class, 'industryResearch']);
            Route::post('/step-2/discover-topics', [PipelineController::class, 'discoverTopics']);
            Route::post('/step-3/validate-trends', [PipelineController::class, 'validateTrends']);
            Route::post('/step-4/cluster-keywords', [PipelineController::class, 'clusterKeywords']);
            Route::post('/step-5/generate-brief', [PipelineController::class, 'generateBrief']);
            Route::post('/step-6/produce-content', [PipelineController::class, 'produceContent']);
            Route::post('/step-6/publish-wp', [PipelineController::class, 'publishToWp']);
            Route::post('/step-7/publish-wp', [PipelineController::class, 'publishToWp']);
            Route::get('/step-8/performance/{id}', [PipelineController::class, 'getPerformance']);
            Route::post('/step-9/diagnose/{id}', [PipelineController::class, 'diagnosePerformance']);
            Route::get('/step-10/learnings', [PipelineController::class, 'getLearnings']);
            Route::post('/step-11/reinject-loop', [PipelineController::class, 'reinjectLoop']);
        });

        // Dual-Currency Token & Cost Audit Reports (USD $ / INR Rs. ₹)
        Route::get('/reports/token-usage', [AiUsageLogController::class, 'index']);
    });
});
