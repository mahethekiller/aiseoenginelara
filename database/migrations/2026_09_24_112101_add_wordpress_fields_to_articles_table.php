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
        Schema::table('articles', function (Blueprint $table) {
            if (! Schema::hasColumn('articles', 'wordpress_post_id')) {
                $table->string('wordpress_post_id')->nullable()->after('completion_tokens');
            }
            if (! Schema::hasColumn('articles', 'wordpress_post_url')) {
                $table->string('wordpress_post_url')->nullable()->after('wordpress_post_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'wordpress_post_url')) {
                $table->dropColumn('wordpress_post_url');
            }
            if (Schema::hasColumn('articles', 'wordpress_post_id')) {
                $table->dropColumn('wordpress_post_id');
            }
        });
    }
};
