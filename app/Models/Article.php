<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'seo_generation_job_id',
        'title',
        'meta_title',
        'meta_description',
        'slug',
        'html_content',
        'markdown_content',
        'schema_jsonld',
        'word_count',
        'seo_score',
        'flesch_reading_ease',
        'keyword_density_metrics',
        'prompt_tokens',
        'completion_tokens',
        'wordpress_post_id',
        'wordpress_post_url',
        'docx_path',
        'pdf_path',
    ];

    protected $casts = [
        'schema_jsonld' => 'array',
        'keyword_density_metrics' => 'array',
        'word_count' => 'integer',
        'seo_score' => 'integer',
        'flesch_reading_ease' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seoGenerationJob(): BelongsTo
    {
        return $this->belongsTo(SeoGenerationJob::class);
    }

    public function generationJob(): BelongsTo
    {
        return $this->belongsTo(SeoGenerationJob::class, 'seo_generation_job_id');
    }
}
