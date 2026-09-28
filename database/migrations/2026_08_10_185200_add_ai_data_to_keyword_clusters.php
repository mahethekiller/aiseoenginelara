<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keyword_clusters', function (Blueprint $table) {
            if (! Schema::hasColumn('keyword_clusters', 'ai_data')) {
                $table->json('ai_data')->nullable()->after('ai_placement_map');
            }
        });
    }

    public function down(): void
    {
        Schema::table('keyword_clusters', function (Blueprint $table) {
            $table->dropColumn(['ai_data']);
        });
    }
};
