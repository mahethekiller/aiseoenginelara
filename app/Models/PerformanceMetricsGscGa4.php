<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceMetricsGscGa4 extends Model
{
    use HasFactory;

    protected $table = 'performance_metrics_gsc_ga4';

    protected $fillable = [
        'client_id',
        'publishing_record_id',
        'metric_date',
        'impressions',
        'clicks',
        'ctr',
        'avg_position',
        'sessions',
        'users',
        'engagement_rate',
        'bounce_rate',
        'conversions',
        'revenue',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'ctr' => 'float',
        'avg_position' => 'float',
        'sessions' => 'integer',
        'users' => 'integer',
        'engagement_rate' => 'float',
        'bounce_rate' => 'float',
        'conversions' => 'integer',
        'revenue' => 'float',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function publishingRecord(): BelongsTo
    {
        return $this->belongsTo(PublishingRecord::class);
    }
}
