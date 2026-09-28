<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_generation_jobs')) {
            Schema::table('seo_generation_jobs', function (Blueprint $table) {
                if (! Schema::hasColumn('seo_generation_jobs', 'logs')) {
                    $table->longText('logs')->nullable()->after('completed_items');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('seo_generation_jobs')) {
            Schema::table('seo_generation_jobs', function (Blueprint $table) {
                if (Schema::hasColumn('seo_generation_jobs', 'logs')) {
                    $table->dropColumn('logs');
                }
            });
        }
    }
};
