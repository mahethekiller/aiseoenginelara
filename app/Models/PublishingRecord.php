<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PublishingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'content_brief_id',
        'title',
        'published_url',
        'publish_date',
        'author',
        'content_version',
        'primary_keyword',
        'body_html',
        'word_count',
        'status',
        'wordpress_post_id',
    ];

    protected $casts = [
        'publish_date' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contentBrief(): BelongsTo
    {
        return $this->belongsTo(ContentBrief::class);
    }

    public function performanceMetrics(): HasMany
    {
        return $this->hasMany(PerformanceMetricsGscGa4::class);
    }

    public function diagnostic(): HasOne
    {
        return $this->hasOne(AiPerformanceDiagnostic::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(OptimizationRecommendation::class);
    }
}
