<?php

namespace App\Services;

use App\Models\LearningRepository;

class SelfLearningEngine
{
    /**
     * Step 10: Extract institutional learnings from AI diagnostics
     */
    public function recordLearning(int $clientId, string $title, string $description, string $impactMetric, array $rule, array $applicableContentTypes = ['all']): LearningRepository
    {
        $codeCount = LearningRepository::where('client_id', $clientId)->count() + 1;
        $learningCode = "LEARNING-{$codeCount}";

        return LearningRepository::create([
            'client_id' => $clientId,
            'learning_code' => $learningCode,
            'title' => $title,
            'insight_description' => $description,
            'impact_metric' => $impactMetric,
            'auto_apply_rule' => $rule,
            'applicable_content_types' => $applicableContentTypes,
            'is_active' => true,
        ]);
    }

    /**
     * Step 11: Auto-inject active learnings matching target content_type into System Prompt
     */
    public function injectLearningsIntoPrompt(int $clientId, string $systemPrompt, string $contentType = 'onpage_blog'): string
    {
        $learnings = LearningRepository::where('client_id', $clientId)
            ->where('is_active', true)
            ->get();

        if ($learnings->isEmpty()) {
            return $systemPrompt;
        }

        // Filter learnings matching target content type OR marked as 'all' / '*'
        $matchingLearnings = $learnings->filter(function ($learning) use ($contentType) {
            $types = $learning->applicable_content_types;
            if (empty($types) || ! is_array($types)) {
                return true; // Default to all if null
            }

            return in_array('all', $types) || in_array('*', $types) || in_array($contentType, $types);
        });

        if ($matchingLearnings->isEmpty()) {
            return $systemPrompt;
        }

        $block = "\n\n=== MANDATORY INSTITUTIONAL LEARNING RULES (AUTO-INJECTED FOR: ".strtoupper($contentType).") ===\n";
        $block .= "STRICT COMPLIANCE INSTRUCTION: You MUST strictly enforce every institutional rule listed below during article generation. Do NOT skip any requirements.\n";
        foreach ($matchingLearnings as $idx => $learning) {
            $num = $idx + 1;
            $ruleDetails = is_array($learning->auto_apply_rule) ? json_encode($learning->auto_apply_rule) : $learning->auto_apply_rule;
            $block .= "{$num}. [{$learning->learning_code}]: {$learning->title} (Impact: {$learning->impact_metric})\n";
            $block .= "   - MANDATORY ACTION: Enforce rule directives: {$ruleDetails}. (e.g. Ensure minimum required FAQ items, schema markup, or brand heading placements are fully included).\n";
        }
        $block .= "====================================================================================\n";

        return $systemPrompt.$block;
    }
}
