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
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'sitemap_url')) {
                $table->string('sitemap_url')->nullable()->after('cta_default');
            }
            if (! Schema::hasColumn('clients', 'sitemap_cache')) {
                $table->json('sitemap_cache')->nullable()->after('sitemap_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'sitemap_url')) {
                $table->dropColumn('sitemap_url');
            }
            if (Schema::hasColumn('clients', 'sitemap_cache')) {
                $table->dropColumn('sitemap_cache');
            }
        });
    }
};
