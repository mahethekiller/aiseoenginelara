<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptimizationRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'publishing_record_id',
        'issue_type',
        'suggested_action',
        'action_details',
        'status',
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
