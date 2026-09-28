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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('website_url');
            $table->string('industry');
            $table->string('brand_tone')->default('Conversational, Authoritative');
            $table->text('target_audience')->nullable();
            $table->text('cta_default')->nullable();
            $table->json('competitor_urls')->nullable();
            $table->json('approved_reference_domains')->nullable();
            $table->string('gsc_property_id')->nullable();
            $table->string('ga4_property_id')->nullable();
            $table->string('google_ads_id')->nullable();
            $table->string('wordpress_url')->nullable();
            $table->string('wordpress_username')->nullable();
            $table->string('wordpress_app_password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
