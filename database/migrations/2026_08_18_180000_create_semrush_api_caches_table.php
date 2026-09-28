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
        Schema::create('semrush_api_caches', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->unique();
            $table->string('endpoint');
            $table->json('query_params')->nullable();
            $table->json('response_data');
            $table->integer('api_units_spent')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semrush_api_caches');
    }
};
