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
        if (! Schema::hasTable('rank_check_batches')) {
            Schema::create('rank_check_batches', function (Blueprint $table) {
                $table->id();
                $table->string('batch_id', 64)->unique()->index();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->string('filename')->nullable();
                $table->string('target_domain')->index();
                $table->text('target_url')->nullable();
                $table->string('match_type', 20)->default('domain');
                $table->string('location')->nullable();
                $table->string('country', 10)->default('in');
                $table->string('language', 10)->default('en');
                $table->string('device', 15)->default('desktop');
                $table->unsignedSmallInteger('depth')->default(50);
                $table->unsignedInteger('total_keywords')->default(0);
                $table->unsignedInteger('completed_keywords')->default(0);
                $table->unsignedInteger('failed_keywords')->default(0);
                $table->unsignedInteger('top_3_count')->default(0);
                $table->unsignedInteger('top_10_count')->default(0);
                $table->unsignedInteger('page_1_count')->default(0);
                $table->unsignedInteger('unranked_count')->default(0);
                $table->string('status', 25)->default('pending')->index(); // pending, processing, completed, failed, cancelled
                $table->json('extracted_keywords')->nullable(); // Keyword list array for queue processor
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rank_check_batches');
    }
};
