<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningRepository extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'learning_code',
        'title',
        'insight_description',
        'impact_metric',
        'auto_apply_rule',
        'applicable_content_types',
        'is_active',
    ];

    protected $casts = [
        'auto_apply_rule' => 'array',
        'applicable_content_types' => 'array',
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
