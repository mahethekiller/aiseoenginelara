<?php

namespace App\Services;

class AiHumanizer
{
    protected array $cliches = [
        'delve', 'tapestry', 'testament', 'pivotal', 'beacon',
        'fostering', 'seamlessly', 'game-changer', 'realm', 'plethora',
    ];

    public function polish(string $text): string
    {
        // 1. Run regex pass to strip common AI clichés
        foreach ($this->cliches as $cliche) {
            $pattern = '/\b'.preg_quote($cliche, '/').'\b/i';
            $text = preg_replace($pattern, $this->getHumanReplacement($cliche), $text);
        }

        return $text;
    }

    protected function getHumanReplacement(string $cliche): string
    {
        $replacements = [
            'delve' => 'explore',
            'tapestry' => 'combination',
            'testament' => 'proof',
            'pivotal' => 'important',
            'beacon' => 'guide',
            'fostering' => 'encouraging',
            'seamlessly' => 'easily',
            'game-changer' => 'innovation',
            'realm' => 'field',
            'plethora' => 'abundance',
        ];

        return $replacements[strtolower($cliche)] ?? $cliche;
    }
}
