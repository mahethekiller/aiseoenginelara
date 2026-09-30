<?php

use Database\Seeders\AiPromptTemplateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_prompt_templates')) {
            $seeder = new AiPromptTemplateSeeder;
            $seeder->run();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive - no rollback needed for seeder updates
    }
};
