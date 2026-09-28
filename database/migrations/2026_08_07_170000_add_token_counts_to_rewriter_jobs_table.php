<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rewriter_jobs')) {
            Schema::table('rewriter_jobs', function (Blueprint $table) {
                if (! Schema::hasColumn('rewriter_jobs', 'prompt_tokens')) {
                    $table->integer('prompt_tokens')->nullable()->after('output_docx_path');
                }
                if (! Schema::hasColumn('rewriter_jobs', 'completion_tokens')) {
                    $table->integer('completion_tokens')->nullable()->after('prompt_tokens');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rewriter_jobs')) {
            Schema::table('rewriter_jobs', function (Blueprint $table) {
                if (Schema::hasColumn('rewriter_jobs', 'prompt_tokens')) {
                    $table->dropColumn('prompt_tokens');
                }
                if (Schema::hasColumn('rewriter_jobs', 'completion_tokens')) {
                    $table->dropColumn('completion_tokens');
                }
            });
        }
    }
};
