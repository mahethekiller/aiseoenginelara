<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Article;
use App\Models\Client;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (! Schema::hasColumn('articles', 'client_id')) {
                $table->foreignId('client_id')->nullable()->after('user_id')->constrained('clients')->nullOnDelete();
            }
        });

        // Safe non-destructive backfill for existing historical articles
        try {
            $articles = Article::with('generationJob')->get();
            foreach ($articles as $art) {
                $cId = $art->generationJob?->parameters['client_id'] ?? null;
                if ($cId && $cId !== 'none' && Client::where('id', $cId)->exists()) {
                    $art->update(['client_id' => $cId]);
                }
            }
        } catch (\Throwable $e) {
            // Silently continue if tables are still syncing
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'client_id')) {
                $table->dropConstrainedForeignId('client_id');
            }
        });
    }
};
