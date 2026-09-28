<?php

use App\Http\Controllers\AiUsageLogController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
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

    // Global Client Switcher
    Route::post('/client/switch', [ClientController::class, 'switchActiveClient'])->name('client.switch');

    // 1. SEO Blog Creator (Core Primary Workspace)
    Route::get('/blog-creator', [SeoBlogController::class, 'index'])->name('blog.creator');
    Route::post('/blog-creator/generate', [SeoBlogController::class, 'generate'])->name('blog.generate');
    Route::post('/blog-creator/preview-prompt', [SeoBlogController::class, 'previewPrompt'])->name('blog.preview.prompt');
    Route::get('/blog-creator/jobs/{id}/logs', [SeoBlogController::class, 'getJobLogs'])->name('blog.job.logs');
    Route::get('/blog-creator/jobs/{id}/article', [SeoBlogController::class, 'getJobArticle'])->name('blog.job.article');
    Route::post('/blog-creator/articles/{id}/publish-wp', [SeoBlogController::class, 'publishToWordPress'])->name('blog.publish.wp');

    // 2. Agency Clients Management
    Route::resource('clients', ClientController::class);
    Route::post('/clients/{client}/crawl-sitemap', [ClientController::class, 'crawlSitemap'])->name('clients.crawl');

    // 3. Prompt Blueprints Archetypes
    Route::resource('prompt-templates', PromptTemplateController::class);
    Route::post('/prompt-templates/{id}/duplicate', [PromptTemplateController::class, 'duplicate'])->name('prompt-templates.duplicate');
    Route::post('/prompt-templates/{id}/reset', [PromptTemplateController::class, 'reset'])->name('prompt-templates.reset');

    // 4. Rewriter Studio
    Route::get('/rewriter', [RewriterController::class, 'index'])->name('rewriter.index');
    Route::post('/rewriter/create', [RewriterController::class, 'create'])->name('rewriter.create');
    Route::get('/rewriter/jobs/{id}/status', [RewriterController::class, 'getStatus'])->name('rewriter.status');
    Route::delete('/rewriter/jobs/{id}', [RewriterController::class, 'destroy'])->name('rewriter.destroy');
    Route::get('/rewriter/jobs/{id}/download/{format}', [RewriterController::class, 'download'])->name('rewriter.download');

    // 5. Content Database / Articles Archive
    Route::resource('articles', ArticleController::class)->only(['index', 'show', 'destroy']);
    Route::get('/articles/{id}/download/{format}', [ArticleController::class, 'download'])->name('articles.download');
    Route::post('/articles/{id}/publish-wp', [ArticleController::class, 'publishToWordPress'])->name('articles.publish.wp');

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
    });

    // 8. Cost & Usage Reports
    Route::get('/reports/token-usage', [AiUsageLogController::class, 'index'])->name('reports.usage');
});
