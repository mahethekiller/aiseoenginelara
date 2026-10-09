<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('keyword_rank_checks')) {
            Schema::create('keyword_rank_checks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->string('keyword')->index();
                $table->string('target_domain')->index();
                $table->string('target_url')->nullable();
                $table->string('match_type', 20)->default('domain'); // 'domain' or 'exact_url'
                $table->string('country', 10)->default('us'); // gl code
                $table->string('location')->nullable(); // Specific city/region
                $table->string('language', 10)->default('en'); // hl code
                $table->string('device', 15)->default('desktop'); // 'desktop' or 'mobile'
                $table->unsignedSmallInteger('position')->nullable(); // null if > 50 (Unranked)
                $table->boolean('is_ranked')->default(false)->index();
                $table->text('ranking_url')->nullable();
                $table->text('ranking_title')->nullable();
                $table->text('ranking_snippet')->nullable();
                $table->unsignedSmallInteger('previous_position')->nullable();
                $table->smallInteger('rank_change')->nullable(); // e.g. +3, -2, 0
                $table->json('serp_features')->nullable(); // e.g. ['featured_snippet', 'paa', 'sitelinks']
                $table->json('top_competitors')->nullable(); // Top 10 organic results snapshot
                $table->string('serpapi_search_url')->nullable();
                $table->timestamp('checked_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_rank_checks');
    }
};
