<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SemrushApiCache extends Model
{
    protected $fillable = [
        'cache_key',
        'endpoint',
        'query_params',
        'response_data',
        'api_units_spent',
        'expires_at',
    ];

    protected $casts = [
        'query_params' => 'array',
        'response_data' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Scope query to valid (non-expired) caches
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }
}
