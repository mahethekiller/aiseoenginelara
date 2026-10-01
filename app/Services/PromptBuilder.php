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
     * Build prompt for SEO Article Generation (Adheres to Master generic_onpage_blog_prompt.md).
     */
    public function buildSeoArticlePrompt(array $params, ?string $presetInstructions = null, array $competitors = [], ?array $clientContext = null): array
    {
        $language = $params['language'] ?? 'English';
        $articleType = $params['format'] ?? 'Ultimate Guide';
        $rawTone = $params['tone'] ?? null;
        if (empty($rawTone) || $rawTone === 'client_default') {
            $tone = $clientContext['brand_tone'] ?? 'Professional & Authoritative';
        } else {
            $tone = $rawTone;
        }
        $targetAudience = !empty($params['target_audience']) ? $params['target_audience'] : ($clientContext['target_audience'] ?? 'General Audience');
        $wordCount = $params['word_count'] ?? 'Standard (1200-1800 words)';
        $primaryKeyword = $params['primary_keyword'] ?? '';
        $secondaryKeywords = is_array($params['secondary_keywords'] ?? null)
            ? implode(', ', $params['secondary_keywords'])
            : ($params['secondary_keywords'] ?? 'None specified');
        $industry = ! empty($params['industry']) ? trim($params['industry']) : ($clientContext['industry'] ?? 'General');
        $searchIntent = $params['search_intent'] ?? 'Informational';
        $clientName = $clientContext['name'] ?? null;
        $clientWebsite = $clientContext['website_url'] ?? null;
        $brandUsp = $clientContext['cta_default'] ?? null;

        $systemPrompt = "You are a world-class Senior SEO Content Strategist, Copywriter, and Subject Matter Expert.\n\n"
            ."Your objective is to create an original, authoritative, highly engaging, search-engine-optimised article that satisfies user search intent, provides genuine value, and aligns with the client's brand, industry, and target audience.\n\n";

        // Section 2: Client & Brand Identity
        $systemPrompt .= "## Client & Brand Identity\n\n";
        if ($clientName) {
            $systemPrompt .= "- **Client/Brand:** {$clientName}\n"
                .($clientWebsite ? "- **Website:** {$clientWebsite}\n" : '')
                ."- **Industry:** {$industry}\n"
                ."- **Brand Positioning:** Position the brand as a credible, knowledgeable, and trusted voice within its industry.\n"
                .($brandUsp ? "- **Brand-Specific Instructions:** {$brandUsp}\n\n" : "- **Brand-Specific Instructions:** Highlight domain authority and genuine solutions without heavy promotional bias.\n\n");
        } else {
            $systemPrompt .= "- **Client/Brand:** Industry Expert / Thought Leader\n"
                ."- **Industry:** {$industry}\n"
                ."- **Brand Positioning:** Position the publication as a credible, knowledgeable, and trusted voice within its industry.\n\n";
        }

        // Section 3: Core SEO & Content Guidelines
        $systemPrompt .= "## Core SEO & Content Guidelines\n\n"
            ."1. **Language:** Write entirely in {$language}.\n"
            ."2. **Article Type:** {$articleType}.\n"
            ."3. **Tone of Voice:** {$tone}.\n"
            ."4. **Target Audience:** {$targetAudience}.\n"
            ."5. **Industry / Domain Context:** {$industry}.\n"
            ."6. **Target Word Count:** Approximately {$wordCount}.\n"
            ."7. **Primary Keyword:** '{$primaryKeyword}'.\n"
            ."   - Include the primary keyword naturally in the H1, introductory section, meta title, meta description, and relevant headings where contextually appropriate.\n"
            ."   - Use the keyword naturally throughout the article without forced repetition or keyword stuffing (maintain ~1.5-2.0% natural density).\n"
            ."   - Use close semantic variations where they improve readability and topical relevance.\n"
            ."8. **Secondary / LSI Keywords:** {$secondaryKeywords}.\n"
            ."   - Incorporate secondary, semantic, and related keywords naturally where relevant.\n"
            ."   - Do not force every keyword into the content.\n"
            ."9. **Search Intent:** Identify and satisfy the primary search intent behind the topic ({$searchIntent}). Structure the content around what the reader is trying to understand, compare, plan, solve, choose, or accomplish.\n"
            ."10. **Topical Relevance:** Keep every section directly relevant to the main topic and search intent. Avoid generic filler or sections that do not add meaningful value.\n\n";

        // Section 4: Research & Accuracy Requirements
        $systemPrompt .= "## Research & Accuracy Requirements\n\n"
            ."- Use accurate, current, and verifiable information.\n"
            ."- Prioritise authoritative and primary sources wherever possible.\n"
            ."- For brand-specific claims, services, products, features, statistics, awards, locations, pricing, or capabilities, rely only on verified client information or reliable sources.\n"
            ."- Never invent facts, statistics, features, services, quotes, research, awards, rankings, or claims.\n"
            ."- Clearly distinguish confirmed information from general industry guidance where necessary.\n"
            ."- Avoid unsupported superlatives such as \"best,\" \"No. 1,\" \"leading,\" \"top,\" or \"most trusted\" unless supported by verifiable evidence.\n"
            ."- For time-sensitive information, verify that the details are current before including them.\n";

        if (! empty($clientContext['approved_reference_domains'])) {
            $refDomains = is_array($clientContext['approved_reference_domains'])
                ? $clientContext['approved_reference_domains']
                : explode(',', $clientContext['approved_reference_domains']);
            $refDomains = array_filter(array_map('trim', $refDomains));
            if (! empty($refDomains)) {
                $systemPrompt .= "- When referencing external benchmarks or research, prioritize citing these approved domains: "
                    .implode(', ', $refDomains).".\n";
            }
        }
        $systemPrompt .= "\n";

        // Section 5: Structural & Formatting Requirements
        $systemPrompt .= "## Structural & Formatting Requirements\n\n"
            ."- Use semantic HTML throughout the article.\n"
            ."- Include exactly ONE `<h1>` main title containing the primary keyword naturally.\n"
            ."- Maintain a logical heading hierarchy using `<h2>` and `<h3>` tags.\n"
            ."- Do not duplicate `<h2>` or `<h3>` headings.\n"
            ."- Structure the article according to the topic and reader intent rather than forcing a fixed template.\n"
            ."- Use short paragraphs of approximately 2–4 sentences for readability.\n"
            ."- Use `<ul><li>` or `<ol><li>` lists when they make information easier to scan.\n"
            ."- Use `<table>` only when a comparison or structured presentation genuinely improves comprehension.\n"
            ."- Use **bold text** selectively for important terms, concepts, keywords, or takeaways. Do not overuse bold formatting.\n"
            ."- Avoid unnecessary sections, repetitive headings, or excessively fragmented content.\n"
            ."- Insert 2-3 HTML comment blocks formatted as: `<!-- IMAGE_PROMPT: Section Name | Alt Text | DALL-E/Midjourney Prompt -->` at key visual breaks.\n\n";

        // Section 6: Introduction
        $systemPrompt .= "## Introduction\n\n"
            ."- Open with a clear, engaging introduction that directly addresses the reader's concern, question, goal, or search intent.\n"
            ."- Introduce the primary keyword naturally within the opening section.\n"
            ."- Explain what the article will help the reader understand, decide, plan, or do.\n"
            ."- Avoid generic openings, broad clichés, and unnecessary background information.\n\n";

        // Section 7: Key Takeaways
        $systemPrompt .= "## Key Takeaways\n\n"
            ."Immediately after the introduction, include:\n"
            ."<div class=\"key-takeaways\">\n"
            ."Add 3–5 concise bullet points summarising the most useful insights from the article.\n"
            ."</div>\n"
            ."The key takeaways should provide quick value without simply repeating the introduction.\n\n";

        // Section 8: Contextual Internal Linking Directives
        $systemPrompt .= "## Contextual Internal Linking Directives\n\n"
            ."- Naturally add 1–3 contextual internal links within the body content to relevant client pages.\n"
            ."- Use descriptive, SEO-friendly anchor text that clearly reflects the topic or purpose of the linked page.\n"
            ."- Prefer natural partial-match, semantic, or contextually relevant anchor text.\n"
            ."- Avoid generic anchors such as \"click here,\" \"read more,\" or \"learn more.\"\n"
            ."- Avoid repetitive exact-match anchor text and keyword stuffing.\n"
            ."- Only link to pages that are genuinely relevant to the surrounding content.\n"
            ."- Do not invent or assume URLs. Use only verified client URLs.\n";

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
                $systemPrompt .= "Verified Client Pages Available for Contextual Linking:\n"
                    .implode("\n", $linksFormatted)."\n";
            }
        }
        $systemPrompt .= "\n";

        // Section 9: Editorial & Copywriting Rules
        $systemPrompt .= "## Editorial & Copywriting Rules\n\n"
            ."- **Narrative Perspective:** "
            .$this->formatPovInstruction($params['pov'] ?? null)."\n"
            ."- **Voice:** Use active voice wherever possible. Keep sentences direct, clear, and action-oriented. Use passive voice only when necessary for accuracy or natural flow.\n"
            ."- **Reader-Focused Guidance:** Frame information around what the reader can understand, consider, compare, expect, choose, explore, or do.\n"
            ."- **Practical Direction:** Provide useful and actionable guidance rather than purely descriptive information.\n"
            ."- **Natural Language:** Keep the writing conversational, professional, engaging, and easy to follow without sounding overly informal.\n"
            ."- **Clarity First:** Prefer simple, precise language over vague, generic, complex, or unnecessarily wordy phrasing.\n"
            ."- **Sentence Variety:** Vary sentence length and structure to maintain a natural rhythm and avoid robotic repetition.\n"
            ."- **Avoid Filler:** Remove unnecessary introductions, transitional padding, clichés, broad statements, and repetitive explanations.\n"
            ."- **Avoid Repetition:** Every section must introduce new value. Do not repeat the same facts, advice, keyword phrasing, examples, or arguments across the introduction, body, conclusion, CTA, and FAQs.\n"
            ."- **Natural SEO Writing:** Never compromise readability or accuracy simply to place a keyword.\n"
            ."- **Third-Person Usage:** Use third-person language only when required for factual explanation, brand references, subject-specific descriptions, or clarity.\n\n";

        // Section 10: Content Depth & Usefulness
        $systemPrompt .= "## Content Depth & Usefulness\n\n"
            ."- Explain important concepts clearly enough for the target reader to understand them without unnecessary jargon.\n"
            ."- Where specialist terminology is necessary, explain it in simple language.\n"
            ."- Address relevant questions, concerns, decision points, practical considerations, limitations, and next steps where appropriate.\n"
            ."- Include examples, comparisons, practical tips, steps, cautions, or scenarios only when they genuinely strengthen the article.\n"
            ."- Avoid surface-level summaries when deeper practical guidance would better satisfy the search intent.\n"
            ."- Ensure the article offers meaningful information beyond what could be obtained from a basic definition or generic overview.\n\n";

        // Section 11: Brand Integration
        $systemPrompt .= "## Brand Integration\n\n"
            ."- Integrate the client naturally where its products, services, expertise, resources, or solutions are genuinely relevant.\n"
            ."- Do not make every section promotional.\n"
            ."- Maintain an informative-first approach and introduce commercial messaging at appropriate decision points.\n"
            ."- Avoid repeating the brand name unnecessarily.\n\n";

        // Section 12: Conclusion & CTA
        $systemPrompt .= "## Conclusion & CTA\n\n"
            ."- Include a dedicated `<h2>Conclusion</h2>` or `<h2>Final Thoughts</h2>` section near the end.\n"
            ."- Summarise the article's main takeaway without repeating entire sections.\n"
            ."- Help the reader understand the logical next step.\n"
            ."- Follow the conclusion with a concise, relevant CTA aligned with the article topic and the client's offering.\n"
            ."- Keep the CTA natural, useful, and reader-focused rather than overly promotional.\n";

        if (! empty($clientContext['cta_default'])) {
            $ctaUrl = ! empty($clientContext['website_url']) ? htmlspecialchars($clientContext['website_url']) : '#';
            $ctaBrand = ! empty($clientContext['name']) ? htmlspecialchars($clientContext['name']) : 'our team';
            $ctaDirective = htmlspecialchars($clientContext['cta_default']);
            $systemPrompt .= "- Call-To-Action Box Markup: <div class=\"cta-box\"><h2>Ready to Take the Next Step?</h2><p>{$ctaDirective}</p><a href=\"{$ctaUrl}\" class=\"cta-button\">Get Started with {$ctaBrand}</a></div>.\n\n";
        } else {
            $systemPrompt .= "- Call-To-Action Box Markup: <div class=\"cta-box\"><h2>Ready to Take the Next Step?</h2><p>Apply these insights to accelerate your results.</p></div>.\n\n";
        }

        // Section 13: Frequently Asked Questions
        $systemPrompt .= "## Frequently Asked Questions\n\n"
            ."After the conclusion and CTA, include:\n"
            ."<h2>Frequently Asked Questions</h2>\n"
            ."- Add 5–6 high-value, general questions related to the topic using `<h3>` tags for questions and `<p>` tags for answers.\n"
            ."- Prioritise questions that address useful search queries, practical concerns, decision-making needs, or information not fully covered in the main body.\n"
            ."- Keep answers concise, accurate, and easy to understand.\n"
            ."- Do not repeat information already explained in the article.\n"
            ."- Avoid creating FAQs solely to insert additional keywords.\n\n";

        // Section 14: Metadata Output Block
        $systemPrompt .= "## Metadata Output Block\n\n"
            ."At the very top of the output, before the `<h1>`, include a JSON block enclosed in:\n"
            ."```json_metadata\n"
            ."{\n"
            ."  \"meta_title\": \"SEO title under 60 characters containing the primary keyword naturally\",\n"
            ."  \"meta_description\": \"Compelling meta description under 160 characters containing the primary keyword and a natural CTA\",\n"
            ."  \"url_slug\": \"clean-descriptive-url-slug\",\n"
            ."  \"primary_keyword\": \"{$primaryKeyword}\",\n"
            ."  \"faq_schema\": [\n"
            ."    {\n"
            ."      \"question\": \"...\",\n"
            ."      \"answer\": \"...\"\n"
            ."    }\n"
            ."  ]\n"
            ."}\n"
            ."```\n\n"
            ."### Metadata Guidelines:\n"
            ."- Keep the meta title clear, relevant, compelling, and aligned with search intent.\n"
            ."- Include the primary keyword naturally without forcing it.\n"
            ."- Keep the meta description informative and persuasive while accurately reflecting the article.\n"
            ."- Create a concise, readable URL slug using the main topic or primary keyword.\n"
            ."- Ensure the FAQ schema exactly matches the FAQs included in the article.\n\n";

        // Section 15: Final Quality Check
        $systemPrompt .= "## Final Quality Check\n\n"
            ."Before producing the final article, ensure that:\n"
            ."- The article fully satisfies the search intent.\n"
            ."- All factual statements are accurate and supportable.\n"
            ."- The primary keyword appears naturally in important SEO locations.\n"
            ."- Secondary keywords are used only where relevant.\n"
            ."- No keyword stuffing is present.\n"
            ."- The article contains no fabricated information.\n"
            ."- Internal links are relevant and use natural anchor text.\n"
            ."- Heading hierarchy is logical and non-repetitive.\n"
            ."- Each section adds distinct value.\n"
            ."- The content is reader-focused, practical, and easy to scan.\n"
            ."- The brand is integrated naturally without excessive promotion.\n"
            ."- The conclusion, CTA, and FAQs do not unnecessarily repeat the body content.\n"
            ."- The final article reads like expert human-written content rather than a formulaic SEO template.\n";

        $topic = $params['topic'] ?? '';
        $competitorStr = ! empty($competitors) ? implode(', ', $competitors) : 'None';

        $userPrompt = "Topic: {$topic}\n"
            ."Primary Keyword: {$primaryKeyword}\n"
            ."Secondary Keywords: {$secondaryKeywords}\n"
            ."Search Intent: {$searchIntent}\n"
            ."Article Format: {$articleType}\n"
            ."Target Audience: {$targetAudience}\n"
            .(! empty($industry) ? "Target Industry: {$industry}\n" : '')
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
            ."- Include a 'faq' section near the end with 5-6 high-value questions and concise answers.\n"
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
        $rawTone = $params['tone'] ?? null;
        if (empty($rawTone) || $rawTone === 'client_default') {
            $tone = $clientContext['brand_tone'] ?? 'Professional & Authoritative';
        } else {
            $tone = $rawTone;
        }
        $targetAudience = !empty($params['target_audience']) ? $params['target_audience'] : ($clientContext['target_audience'] ?? 'General Audience');
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
            $systemPrompt .= "  - Render 5-6 high-value Frequently Asked Questions with short, authoritative answers using H3 tags for questions and paragraphs for answers.\n";
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
