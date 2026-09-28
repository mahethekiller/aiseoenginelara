<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewriter_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('rewriter_mode'); // layout-preserving, semantic-clean
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('source_url');
            $table->longText('original_html')->nullable();
            $table->longText('rewritten_html')->nullable();
            $table->text('custom_instructions')->nullable();
            $table->string('output_docx_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewriter_jobs');
    }
};
