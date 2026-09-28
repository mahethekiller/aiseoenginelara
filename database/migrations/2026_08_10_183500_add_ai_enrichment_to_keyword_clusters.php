<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keyword_clusters', function (Blueprint $table) {
            if (! Schema::hasColumn('keyword_clusters', 'is_ai_enriched')) {
                $table->boolean('is_ai_enriched')->default(false)->after('informational_keywords');
            }
            if (! Schema::hasColumn('keyword_clusters', 'llm_provider')) {
                $table->string('llm_provider')->nullable()->after('is_ai_enriched');
            }
            if (! Schema::hasColumn('keyword_clusters', 'ai_placement_map')) {
                $table->json('ai_placement_map')->nullable()->after('llm_provider');
            }
            if (! Schema::hasColumn('keyword_clusters', 'standard_metrics')) {
                $table->json('standard_metrics')->nullable()->after('ai_placement_map');
            }
        });
    }

    public function down(): void
    {
        Schema::table('keyword_clusters', function (Blueprint $table) {
            $table->dropColumn(['is_ai_enriched', 'llm_provider', 'ai_placement_map', 'standard_metrics']);
        });
    }
};
