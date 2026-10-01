<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Article;
use App\Models\AiPromptTemplate;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (! Schema::hasColumn('articles', 'prompt_template_id')) {
                $table->foreignId('prompt_template_id')->nullable()->after('seo_generation_job_id')->constrained('ai_prompt_templates')->nullOnDelete();
            }
            if (! Schema::hasColumn('articles', 'prompt_template_name')) {
                $table->string('prompt_template_name')->nullable()->after('prompt_template_id');
            }
        });

        // Safe non-destructive backfill for existing historical articles
        try {
            $templates = AiPromptTemplate::all()->keyBy('id');
            $articles = Article::with('generationJob')->get();
            foreach ($articles as $art) {
                $tmplId = $art->generationJob?->parameters['prompt_template_id'] ?? null;
                $tmpl = $tmplId ? ($templates[$tmplId] ?? null) : null;
                $art->update([
                    'prompt_template_id' => $tmpl?->id,
                    'prompt_template_name' => $tmpl ? $tmpl->archetype_name : 'Comprehensive Master Prompt',
                ]);
            }
        } catch (\Throwable $e) {
            // Silently continue if tables are still syncing
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'prompt_template_name')) {
                $table->dropColumn('prompt_template_name');
            }
            if (Schema::hasColumn('articles', 'prompt_template_id')) {
                $table->dropConstrainedForeignId('prompt_template_id');
            }
        });
    }
};
