<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeywordRankCheck extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'batch_id',
        'keyword',
        'target_domain',
        'target_url',
        'match_type',
        'country',
        'location',
        'language',
        'device',
        'position',
        'is_ranked',
        'ranking_url',
        'ranking_title',
        'ranking_snippet',
        'previous_position',
        'rank_change',
        'serp_features',
        'top_competitors',
        'serpapi_search_url',
        'checked_at',
    ];

    protected $casts = [
        'is_ranked' => 'boolean',
        'position' => 'integer',
        'previous_position' => 'integer',
        'rank_change' => 'integer',
        'serp_features' => 'array',
        'top_competitors' => 'array',
        'checked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (KeywordRankCheck $check) {
            if (empty($check->batch_id)) {
                $check->batch_id = 'B-' . date('Ymd-His') . '-' . strtolower(\Illuminate\Support\Str::random(4));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeRanked(Builder $query): Builder
    {
        return $query->where('is_ranked', true)->whereNotNull('position');
    }

    public function scopeTop3(Builder $query): Builder
    {
        return $query->where('is_ranked', true)->whereBetween('position', [1, 3]);
    }

    public function scopeTop10(Builder $query): Builder
    {
        return $query->where('is_ranked', true)->whereBetween('position', [1, 10]);
    }

    public function scopeTop50(Builder $query): Builder
    {
        return $query->where('is_ranked', true)->whereBetween('position', [1, 50]);
    }

    public function scopeUnranked(Builder $query): Builder
    {
        return $query->where('is_ranked', false)->orWhereNull('position');
    }
}
