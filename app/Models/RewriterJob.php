<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RewriterJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rewriter_mode',
        'status',
        'source_url',
        'original_html',
        'rewritten_html',
        'custom_instructions',
        'output_docx_path',
        'error_message',
        'prompt_tokens',
        'completion_tokens',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tokenUsageLogs(): MorphMany
    {
        return $this->morphMany(TokenUsageLog::class, 'jobable');
    }
}
