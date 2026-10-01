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
        'client_id',
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
        'prompt_template_id',
        'prompt_template_name',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getClientNameAttribute(): string
    {
        if ($this->client) {
            return $this->client->name;
        }

        $clientId = $this->generationJob?->parameters['client_id'] ?? null;
        if ($clientId && $clientId !== 'none') {
            static $clientCache = null;
            if ($clientCache === null) {
                $clientCache = Client::all()->keyBy('id');
            }
            return $clientCache[$clientId]->name ?? 'Independent';
        }

        return 'Independent';
    }

    public function seoGenerationJob(): BelongsTo
    {
        return $this->belongsTo(SeoGenerationJob::class);
    }

    public function generationJob(): BelongsTo
    {
        return $this->belongsTo(SeoGenerationJob::class, 'seo_generation_job_id');
    }

    public function promptTemplate(): BelongsTo
    {
        return $this->belongsTo(AiPromptTemplate::class, 'prompt_template_id');
    }

    public function getPromptTemplateInfoAttribute(): array
    {
        static $templateCache = null;
        if ($templateCache === null) {
            $templateCache = AiPromptTemplate::all()->keyBy('id');
        }

        $params = $this->generationJob?->parameters ?? [];
        $templateId = $this->prompt_template_id ?? ($params['prompt_template_id'] ?? null);
        $template = $templateId ? ($templateCache[$templateId] ?? null) : null;

        $name = $this->prompt_template_name
            ?: ($template ? $template->archetype_name : 'Comprehensive Master Prompt');

        $isCustom = $template ? ! $template->is_system : false;
        $isSystem = $template ? (bool) $template->is_system : true;

        return [
            'id' => $template?->id,
            'name' => $name,
            'archetype_key' => $template?->archetype_key ?? 'master_seo_directive',
            'is_custom' => $isCustom,
            'is_system' => $isSystem,
            'format' => $params['format'] ?? 'Ultimate Guide',
            'tone' => $params['tone'] ?? 'Authoritative',
            'pov' => $params['pov'] ?? 'Second Person',
        ];
    }
}
