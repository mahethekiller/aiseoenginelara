<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPerformanceDiagnostic extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'publishing_record_id',
        'outcome',
        'primary_reasons',
        'root_cause_analysis',
        'actionable_remedies',
    ];

    protected $casts = [
        'primary_reasons' => 'array',
        'actionable_remedies' => 'array',
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
