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
        // 1. Topic Discoveries (Steps 1, 2, 3)
        Schema::create('topic_discoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('topic_name');
            $table->string('target_url')->nullable();
            $table->json('organic_keywords')->nullable();
            $table->json('top_pages')->nullable();
            $table->unsignedInteger('traffic_estimate')->default(0);
            $table->json('ranking_keywords')->nullable();
            $table->json('featured_snippets')->nullable();
            $table->unsignedInteger('keyword_difficulty')->default(0);
            $table->string('search_intent')->default('Informational');
            $table->enum('classification', ['evergreen', 'seasonal', 'trending', 'news'])->default('evergreen');
            $table->unsignedInteger('priority_score')->default(50);
            $table->enum('status', ['discovered', 'selected', 'briefed', 'archived'])->default('discovered');
            $table->timestamps();
        });

        // 2. Keyword Clusters (Step 4)
        Schema::create('keyword_clusters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_discovery_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('primary_keyword');
            $table->unsignedInteger('primary_sv')->default(0);
            $table->json('secondary_keywords')->nullable();
            $table->json('long_tail_keywords')->nullable();
            $table->json('question_keywords')->nullable();
            $table->json('lsi_keywords')->nullable();
            $table->json('commercial_keywords')->nullable();
            $table->json('transactional_keywords')->nullable();
            $table->json('informational_keywords')->nullable();
            $table->timestamps();
        });

        // 3. Content Briefs (Step 5)
        Schema::create('content_briefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('keyword_cluster_id')->nullable()->constrained()->onDelete('set null');
            $table->string('brand_name');
            $table->string('content_type')->default('onpage_blog');
            $table->string('working_title');
            $table->string('primary_keyword');
            $table->text('target_audience')->nullable();
            $table->string('search_intent')->default('Informational');
            $table->unsignedInteger('suggested_word_count')->default(1500);
            $table->string('tone_and_language')->default('Conversational');
            $table->text('cta_details')->nullable();
            $table->string('seo_title');
            $table->string('meta_title');
            $table->text('meta_description');
            $table->string('url_slug');
            $table->string('canonical_url')->nullable();
            $table->json('wireframe_structure')->nullable();
            $table->json('brand_heading_rules')->nullable();
            $table->json('keyword_placement_map')->nullable();
            $table->longText('intelligent_prompt')->nullable();
            $table->timestamps();
        });

        // 4. Publishing Records (Steps 6 & 7)
        Schema::create('publishing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('content_brief_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->string('published_url')->nullable();
            $table->timestamp('publish_date')->nullable();
            $table->string('author')->nullable();
            $table->string('content_version')->default('1.0');
            $table->string('primary_keyword')->nullable();
            $table->enum('status', ['draft', 'review', 'published', 'updated', 'archived'])->default('draft');
            $table->string('wordpress_post_id')->nullable();
            $table->timestamps();
        });

        // 5. Performance Metrics GSC & GA4 (Step 8)
        Schema::create('performance_metrics_gsc_ga4', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('publishing_record_id')->constrained()->onDelete('cascade');
            $table->date('metric_date');
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->decimal('ctr', 5, 2)->default(0.00);
            $table->decimal('avg_position', 5, 2)->default(0.00);
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('users')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0.00);
            $table->decimal('bounce_rate', 5, 2)->default(0.00);
            $table->unsignedInteger('conversions')->default(0);
            $table->decimal('revenue', 10, 2)->default(0.00);
            $table->timestamps();
        });

        // 6. AI Performance Diagnostics (Step 9)
        Schema::create('ai_performance_diagnostics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('publishing_record_id')->constrained()->onDelete('cascade');
            $table->enum('outcome', ['outperformed', 'underperformed', 'neutral'])->default('neutral');
            $table->json('primary_reasons')->nullable();
            $table->text('root_cause_analysis')->nullable();
            $table->json('actionable_remedies')->nullable();
            $table->timestamps();
        });

        // 7. Learning Repository (Step 10 & 11)
        Schema::create('learning_repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('learning_code');
            $table->string('title');
            $table->text('insight_description');
            $table->string('impact_metric')->nullable();
            $table->json('auto_apply_rule')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 8. Optimization Recommendations (Step 10 & 11 Scanner)
        Schema::create('optimization_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('publishing_record_id')->constrained()->onDelete('cascade');
            $table->string('issue_type');
            $table->string('suggested_action');
            $table->text('action_details')->nullable();
            $table->enum('status', ['pending', 'applied', 'dismissed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('optimization_recommendations');
        Schema::dropIfExists('learning_repositories');
        Schema::dropIfExists('ai_performance_diagnostics');
        Schema::dropIfExists('performance_metrics_gsc_ga4');
        Schema::dropIfExists('publishing_records');
        Schema::dropIfExists('content_briefs');
        Schema::dropIfExists('keyword_clusters');
        Schema::dropIfExists('topic_discoveries');
    }
};
