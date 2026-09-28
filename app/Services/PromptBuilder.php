<?php

namespace App\Services;

class PromptBuilder
{
    /**
     * Build prompt for Semantic HTML & Clean Copy rewriter mode.
     */
    public function buildSemanticCleanRewriterPrompt(string $url, string $rawText, ?string $customInstructions = null): array
    {
        $systemPrompt = "You are an elite SEO copywriter. Completely rewrite and rephrase all text using fresh, highly engaging, and distinct wording while strictly preserving the underlying facts, amenities, policies, and key details.\n\n"
            ."CRITICAL INSTRUCTIONS:\n"
            ."1. DO NOT copy sentences or phrases verbatim. Transform sentence structures, vocabulary, and flow.\n"
            ."2. Output ONLY clean semantic HTML body tags (<h1>, <h2>, <p>, <ul>, <li>).\n"
            .'3. Do NOT use markdown code blocks like ```html ... ```.';

        if ($customInstructions) {
            $systemPrompt .= "\n\nAdditional Directives: ".$customInstructions;
        }

        $userPrompt = "Target URL: {$url}\n\nOriginal Page Content to Rewrite:\n{$rawText}";

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt,
        ];
    }

    /**
     * Build system prompt for Layout-Preserving Structure mode.
     */
    public function buildLayoutPreservingRewriterPrompt(?string $customInstructions = null): string
    {
        $systemPrompt = "You are an expert HTML layout copywriter.\n"
            ."Your task is to completely rewrite and rephrase the human-readable text content inside headings, paragraphs, spans, buttons, and list items using fresh, creative, and distinct wording.\n\n"
            ."CRITICAL RULES:\n"
            ."1. DO NOT copy text verbatim. Completely transform sentence structures and vocabulary while maintaining core facts, amenities, and details.\n"
            ."2. DO NOT remove, rename, or modify any HTML tags, CSS classes, IDs, inline styles, or container hierarchy.\n"
            .'3. Return ONLY valid HTML or JSON mapping as requested. Do NOT include markdown code blocks like ```html ... ```.';

        if ($customInstructions) {
            $systemPrompt .= "\n\nAdditional Directives: ".$customInstructions;
        }

        return $systemPrompt;
    }

    /**
     * Build prompt for SEO Article Generation.
     */
    public function buildSeoArticlePrompt(array $params, ?string $presetInstructions = null, array $competitors = [], ?array $clientContext = null): array
    {
        $language = $params['language'] ?? 'English';
        $articleType = $params['format'] ?? 'Ultimate Guide';
        $tone = $params['tone'] ?? ($clientContext['brand_tone'] ?? 'Professional & Authoritative');
        $targetAudience = $params['target_audience'] ?? ($clientContext['target_audience'] ?? 'General Audience');
        $wordCount = $params['word_count'] ?? 'Standard (1200-1800 words)';
        $primaryKeyword = $params['primary_keyword'] ?? '';
        $secondaryKeywords = is_array($params['secondary_keywords'] ?? null)
            ? implode(', ', $params['secondary_keywords'])
            : ($params['secondary_keywords'] ?? 'None specified');

        $systemPrompt = "You are a world-class Senior SEO Content Strategist, Copywriter, and Subject Matter Expert.\n"
            ."Your objective is to craft an original, authoritative, highly engaging, and search-engine-optimized article.\n\n";

        if (! empty($clientContext['name'])) {
            $brandUrl = ! empty($clientContext['website_url']) ? " ({$clientContext['website_url']})" : '';
            $systemPrompt .= "### Client & Brand Identity:\n"
                ."You are writing on behalf of: {$clientContext['name']}{$brandUrl}.\n"
                .(! empty($clientContext['industry']) ? "Industry: {$clientContext['industry']}\n" : '')
                ."Establish the brand as the premier, trusted voice in this subject matter.\n\n";
        }

        $systemPrompt .= "### Core SEO & Content Guidelines:\n"
            ."1. Language: Write entirely in {$language}.\n"
            ."2. Article Format: {$articleType}.\n"
            ."3. Tone of Voice: {$tone}.\n"
            ."4. Target Audience: {$targetAudience}.\n"
            .(! empty($params['industry']) ? "5. Target Industry / Domain Context: {$params['industry']}.\n" : '')
            ."6. Target Word Count: Approximately {$wordCount}.\n"
            ."7. Primary Keyword: '{$primaryKeyword}' (Must appear in H1 title, first 100 words, at least one H2, meta description, and naturally throughout with 1.5-2.0% density).\n"
            ."8. Secondary / LSI Keywords: Naturally weave in these terms where relevant: {$secondaryKeywords}.\n\n"
            ."### Structural & Formatting Requirements (Use Semantic HTML):\n"
            ."- Exactly ONE <h1> main title containing the primary keyword.\n"
            ."- Logical heading hierarchy using <h2> and <h3> tags.\n"
            ."- Do NOT output duplicate <h2> headings for any section.\n"
            ."- Include at least ONE rich visual element such as a comparison table (<table>) or structured list comparing key approaches, and code snippets where relevant.\n"
            ."- Use short paragraphs (2-4 sentences max), bullet points (<ul><li>), and bold key phrases for visual scannability.\n"
            ."- Include a <div class=\"key-takeaways\"> summary box right after the intro with 3-5 core bullet points.\n"
            ."- Include an <h2>Frequently Asked Questions</h2> section near the end with 3-5 high-value questions and concise answers.\n"
            ."- Include a dedicated <h2>Conclusion</h2> (or <h2>Final Thoughts</h2>) section near the end (2-3 detailed paragraphs) synthesizing the core takeaways, recommendations, and strategic outlook before the CTA box.\n";

        if (! empty($clientContext['cta_default'])) {
            $ctaUrl = ! empty($clientContext['website_url']) ? htmlspecialchars($clientContext['website_url']) : '#';
            $ctaBrand = ! empty($clientContext['name']) ? htmlspecialchars($clientContext['name']) : 'us';
            $ctaDirective = htmlspecialchars($clientContext['cta_default']);
            $systemPrompt .= "- End with an official Call-To-Action (CTA) box: <div class=\"cta-box\"><h2>Ready to Take the Next Step?</h2><p>{$ctaDirective}</p><a href=\"{$ctaUrl}\" class=\"cta-button\">Get Started with {$ctaBrand}</a></div>.\n";
        } else {
            $systemPrompt .= "- End with a strong Call-To-Action (CTA) box (<div class=\"cta-box\">) encouraging user engagement or next steps.\n";
        }

        $systemPrompt .= "- Insert 2-3 HTML comment blocks formatted as: <!-- IMAGE_PROMPT: Section Name | Alt Text | DALL-E/Midjourney Prompt --> at key visual breaks.\n\n";

        if (! empty($clientContext['internal_links'])) {
            $linksFormatted = [];
            foreach (array_slice($clientContext['internal_links'], 0, 4) as $link) {
                $u = is_array($link) ? ($link['url'] ?? '') : (string) $link;
                $t = is_array($link) ? ($link['title'] ?? $u) : (string) $link;
                if ($u) {
                    $linksFormatted[] = "- {$u} (Topic: {$t})";
                }
            }
            if (! empty($linksFormatted)) {
                $systemPrompt .= "### Contextual Internal Linking Directives:\n"
                    ."Naturally hyperlink 1-3 contextual references in the body text to these relevant client pages with descriptive anchor text:\n"
                    .implode("\n", $linksFormatted)."\n\n";
            }
        }

        if (! empty($clientContext['approved_reference_domains'])) {
            $refDomains = is_array($clientContext['approved_reference_domains'])
                ? $clientContext['approved_reference_domains']
                : explode(',', $clientContext['approved_reference_domains']);
            $refDomains = array_filter(array_map('trim', $refDomains));
            if (! empty($refDomains)) {
                $systemPrompt .= "### Approved External Citation References:\n"
                    .'When referencing external benchmarks or research, prioritize citing these approved domains: '
                    .implode(', ', $refDomains)."\n\n";
            }
        }

        $systemPrompt .= "### Editorial & Copywriting Rules:\n"
            .$this->formatPovInstruction($params['pov'] ?? null)."\n"
            ."- Banned Words Filter:\n"
            ."  ❌ DO NOT use 'can' -> Replace with 'does', 'enables', 'is capable of', or direct action verbs.\n"
            ."  ❌ DO NOT use 'hence', 'thus', 'as per', 'etc', 'via', 'therefore', 'moreover'.\n"
            ."  ❌ DO NOT use 'then' -> Replace with a comma and move on.\n"
            ."  ❌ DO NOT start sentences with 'however' or 'but'.\n"
            ."  ❌ DO NOT use 'have to' or 'must' -> Replace with 'need to' or action verbs.\n"
            ."- Grammar & Voice: Active voice only, simple present tense, and bold lead-in titles for bullet items (e.g., <li><strong>Title:</strong> ...</li>).\n\n"
            ."### Metadata Output Block:\n"
            ."At the very top of your output (before the <h1>), include a JSON block enclosed in ```json_metadata ... ``` containing:\n"
            ."{\n"
            ."  \"meta_title\": \"SEO Title under 60 chars including primary keyword\",\n"
            ."  \"meta_description\": \"Compelling meta description under 160 chars with primary keyword & CTA\",\n"
            ."  \"url_slug\": \"clean-url-slug-keyword\",\n"
            ."  \"primary_keyword\": \"{$primaryKeyword}\",\n"
            ."  \"faq_schema\": [ {\"question\": \"...\", \"answer\": \"...\"} ]\n"
            .'}';

        $topic = $params['topic'] ?? '';
        $searchIntent = $params['search_intent'] ?? 'Informational';
        $competitorStr = ! empty($competitors) ? implode(', ', $competitors) : 'None';

        $userPrompt = "Topic: {$topic}\n"
            ."Primary Keyword: {$primaryKeyword}\n"
            ."Secondary Keywords: {$secondaryKeywords}\n"
            ."Search Intent: {$searchIntent}\n"
            ."Article Format: {$articleType}\n"
            ."Target Audience: {$targetAudience}\n"
            .(! empty($params['industry']) ? "Target Industry: {$params['industry']}\n" : '')
            .'Point of View (POV): '.($params['pov'] ?? 'Second Person')."\n"
            ."Target Word Count: {$wordCount}\n"
            ."Tone: {$tone}\n"
            ."Competitor Outlines/Resources: {$competitorStr}\n";

        if ($presetInstructions) {
            $userPrompt .= "\nPreset Directive: ".$presetInstructions."\n";
        }

        $userPrompt .= "\nGenerate a complete, fully structured SEO article matching all specifications above.";

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt,
        ];
    }

    /**
     * Build system and user prompt for generating a detailed JSON article outline.
     */
    public function buildOutlinePrompt(array $params, ?string $presetInstructions = null, array $competitorOutlines = [], ?array $clientContext = null): array
    {
        $topic = $params['topic'] ?? '';
        $primaryKeyword = $params['primary_keyword'] ?? '';
        $secondaryKeywords = $params['secondary_keywords'] ?? '';
        $format = $params['format'] ?? 'Ultimate Guide';
        $wordLimitSelection = $params['word_count'] ?? 'Standard';
        $industry = ! empty($params['industry']) ? trim($params['industry']) : ($clientContext['industry'] ?? null);

        // Calculate segment specifications
        $spec = match ($wordLimitSelection) {
            'Short' => ['count' => 4, 'total' => 900],
            'Standard' => ['count' => 6, 'total' => 1500],
            'Long-form' => ['count' => 8, 'total' => 2500],
            'In-Depth' => ['count' => 10, 'total' => 4000],
            default => ['count' => 6, 'total' => 1500]
        };

        $systemPrompt = "You are a master SEO Outline Architect.\n"
            ."Your job is to structure a highly detailed, comprehensive content blueprint for an article of approximately {$spec['total']} words in JSON format.\n"
            ."Ensure the outline covers all primary headings while maintaining a logical flow. Outrank competitors by addressing search intent gaps.\n\n";

        if (! empty($clientContext['name'])) {
            $systemPrompt .= "### Client & Brand Persona:\n"
                ."- Brand: {$clientContext['name']}".(! empty($clientContext['website_url']) ? " ({$clientContext['website_url']})" : '')."\n"
                .(! empty($clientContext['industry']) ? "- Industry: {$clientContext['industry']}\n" : '')
                .(! empty($clientContext['brand_tone']) ? "- Brand Tone: {$clientContext['brand_tone']}\n" : '')
                .(! empty($clientContext['target_audience']) ? "- Target Audience: {$clientContext['target_audience']}\n" : '')
                ."- Directive: The outline must naturally build authority for this brand, and the 'cta' section must conclude with the brand's primary value proposition.\n\n";
        } elseif (! empty($industry)) {
            $systemPrompt .= "### Target Domain & Industry Context:\n"
                ."- Industry / Niche: {$industry}\n"
                ."- Directive: Structure outline topics with deep expertise and terminology standard in this domain.\n\n";
        }

        $systemPrompt .= "CRITICAL INSTRUCTION: You must return ONLY a raw JSON array of sections. Do NOT wrap in markdown or include extra text. The JSON schema must look exactly like this:\n"
            ."[\n"
            ."  {\n"
            ."    \"heading\": \"Section Title (include primary/secondary keyword in at least some headings)\",\n"
            ."    \"type\": \"intro | key-takeaways | standard | comparison-table | faq | conclusion | cta\",\n"
            ."    \"talking_points\": [\"Specific point to cover 1\", \"Specific point to cover 2\"],\n"
            ."    \"target_words\": 250\n"
            ."  }\n"
            ."]\n\n"
            ."MANDATORY OUTLINE STRUCTURE RULES:\n"
            ."- First section must be type 'intro'.\n"
            ."- Include structured body sections of type 'standard' and at least one 'comparison-table'.\n"
            ."- Include a 'faq' section near the end.\n"
            ."- You MUST ALWAYS include a dedicated 'conclusion' section (heading like 'Conclusion', 'Final Thoughts', or 'Summary & Key Takeaways') with 200-250 target words synthesizing core insights and actionable advice before the final call-to-action.\n"
            ."- End with a 'cta' section for conversion.\n";

        $competitorStr = ! empty($competitorOutlines) ? implode("\n", $competitorOutlines) : 'None extracted.';

        $userPrompt = "Topic: {$topic}\n"
            ."Primary Keyword: {$primaryKeyword}\n"
            ."Secondary Keywords: {$secondaryKeywords}\n"
            ."Article Format: {$format}\n"
            .(! empty($industry) ? "Industry Context: {$industry}\n" : '')
            .'Point of View (POV): '.($params['pov'] ?? 'Second Person')."\n"
            ."Target Word Count: {$spec['total']}\n"
            ."Requested H2 Section Count: {$spec['count']}\n"
            ."Competitor Outlines/Structures:\n{$competitorStr}\n";

        if (! empty($clientContext['cta_default'])) {
            $userPrompt .= "Client Call-to-Action Theme: {$clientContext['cta_default']}\n";
        }

        $userPrompt .= "\nGenerate the JSON outline array now.";

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt,
        ];
    }

    /**
     * Build prompt to write a specific section, keeping track of history.
     */
    public function buildSectionPrompt(array $params, array $section, string $previousContent, ?string $presetInstructions = null, ?array $clientContext = null): array
    {
        $language = $params['language'] ?? 'English';
        $tone = $params['tone'] ?? ($clientContext['brand_tone'] ?? 'Professional & Authoritative');
        $targetAudience = $params['target_audience'] ?? ($clientContext['target_audience'] ?? 'General Audience');
        $primaryKeyword = $params['primary_keyword'] ?? '';
        $secondaryKeywords = $params['secondary_keywords'] ?? '';
        $industry = ! empty($params['industry']) ? trim($params['industry']) : ($clientContext['industry'] ?? null);

        $systemPrompt = "You are a world-class Senior SEO Copywriter and Subject Matter Expert writing in {$language}.\n"
            ."Write a deeply informative, original, and highly engaging section for the heading: \"{$section['heading']}\".\n\n";

        if (! empty($clientContext['name'])) {
            $brandUrl = ! empty($clientContext['website_url']) ? " ({$clientContext['website_url']})" : '';
            $systemPrompt .= "### Authorship & Brand Persona:\n"
                ."You are writing as the official voice of {$clientContext['name']}{$brandUrl}.\n"
                ."Reflect deep industry authority, credibility, and hands-on expertise throughout the copy.\n\n";
        }

        $systemPrompt .= "### Editorial & Copywriting Rules:\n"
            ."- Write in the {$tone} tone for a {$targetAudience} audience.\n"
            .(! empty($industry) ? "- Industry / Niche Context: {$industry}. Use relevant domain expertise.\n" : '')
            ."- Target Word Count: Write approximately {$section['target_words']} words for this section.\n"
            .$this->formatPovInstruction($params['pov'] ?? null)."\n"
            ."- Voice: Active voice only, simple present tense.\n"
            ."- Banned Words Filter:\n"
            ."  ❌ DO NOT use 'can' -> Replace with 'does', 'enables', 'is capable of', or direct action verbs.\n"
            ."  ❌ DO NOT use 'hence', 'thus', 'as per', 'etc', 'via', 'therefore', 'moreover'.\n"
            ."  ❌ DO NOT use 'then' -> Replace with a comma and move on.\n"
            ."  ❌ DO NOT start sentences with 'however' or 'but'.\n"
            ."- Bold lead-ins for bullet points (e.g., <li><strong>Title:</strong> Description...</li>).\n\n"
            ."### Styling & Markup Rules:\n"
            ."- Output ONLY clean semantic HTML body tags (<h2>, <h3>, <p>, <ul>, <li>, <table>, <strong>).\n"
            ."- Do NOT include <html>, <body>, or markdown code blocks like ```html ... ```.\n"
            ."- Do NOT repeat the main article H1 title.\n"
            ."- Use the section type: \"{$section['type']}\" instructions:\n";

        if ($section['type'] === 'intro') {
            $systemPrompt .= "  - Focus on hooking the reader immediately, introducing the core topic, and incorporating the focus keyword: '{$primaryKeyword}'.\n";
        } elseif ($section['type'] === 'key-takeaways') {
            $systemPrompt .= "  - Render exactly: <div class=\"key-takeaways\"><h3>Key Takeaways</h3><ul><li>...</li></ul></div> containing 3-5 core takeaways.\n";
        } elseif ($section['type'] === 'comparison-table') {
            $systemPrompt .= "  - Render a clean, descriptive comparison table comparing key items/concepts. Format: <table><thead><tr><th>...</th></tr></thead><tbody><tr><td>...</td></tr></tbody></table>.\n";
        } elseif ($section['type'] === 'faq') {
            $systemPrompt .= "  - Render 3-5 high-value Frequently Asked Questions with short, authoritative answers using H3 tags for questions and paragraphs for answers.\n";
        } elseif ($section['type'] === 'conclusion') {
            $systemPrompt .= "  - Write a comprehensive, high-value conclusion section (2-3 detailed paragraphs). Synthesize the main insights, deliver strategic and actionable takeaways, address key considerations, and provide a clear final verdict for the reader. Do not write a shallow summary; make it deeply useful.\n";
        } elseif ($section['type'] === 'cta') {
            if (! empty($clientContext['cta_default'])) {
                $ctaUrl = ! empty($clientContext['website_url']) ? htmlspecialchars($clientContext['website_url']) : '#';
                $ctaBrand = ! empty($clientContext['name']) ? htmlspecialchars($clientContext['name']) : 'our team';
                $ctaDirective = htmlspecialchars($clientContext['cta_default']);
                $systemPrompt .= "  - Render exactly a conversion callout: <div class=\"cta-box\"><h2>Take the Next Step</h2><p>{$ctaDirective}</p><a href=\"{$ctaUrl}\" class=\"cta-button\">Connect with {$ctaBrand}</a></div> to conclude the article, directly embedding the client's official call-to-action.\n";
            } else {
                $systemPrompt .= "  - Render exactly a conversion callout: <div class=\"cta-box\"><h2>...</h2><p>...</p></div> to conclude the article.\n";
            }
        }

        // Add visual comments instruction randomly for standard sections
        if (in_array($section['type'], ['standard', 'comparison-table'])) {
            $systemPrompt .= "- Insert exactly one image prompt comment: <!-- IMAGE_PROMPT: Section Name | Alt Text | DALL-E/Midjourney Prompt --> at a logical visual break.\n";
        }

        // Inject contextual internal links for standard and intro sections
        if (! empty($clientContext['internal_links']) && in_array($section['type'], ['standard', 'intro'])) {
            $linksFormatted = [];
            foreach (array_slice($clientContext['internal_links'], 0, 3) as $link) {
                $u = is_array($link) ? ($link['url'] ?? '') : (string) $link;
                $t = is_array($link) ? ($link['title'] ?? $u) : (string) $link;
                if ($u) {
                    $linksFormatted[] = "- {$u} (Context: {$t})";
                }
            }
            if (! empty($linksFormatted)) {
                $systemPrompt .= "\n### Contextual Internal Linking Directive:\n"
                    ."If contextually relevant, naturally embed 1-2 hyperlinks to the client's existing pages using natural, descriptive anchor text (DO NOT use naked URLs or generic text like 'click here'):\n"
                    .implode("\n", $linksFormatted)."\n";
            }
        }

        // Inject approved external reference domains
        if (! empty($clientContext['approved_reference_domains'])) {
            $refDomains = is_array($clientContext['approved_reference_domains'])
                ? $clientContext['approved_reference_domains']
                : explode(',', $clientContext['approved_reference_domains']);
            $refDomains = array_filter(array_map('trim', $refDomains));
            if (! empty($refDomains)) {
                $systemPrompt .= "\n### Approved External Citations:\n"
                    .'When citing data, statistics, or industry authorities, prioritize citing sources from these approved reference domains: '
                    .implode(', ', $refDomains).".\n";
            }
        }

        if ($presetInstructions) {
            $systemPrompt .= "\nPreset Directive: ".$presetInstructions."\n";
        }

        $userPrompt = "Heading: {$section['heading']}\n"
            ."Section Type: {$section['type']}\n"
            ."Talking Points to Cover:\n".implode("\n", array_map(fn ($t) => '- '.$t, $section['talking_points']))."\n\n"
            ."Primary Keyword: {$primaryKeyword}\n"
            ."Secondary/LSI Keywords to integrate naturally: {$secondaryKeywords}\n\n"
            ."Previous written sections (For context to avoid repetition or style mismatch):\n"
            ."------------------\n"
            .$previousContent."\n"
            ."------------------\n\n"
            .'Write the HTML content for this section now.';

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt,
        ];
    }

    /**
     * Build prompt for generating the metadata block based on final compiled HTML.
     */
    public function buildMetadataPrompt(string $topic, string $compiledHtml): array
    {
        $systemPrompt = "You are an elite SEO Metadata Optimizer.\n"
            ."Analyze the provided article and generate optimized SEO metadata in JSON format.\n\n"
            ."CRITICAL INSTRUCTION: Return ONLY a raw JSON block. Do NOT include markdown blocks or extra text. The JSON schema must look exactly like this:\n"
            ."{\n"
            ."  \"meta_title\": \"SEO Title under 60 chars including focus keywords\",\n"
            ."  \"meta_description\": \"Compelling meta description under 160 chars with focus keywords & CTA\",\n"
            ."  \"url_slug\": \"clean-url-slug-topic\",\n"
            ."  \"faq_schema\": [ {\"question\": \"...\", \"answer\": \"...\"} ]\n"
            .'}';

        $userPrompt = "Topic: {$topic}\n\nCompiled HTML Article Content:\n{$compiledHtml}";

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt,
        ];
    }

    /**
     * Extract json_metadata block and clean HTML from LLM output.
     */
    public function parseMetadataAndHtml(string $llmOutput): array
    {
        $metadata = [];
        $html = $llmOutput;

        if (preg_match('/```json_metadata\s*(\{.*?\})\s*```/s', $llmOutput, $matches)) {
            $jsonStr = $matches[1];
            $decoded = json_decode($jsonStr, true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
            $html = trim(str_replace($matches[0], '', $llmOutput));
        }

        // Clean any residual ```html ... ``` wrapping if returned
        if (preg_match('/```html\s*(.*?)\s*```/s', $html, $htmlMatches)) {
            $html = trim($htmlMatches[1]);
        }

        return [
            'metadata' => $metadata,
            'html' => $html,
        ];
    }

    /**
     * Format Point of View / Narrative Perspective directive for prompts.
     */
    protected function formatPovInstruction(?string $pov): string
    {
        $povClean = strtolower(trim((string) $pov));
        if (str_contains($povClean, 'first')) {
            return "- Narrative Perspective: First person ('we', 'our', or 'I' where fitting). Write with direct practitioner authority, firsthand experience, case tests, and team insights.";
        } elseif (str_contains($povClean, 'third')) {
            return "- Narrative Perspective: Third person ('they', 'industry experts', 'organizations'). Maintain an objective, journalistic, expert analytical viewpoint without addressing the reader directly as 'you'.";
        } else {
            // Default: Second person
            return "- Narrative Perspective: Second person ('you', 'your'). Speak directly to the reader, focusing on their specific needs, actions, and step-by-step guidance.";
        }
    }
}
