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
        Schema::table('publishing_records', function (Blueprint $table) {
            if (! Schema::hasColumn('publishing_records', 'body_html')) {
                $table->longText('body_html')->nullable()->after('primary_keyword');
            }
            if (! Schema::hasColumn('publishing_records', 'word_count')) {
                $table->unsignedInteger('word_count')->nullable()->after('body_html');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishing_records', function (Blueprint $table) {
            if (Schema::hasColumn('publishing_records', 'body_html')) {
                $table->dropColumn('body_html');
            }
            if (Schema::hasColumn('publishing_records', 'word_count')) {
                $table->dropColumn('word_count');
            }
        });
    }
};
