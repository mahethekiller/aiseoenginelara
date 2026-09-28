<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SeoGenerationJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'execution_mode',
        'status',
        'parameters',
        'total_items',
        'completed_items',
        'logs',
        'error_message',
    ];

    protected $casts = [
        'parameters' => 'array',
        'total_items' => 'integer',
        'completed_items' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function tokenUsageLogs(): MorphMany
    {
        return $this->morphMany(TokenUsageLog::class, 'jobable');
    }
}
