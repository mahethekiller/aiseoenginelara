<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContentBrief extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'keyword_cluster_id',
        'brand_name',
        'content_type',
        'working_title',
        'primary_keyword',
        'target_audience',
        'search_intent',
        'suggested_word_count',
        'tone_and_language',
        'cta_details',
        'seo_title',
        'meta_title',
        'meta_description',
        'url_slug',
        'canonical_url',
        'wireframe_structure',
        'brand_heading_rules',
        'keyword_placement_map',
        'intelligent_prompt',
    ];

    protected $casts = [
        'suggested_word_count' => 'integer',
        'wireframe_structure' => 'array',
        'brand_heading_rules' => 'array',
        'keyword_placement_map' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function keywordCluster(): BelongsTo
    {
        return $this->belongsTo(KeywordCluster::class);
    }

    public function publishingRecord(): HasOne
    {
        return $this->hasOne(PublishingRecord::class);
    }
}
