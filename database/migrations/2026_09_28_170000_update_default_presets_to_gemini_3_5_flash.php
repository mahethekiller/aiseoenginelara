<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_presets')) {
            DB::table('ai_presets')
                ->where('model', 'gemini-2.0-flash')
                ->update(['model' => 'gemini-3.5-flash']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ai_presets')) {
            DB::table('ai_presets')
                ->where('model', 'gemini-3.5-flash')
                ->update(['model' => 'gemini-2.0-flash']);
        }
    }
};
