<?php

namespace App\Services;

use App\Models\AiPromptTemplate;
use App\Models\Client;
use Illuminate\Support\Str;

class WireframeBriefEngine
{
    /**
     * Step 5: Generate SEO Brief, Wireframe, SEO Metadata, Brand Headings, Placement Map & System Prompt
     */
    public function generateBrief(Client $client, array $inputs): array
    {
        $contentType = $inputs['content_type'] ?? 'onpage_blog';
        $topic = $inputs['topic_name'] ?? 'Health & Wellness Guide';
        $primaryKw = $inputs['primary_keyword'] ?? strtolower($topic);
        $brandName = $client->name;
        $tone = $client->brand_tone ?? 'Conversational, Authoritative';
        $audience = $client->target_audience ?? 'General Audience';
        $cta = $client->cta_default ?? "Contact {$brandName} today.";

        // Feature 1: Word Count & Reading Complexity Controls
        $wordCount = intval($inputs['suggested_word_count'] ?? 1800);
        if ($wordCount <= 0) {
            $wordCount = 1800;
        }

        $readingLevelKey = $inputs['reading_level'] ?? 'professional';
        $readingLevelLabel = match ($readingLevelKey) {
            'conversational' => 'Conversational & Easy Read (Flesch Score 70-80, Grade 7-8)',
            'technical' => 'Technical & Industry Expert (Flesch Score 40-50, Grade 12+)',
            default => 'Professional & Authoritative (Flesch Score 55-65, Grade 9-11)',
        };

        // Feature 3: Live Client Sitemap XML Internal Linking Strategy
        $sitemapService = app(SitemapCrawlerService::class);
        $relevantSitemapLinks = $sitemapService->getRelevantSitemapLinks($client, $topic, 3);

        $internalLinkInstruction = '';
        if (! empty($relevantSitemapLinks)) {
            $internalLinkInstruction = "SITEMAP XML INTERNAL LINKING DIRECTIVE: Naturally embed 2-3 internal hyperlinks into body paragraphs using descriptive anchor text pointing to these actual client sitemap URLs:\n";
            foreach ($relevantSitemapLinks as $slink) {
                $internalLinkInstruction .= "- URL: {$slink['url']} (Target Page Topic: '{$slink['title']}')\n";
            }
        } else {
            $internalLinkInstruction = 'INTERNAL LINKING DIRECTIVE: Include 2 internal links pointing to '.rtrim($client->website_url, '/')."/services.\n";
        }

        // 1. SEO Metadata Outputs
        $slug = Str::slug($primaryKw);
        $seoTitle = "{$topic} | {$brandName}";
        $metaTitle = Str::limit("{$topic}: Complete Guide & Expert Tips | {$brandName}", 58, '');
        $metaDescription = Str::limit("Discover essential insights on {$primaryKw}. Learn expert recommendations, symptoms, and solutions from {$brandName}.", 155, '');
        $canonicalUrl = rtrim($client->website_url, '/').'/blog/'.$slug;

        // 2. Brand-Aware Heading Logic
        $brandHeadingH2 = "Expert Care & Solutions at {$brandName}";
        $brandHeadingH3 = "Why Patients Choose {$brandName}";

        // 3. 13-Location Keyword Placement Strategy Map
        $placementMap = [
            '1_meta_title' => "Include '{$primaryKw}' within first 30 characters.",
            '2_meta_description' => "Include '{$primaryKw}' and 1 secondary keyword.",
            '3_url_slug' => "Clean path /blog/{$slug}",
            '4_h1_title' => "Exact match '{$primaryKw}' in H1.",
            '5_first_100_words' => "State '{$primaryKw}' in opening paragraph naturally.",
            '6_h2_subheadings' => 'Embed secondary keywords in H2 subheadings.',
            '7_h3_subheadings' => 'Embed long-tail & PAA question keywords in H3.',
            '8_body_paragraphs' => 'Maintain 1.2% - 1.8% keyword density.',
            '9_image_alt_tags' => 'Use descriptive alt tags with keyword variations.',
            '10_bullet_lists' => 'Feature LSI keywords in key takeaway bullet lists.',
            '11_internal_links' => "Link 2-3 related blog URLs on {$client->website_url}.",
            '12_faq_section' => 'Include min 4 Q&A pairs with FAQPage Schema markup.',
            '13_cta_closing' => 'Incorporate brand CTA naturally in final conclusion.',
        ];

        // 4. Content Type Specific Wireframe Behaviors (9 Types)
        $wireframeStructure = match ($contentType) {
            'landing_page' => [
                'hero_section' => "H1 Headline with {$primaryKw} + High-impact Subheadline + Primary CTA Button",
                'problem_statement' => "Identify pain points faced by {$audience}",
                'solution_breakdown' => "Introduce {$brandName} solutions & core benefits",
                'social_proof' => 'Client testimonials, ratings, & medical certifications',
                'pricing_or_plans' => 'Transparent breakdown of plans/services',
                'faq_section' => 'Top 5 conversion questions & answers',
                'final_cta' => $cta,
            ],
            'pr_article' => [
                'dateline_lead' => "CITY, State — Release summary introducing {$topic}",
                'executive_quote' => "Official quote from {$brandName} leadership",
                'market_impact' => 'Industry data & statistical benchmarks',
                'boilerplate' => "About {$brandName} official background block",
            ],
            'ad_copy' => [
                'headlines' => ["Best {$primaryKw} | {$brandName}", "Top Rated {$topic}", "Get Expert {$primaryKw}"],
                'descriptions' => ["Looking for {$primaryKw}? Discover proven results with {$brandName}. {$cta}"],
                'call_to_action' => $cta,
            ],
            'faq_page' => [
                'h1' => "Frequently Asked Questions About {$topic}",
                'categories' => ['General Overview', 'Symptoms & Causes', "Treatment Options at {$brandName}", 'Costs & Appointments'],
            ],
            'service_page' => [
                'hero' => "{$primaryKw} Services at {$brandName}",
                'service_overview' => 'Detailed service breakdown & technology used',
                'brand_heading' => $brandHeadingH2,
                'faq_section' => "4 Key questions regarding {$primaryKw} services",
                'cta' => $cta,
            ],
            default => [ // onpage_blog
                'h1' => $topic,
                'introduction' => "Hook reader, introduce {$primaryKw}, and state key takeaways for {$audience}.",
                'h2_section_1' => "Understanding {$primaryKw}: Causes & Fundamentals",
                'h2_section_2' => 'Top 5 Signs & Symptoms to Watch For',
                'h2_brand' => $brandHeadingH2,
                'h3_brand' => $brandHeadingH3,
                'faq_section' => 'Frequently Asked Questions (min 4 questions)',
                'conclusion' => 'Summary of key takeaways & closing call to action.',
            ],
        };

        $contentTypeLabel = match ($contentType) {
            'landing_page' => 'High-Converting Landing Page',
            'pr_article' => 'PR Press Release Article',
            'ad_copy' => 'Ad Copy & Search Headlines',
            'faq_page' => 'Dedicated FAQ Resource Page',
            'service_page' => 'Commercial Service Page',
            default => 'On-Page SEO Blog Post',
        };

        // Feature 2: Schema Markup Directive
        $schemaDirective = "JSON-LD SCHEMA INSTRUCTION: Automatically append valid, formatted <script type=\"application/ld+json\"> markup at the bottom of the HTML output. Include FAQPage schema (for Q&A items) and Article schema (with author, publisher '{$brandName}', datePublished).";

        // Build Archetype-Specific Structural Wireframe Directive
        $wireframeInstruction = 'TARGET STRUCTURAL LAYOUT ('.strtoupper($contentTypeLabel)."):\n";
        foreach ($wireframeStructure as $key => $val) {
            $label = ucwords(str_replace('_', ' ', $key));
            if (is_array($val)) {
                $wireframeInstruction .= "- {$label}: ".implode(', ', $val)."\n";
            } else {
                $wireframeInstruction .= "- {$label}: {$val}\n";
            }
        }

        // 5. Query DB Prompt Template & Compile Dynamic Placeholders
        $dbTemplate = null;
        if (! empty($inputs['prompt_template_id'])) {
            $dbTemplate = AiPromptTemplate::where('id', $inputs['prompt_template_id'])
                ->where('is_active', true)
                ->first();
        }

        if (! $dbTemplate) {
            $dbTemplate = AiPromptTemplate::where('archetype_key', $contentType)
                ->where('is_active', true)
                ->first();
        }

        if ($dbTemplate && ! $dbTemplate->is_system) {
            $contentTypeLabel = $dbTemplate->archetype_name;
        }

        $variables = [
            'brand_name' => $brandName,
            'audience' => $audience,
            'tone' => $tone,
            'content_type_label' => $contentTypeLabel,
            'primary_keyword' => $primaryKw,
            'wireframe_layout' => $wireframeInstruction,
            'word_count' => $wordCount,
            'reading_level' => $readingLevelLabel,
            'seo_title' => $seoTitle,
            'meta_description' => $metaDescription,
            'slug' => $slug,
            'brand_heading' => $brandHeadingH2,
            'internal_links' => $internalLinkInstruction,
            'cta' => $cta,
            'schema_directive' => $schemaDirective,
        ];

        if ($dbTemplate) {
            $intelligentPrompt = $dbTemplate->compile($variables);
        } else {
            // Built-in fallback template if DB record missing
            $intelligentPrompt = "You are an elite E-E-A-T SEO Copywriter writing for {$brandName}.\n"
                ."TARGET AUDIENCE: {$audience}\n"
                ."BRAND TONE: {$tone}\n"
                ."CONTENT TYPE: {$contentTypeLabel}\n"
                ."PRIMARY KEYWORD: {$primaryKw}\n"
                ."{$wireframeInstruction}"
                ."STRICT WORD COUNT MANDATE: The generated article MUST BE AT LEAST {$wordCount} words (±5%). Do NOT truncate or output a short summary. Expand every section with deep analytical explanations, real-world examples, bullet lists, and detailed body paragraphs to strictly meet the {$wordCount} words target.\n"
                ."READING COMPLEXITY: {$readingLevelLabel}\n"
                ."SEO METADATA:\n"
                ."- Title: {$seoTitle}\n"
                ."- Meta Description: {$metaDescription}\n"
                ."- URL Slug: {$slug}\n"
                ."BRAND HEADING INSTRUCTION: Include '{$brandHeadingH2}' as a prominent H2.\n"
                ."KEYWORD PLACEMENT MAP: Follow all 13 structural locations strictly.\n"
                ."{$internalLinkInstruction}"
                ."CTA INSTRUCTION: End with exact CTA: '{$cta}'.\n"
                ."{$schemaDirective}";
        }

        return [
            'prompt_template_id' => $dbTemplate ? $dbTemplate->id : null,
            'prompt_template_name' => $dbTemplate ? $dbTemplate->archetype_name : null,
            'seo_title' => $seoTitle,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'url_slug' => $slug,
            'canonical_url' => $canonicalUrl,
            'suggested_word_count' => $wordCount,
            'reading_level' => $readingLevelKey,
            'wireframe_structure' => $wireframeStructure,
            'brand_heading_rules' => [
                'h2_brand' => $brandHeadingH2,
                'h3_brand' => $brandHeadingH3,
            ],
            'keyword_placement_map' => $placementMap,
            'intelligent_prompt' => $intelligentPrompt,
        ];
    }
}
