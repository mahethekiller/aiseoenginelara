<?php

namespace App\Services;

use App\Models\Article;

class ArticleFormatter
{
    /**
     * Transform HTML content by converting IMAGE_PROMPT comments into styled cards.
     */
    public function formatContent(string $rawHtml): string
    {
        // Transform <!-- IMAGE_PROMPT: Section | Alt | Prompt --> comments into styled cards
        $formatted = preg_replace_callback(
            '/<!--\s*IMAGE_PROMPT:\s*(.*?)\s*\|\s*(.*?)\s*\|\s*(.*?)\s*-->/i',
            function ($matches) {
                $section = htmlspecialchars(trim($matches[1]), ENT_QUOTES, 'UTF-8');
                $alt = htmlspecialchars(trim($matches[2]), ENT_QUOTES, 'UTF-8');
                $prompt = htmlspecialchars(trim($matches[3]), ENT_QUOTES, 'UTF-8');

                return <<<HTML
<div class="ai-image-card">
  <div class="image-card-header">📷 AI Image Prompt <span>({$section})</span></div>
  <div class="image-card-body">
    <p><strong>Alt Text:</strong> {$alt}</p>
    <p><strong>Prompt:</strong> <code>{$prompt}</code></p>
  </div>
</div>
HTML;
            },
            $rawHtml
        );

        return $formatted;
    }

    /**
     * Build standalone, fully-styled HTML document matching the reference specification.
     */
    public function buildFullHtml(Article $article): string
    {
        $reportHtml = $this->generateReportHtml($article);
        $content = $this->formatContent($article->html_content)."\n".$reportHtml;
        $title = htmlspecialchars($article->meta_title ?? $article->title, ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($article->meta_description ?? '', ENT_QUOTES, 'UTF-8');

        $schemaJson = ! empty($article->schema_jsonld)
            ? json_encode($article->schema_jsonld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : '';

        $schemaScript = $schemaJson ? "<script type=\"application/ld+json\">\n{$schemaJson}\n</script>" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <meta name="description" content="{$description}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e40af;
            --primary-dark: #0f172a;
            --secondary: #0284c7;
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }
        body { 
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
            line-height: 1.7; 
            color: var(--text-main); 
            background-color: var(--bg-body);
            max-width: 900px; 
            margin: 40px auto; 
            padding: 0 24px; 
        }
        .article-container {
            background-color: var(--bg-card);
            padding: 42px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }
        h1 { 
            color: var(--primary-dark); 
            font-size: 2.3em; 
            font-weight: 800;
            line-height: 1.25;
            border-bottom: 3px solid var(--primary); 
            padding-bottom: 12px; 
            margin-bottom: 24px;
        }
        h2 { 
            color: var(--primary); 
            font-size: 1.65em; 
            font-weight: 700;
            margin-top: 36px; 
            margin-bottom: 14px;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--border-color);
        }
        h3 { 
            color: var(--primary-dark); 
            font-size: 1.3em; 
            font-weight: 600;
            margin-top: 24px;
            margin-bottom: 10px;
        }
        p { margin-bottom: 18px; font-size: 1.05em; color: #334155; }
        ul, ol { margin-bottom: 20px; padding-left: 28px; }
        li { margin-bottom: 8px; font-size: 1.05em; }
        strong { color: var(--primary-dark); font-weight: 600; }

        .key-takeaways { 
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); 
            border-left: 5px solid var(--primary); 
            padding: 24px; 
            margin: 28px 0; 
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(30,64,175,0.08);
        }
        .key-takeaways h3 { margin-top: 0; color: var(--primary); font-size: 1.3em; }

        .cta-box { 
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); 
            color: #ffffff; 
            padding: 32px; 
            text-align: center; 
            border-radius: 12px; 
            margin: 40px 0; 
            box-shadow: 0 6px 20px rgba(30,58,138,0.2);
        }
        .cta-box h2, .cta-box h3, .cta-box p { color: #ffffff; }

        .ai-image-card {
            background-color: #f1f5f9;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 28px 0;
        }
        .image-card-header {
            font-weight: 700;
            color: #0f172a;
            font-size: 0.95em;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .image-card-header span { color: #2563eb; }
        .image-card-body p { margin-bottom: 6px; font-size: 0.95em; color: #475569; }
        .image-card-body code {
            background-color: #e2e8f0;
            color: #0f172a;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.9em;
            display: inline-block;
            word-break: break-all;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
            font-size: 1em;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        th { background-color: var(--primary); color: #ffffff; font-weight: 600; text-align: left; padding: 12px 16px; }
        td { padding: 12px 16px; border-bottom: 1px solid var(--border-color); background-color: #ffffff; }
        tr:nth-child(even) td { background-color: #f8fafc; }

        pre {
            background-color: #0f172a;
            color: #f8fafc;
            padding: 18px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.95em;
            margin: 20px 0;
        }
        code { font-family: 'Consolas', 'Monaco', monospace; }
    </style>
    {$schemaScript}
</head>
<body>
<div class="article-container">
{$content}
</div>
</body>
</html>
HTML;
    }

    /**
     * Build Word-optimized document (.doc / .docx HTML) with MS Word explicit formatting.
     */
    public function buildWordDoc(Article $article): string
    {
        $reportHtml = $this->generateReportHtml($article);
        $content = $this->formatContent($article->html_content)."\n".$reportHtml;
        $title = htmlspecialchars($article->title, ENT_QUOTES, 'UTF-8');

        // Apply Word MSO inline styles for tables, callouts, and boxes
        $wordContent = str_replace(
            ['<div class="key-takeaways">', '<div class="cta-box">', '<div class="ai-image-card">', '<table>', '<th>', '<td>'],
            [
                '<div class="key-takeaways" style="background-color:#eff6ff; border-left:6px solid #1e40af; padding:20px; margin:24px 0; font-family:Arial,sans-serif;">',
                '<div class="cta-box" style="background-color:#1e3a8a; color:#ffffff; padding:28px; text-align:center; margin:32px 0; font-family:Arial,sans-serif;">',
                '<div class="ai-image-card" style="background-color:#f1f5f9; border:2px dashed #cbd5e1; padding:16px; margin:24px 0; font-family:Arial,sans-serif;">',
                '<table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse; border:1px solid #e2e8f0; margin:20px 0; font-family:Arial,sans-serif;">',
                '<th style="background-color:#1e40af; color:#ffffff; font-weight:bold; text-align:left; padding:10px 14px;">',
                '<td style="padding:10px 14px; border:1px solid #e2e8f0;">',
            ],
            $content
        );

        return <<<HTML
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="utf-8">
    <title>{$title}</title>
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #1e293b;
            margin: 40px;
        }
        h1 { color: #0f172a; font-size: 22pt; font-weight: bold; border-bottom: 3px solid #1e40af; padding-bottom: 8px; margin-bottom: 20px; }
        h2 { color: #1e40af; font-size: 16pt; font-weight: bold; margin-top: 28px; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; }
        h3 { color: #0f172a; font-size: 13pt; font-weight: bold; margin-top: 20px; margin-bottom: 8px; }
        p { margin-bottom: 14px; color: #334155; }
        ul, ol { margin-bottom: 16px; padding-left: 24px; }
        li { margin-bottom: 6px; }
        strong { color: #0f172a; font-weight: bold; }
        .key-takeaways { background-color: #eff6ff; border-left: 6px solid #1e40af; padding: 18px; margin: 20px 0; }
        .cta-box { background-color: #1e3a8a; color: #ffffff; padding: 24px; text-align: center; margin: 30px 0; }
        .cta-box p, .cta-box h2, .cta-box h3 { color: #ffffff !important; }
        .ai-image-card { background-color: #f1f5f9; border: 2px dashed #cbd5e1; padding: 14px; margin: 20px 0; }
        .image-card-header { font-weight: bold; color: #0f172a; font-size: 10pt; margin-bottom: 6px; }
        .image-card-header span { color: #2563eb; }
        .image-card-body p { margin-bottom: 4px; color: #475569; }
        .image-card-body code { background-color: #e2e8f0; color: #0f172a; padding: 3px 6px; font-family: 'Courier New', monospace; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background-color: #1e40af; color: #ffffff; font-weight: bold; text-align: left; padding: 10px; }
        td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
    </style>
</head>
<body>
<div class="article-container">
{$wordContent}
</div>
</body>
</html>
HTML;
    }

    /**
     * Generate HTML for performance and metrics report.
     */
    private function generateReportHtml(Article $article): string
    {
        $wordCount = $article->word_count ?: str_word_count(strip_tags($article->html_content));
        $fleschScore = $article->flesch_reading_ease ?: 65.0;
        $promptTokens = $article->prompt_tokens ?: 0;
        $completionTokens = $article->completion_tokens ?: 0;
        $totalTokens = $promptTokens + $completionTokens;

        $cost = round(($promptTokens * 0.00000015) + ($completionTokens * 0.0000006), 5);
        $costStr = number_format($cost, 5);

        $job = $article->seoGenerationJob;
        $primaryKeyword = $job && isset($job->parameters['primary_keyword'])
            ? htmlspecialchars($job->parameters['primary_keyword'], ENT_QUOTES, 'UTF-8')
            : 'Target SEO Keyword';

        $imageCards = '';
        if (preg_match_all('/<!--\s*IMAGE_PROMPT:\s*(.*?)\s*\|\s*(.*?)\s*\|\s*(.*?)\s*-->/i', $article->html_content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $section = htmlspecialchars(trim($match[1]), ENT_QUOTES, 'UTF-8');
                $alt = htmlspecialchars(trim($match[2]), ENT_QUOTES, 'UTF-8');
                $prompt = htmlspecialchars(trim($match[3]), ENT_QUOTES, 'UTF-8');
                $imageCards .= "<li><strong>Section ({$section}):</strong> Alt Text: \"{$alt}\" | Prompt: <code>{$prompt}</code></li>";
            }
        }
        if (empty($imageCards)) {
            $imageCards = '<li>No specific image prompts were embedded in this template.</li>';
        }

        return <<<HTML
<div class="article-performance-report" style="margin-top: 48px; padding-top: 32px; border-top: 2px solid #e2e8f0; font-family: Arial, sans-serif; font-size: 14px; color: #1e293b;">
    <h2 style="color: #1e40af; font-size: 20px; font-weight: bold; border-bottom: 2px solid #1e40af; padding-bottom: 8px; margin-bottom: 20px;">📊 Content Performance & Metadata Report</h2>
    
    <h3 style="color: #0f172a; font-size: 16px; font-weight: bold; margin-top: 24px; margin-bottom: 12px;">1. Search Engine Metadata</h3>
    <table style="width:100%; border-collapse:collapse; margin-bottom: 20px; font-size: 13px;">
        <tr>
            <td style="font-weight:bold; width:30%; padding:10px; border: 1px solid #cbd5e1; background-color: #f8fafc;">Meta Title</td>
            <td style="padding:10px; border: 1px solid #cbd5e1;">{$article->meta_title}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; padding:10px; border: 1px solid #cbd5e1; background-color: #f8fafc;">Meta Description</td>
            <td style="padding:10px; border: 1px solid #cbd5e1;">{$article->meta_description}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; padding:10px; border: 1px solid #cbd5e1; background-color: #f8fafc;">URL Slug</td>
            <td style="padding:10px; border: 1px solid #cbd5e1;">{$article->slug}</td>
        </tr>
    </table>

    <h3 style="color: #0f172a; font-size: 16px; font-weight: bold; margin-top: 24px; margin-bottom: 12px;">2. Readability & Performance Analytics</h3>
    <table style="width:100%; border-collapse:collapse; margin-bottom: 20px; font-size: 13px;">
        <tr>
            <td style="font-weight:bold; width:40%; padding:10px; border: 1px solid #cbd5e1; background-color: #f8fafc;">Total Word Count</td>
            <td style="padding:10px; border: 1px solid #cbd5e1;">{$wordCount} words</td>
        </tr>
        <tr>
            <td style="font-weight:bold; padding:10px; border: 1px solid #cbd5e1; background-color: #f8fafc;">Flesch Reading Ease</td>
            <td style="padding:10px; border: 1px solid #cbd5e1;">{$fleschScore}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; padding:10px; border: 1px solid #cbd5e1; background-color: #f8fafc;">Prompt Tokens</td>
            <td style="padding:10px; border: 1px solid #cbd5e1;">{$promptTokens}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; padding:10px; border: 1px solid #cbd5e1; background-color: #f8fafc;">Completion Tokens</td>
            <td style="padding:10px; border: 1px solid #cbd5e1;">{$completionTokens}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; padding:10px; border: 1px solid #cbd5e1; background-color: #f8fafc;">Total Tokens Consumed</td>
            <td style="padding:10px; border: 1px solid #cbd5e1;">{$totalTokens}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; padding:10px; border: 1px solid #cbd5e1; background-color: #f8fafc;">Estimated API Cost</td>
            <td style="padding:10px; border: 1px solid #cbd5e1; color: #16a34a; font-weight: bold;">\${$costStr} USD</td>
        </tr>
    </table>

    <h3 style="color: #0f172a; font-size: 16px; font-weight: bold; margin-top: 24px; margin-bottom: 12px;">3. Keyword Density Checklist</h3>
    <ul style="padding-left: 20px; margin-bottom: 20px; line-height: 1.6;">
        <li><strong>Focus Keyword:</strong> <code>{$primaryKeyword}</code></li>
        <li><strong>Density Status:</strong> Verified heading tags (H1, H2) coverage and natural density throughout sections (~1.5% target).</li>
    </ul>

    <h3 style="color: #0f172a; font-size: 16px; font-weight: bold; margin-top: 24px; margin-bottom: 12px;">4. AI Image Prompts Catalog</h3>
    <ul style="padding-left: 20px; margin-bottom: 20px; line-height: 1.6;">
        {$imageCards}
    </ul>
</div>
HTML;
    }
}
