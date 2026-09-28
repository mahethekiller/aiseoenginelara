<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiPromptTemplate extends Model
{
    use HasFactory;

    protected $table = 'ai_prompt_templates';

    protected $fillable = [
        'user_id',
        'client_id',
        'archetype_key',
        'archetype_name',
        'description',
        'system_prompt_template',
        'available_placeholders',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'available_placeholders' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope for templates available to a given user and client
     */
    public function scopeAvailableFor($query, $userId = null, $clientId = null)
    {
        return $query->where(function ($q) use ($userId, $clientId) {
            $q->where('is_system', true);
            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
            if ($clientId) {
                $q->orWhere('client_id', $clientId);
            }
        });
    }

    /**
     * Compile the prompt template by substituting {{variable_name}} placeholders
     */
    public function compile(array $variables): string
    {
        $template = $this->system_prompt_template;

        foreach ($variables as $key => $val) {
            $replacement = is_array($val) ? implode(', ', $val) : (string) $val;
            $template = str_replace('{{'.$key.'}}', $replacement, $template);
        }

        return $template;
    }
}
