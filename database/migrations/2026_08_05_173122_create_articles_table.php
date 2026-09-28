<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('seo_generation_job_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->string('meta_title');
            $table->text('meta_description');
            $table->string('slug');
            $table->longText('html_content');
            $table->longText('markdown_content');
            $table->json('schema_jsonld')->nullable();
            $table->integer('word_count')->default(0);
            $table->integer('seo_score')->default(0);
            $table->float('flesch_reading_ease')->default(0);
            $table->json('keyword_density_metrics')->nullable();
            $table->string('docx_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
