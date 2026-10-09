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
        if (! Schema::hasTable('serp_api_caches')) {
            Schema::create('serp_api_caches', function (Blueprint $table) {
                $table->id();
                $table->string('cache_key', 64)->unique()->index();
                $table->string('engine', 32)->default('google')->index();
                $table->text('query_params');
                $table->longText('response_data');
                $table->unsignedInteger('api_units_spent')->default(1);
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serp_api_caches');
    }
};
