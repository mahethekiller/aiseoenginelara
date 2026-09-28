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
        Schema::table('learning_repositories', function (Blueprint $table) {
            if (! Schema::hasColumn('learning_repositories', 'applicable_content_types')) {
                $table->json('applicable_content_types')->nullable()->after('auto_apply_rule');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_repositories', function (Blueprint $table) {
            if (Schema::hasColumn('learning_repositories', 'applicable_content_types')) {
                $table->dropColumn('applicable_content_types');
            }
        });
    }
};
