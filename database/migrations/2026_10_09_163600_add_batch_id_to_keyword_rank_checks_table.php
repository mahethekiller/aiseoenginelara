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
        Schema::table('keyword_rank_checks', function (Blueprint $table) {
            if (! Schema::hasColumn('keyword_rank_checks', 'batch_id')) {
                $table->string('batch_id', 50)->nullable()->index()->after('client_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('keyword_rank_checks', function (Blueprint $table) {
            if (Schema::hasColumn('keyword_rank_checks', 'batch_id')) {
                $table->dropColumn('batch_id');
            }
        });
    }
};
