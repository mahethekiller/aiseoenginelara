<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KeywordCluster extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'topic_discovery_id',
        'primary_keyword',
        'primary_sv',
        'secondary_keywords',
        'long_tail_keywords',
        'question_keywords',
        'lsi_keywords',
        'commercial_keywords',
        'transactional_keywords',
        'informational_keywords',
        'is_ai_enriched',
        'llm_provider',
        'ai_placement_map',
        'ai_data',
        'standard_metrics',
    ];

    protected $casts = [
        'primary_sv' => 'integer',
        'secondary_keywords' => 'array',
        'long_tail_keywords' => 'array',
        'question_keywords' => 'array',
        'lsi_keywords' => 'array',
        'commercial_keywords' => 'array',
        'transactional_keywords' => 'array',
        'informational_keywords' => 'array',
        'is_ai_enriched' => 'boolean',
        'ai_placement_map' => 'array',
        'ai_data' => 'array',
        'standard_metrics' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function topicDiscovery(): BelongsTo
    {
        return $this->belongsTo(TopicDiscovery::class);
    }

    public function contentBrief(): HasOne
    {
        return $this->hasOne(ContentBrief::class);
    }
}
