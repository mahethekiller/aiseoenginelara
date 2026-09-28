<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('articles')) {
            Schema::table('articles', function (Blueprint $table) {
                if (! Schema::hasColumn('articles', 'prompt_tokens')) {
                    $table->integer('prompt_tokens')->nullable()->after('flesch_reading_ease');
                }
                if (! Schema::hasColumn('articles', 'completion_tokens')) {
                    $table->integer('completion_tokens')->nullable()->after('prompt_tokens');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('articles')) {
            Schema::table('articles', function (Blueprint $table) {
                if (Schema::hasColumn('articles', 'prompt_tokens')) {
                    $table->dropColumn('prompt_tokens');
                }
                if (Schema::hasColumn('articles', 'completion_tokens')) {
                    $table->dropColumn('completion_tokens');
                }
            });
        }
    }
};
