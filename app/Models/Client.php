<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'website_url',
        'industry',
        'brand_tone',
        'target_audience',
        'cta_default',
        'sitemap_url',
        'sitemap_cache',
        'competitor_urls',
        'approved_reference_domains',
        'gsc_property_id',
        'ga4_property_id',
        'google_ads_id',
        'wordpress_url',
        'wordpress_username',
        'wordpress_app_password',
        'is_active',
    ];

    protected $casts = [
        'competitor_urls' => 'array',
        'approved_reference_domains' => 'array',
        'sitemap_cache' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topicDiscoveries(): HasMany
    {
        return $this->hasMany(TopicDiscovery::class);
    }

    public function keywordClusters(): HasMany
    {
        return $this->hasMany(KeywordCluster::class);
    }

    public function contentBriefs(): HasMany
    {
        return $this->hasMany(ContentBrief::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
