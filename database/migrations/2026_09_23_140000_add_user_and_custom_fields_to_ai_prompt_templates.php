<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_prompt_templates', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->after('user_id')->constrained('clients')->nullOnDelete();
            $table->boolean('is_system')->default(false)->after('is_active');
        });

        // Mark existing seeded templates as system templates
        DB::table('ai_prompt_templates')->update(['is_system' => true]);

        // Drop unique constraint on archetype_key so multiple custom templates can share or omit keys
        Schema::table('ai_prompt_templates', function (Blueprint $table) {
            $table->dropUnique(['archetype_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_prompt_templates', function (Blueprint $table) {
            $table->unique('archetype_key');
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn('is_system');
        });
    }
};
