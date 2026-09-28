<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TopicDiscovery extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'topic_name',
        'target_url',
        'organic_keywords',
        'top_pages',
        'traffic_estimate',
        'ranking_keywords',
        'featured_snippets',
        'keyword_difficulty',
        'search_intent',
        'classification',
        'priority_score',
        'status',
    ];

    protected $casts = [
        'organic_keywords' => 'array',
        'top_pages' => 'array',
        'ranking_keywords' => 'array',
        'featured_snippets' => 'array',
        'traffic_estimate' => 'integer',
        'keyword_difficulty' => 'integer',
        'priority_score' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function keywordCluster(): HasOne
    {
        return $this->hasOne(KeywordCluster::class);
    }
}
