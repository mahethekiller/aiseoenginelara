<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;

class LayoutPreservingRewriter
{
    protected MultiProviderLlmClient $llmClient;

    protected PromptBuilder $promptBuilder;

    protected string $cleanOriginalHtml = '';

    protected int $promptTokens = 0;

    protected int $completionTokens = 0;

    public function __construct(MultiProviderLlmClient $llmClient)
    {
        $this->llmClient = $llmClient;
        $this->promptBuilder = new PromptBuilder;
    }

    public function getCleanOriginalHtml(): string
    {
        return $this->cleanOriginalHtml;
    }

    public function getPromptTokens(): int
    {
        return $this->promptTokens;
    }

    public function getCompletionTokens(): int
    {
        return $this->completionTokens;
    }

    public function rewriteUrl(string $url, ?string $customInstructions = null, string $mode = 'layout-preserving'): string
    {
        $response = Http::timeout(240)->withOptions([
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ],
        ])->get($url);
        if ($response->failed()) {
            throw new \Exception("Failed to download URL: $url");
        }
        $rawHtml = $response->body();

        if ($mode === 'semantic-clean') {
            return $this->rewriteSemanticClean($url, $rawHtml, $customInstructions);
        }

        return $this->rewriteLayoutPreserving($rawHtml, $customInstructions);
    }

    protected function rewriteSemanticClean(string $url, string $rawHtml, ?string $customInstructions = null): string
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($rawHtml, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $elementsToStrip = $xpath->query('//script | //style | //noscript | //svg | //iframe | //header | //footer | //nav | //aside | //img');
        foreach ($elementsToStrip as $element) {
            $element->parentNode->removeChild($element);
        }

        $this->cleanOriginalHtml = $dom->saveHTML();

        $body = $dom->getElementsByTagName('body')->item(0);
        $rawText = $body ? $body->textContent : strip_tags($rawHtml);
        $rawText = preg_replace('/\s+/', ' ', trim($rawText));

        $prompts = $this->promptBuilder->buildSemanticCleanRewriterPrompt($url, substr($rawText, 0, 10000), $customInstructions);
        $result = $this->llmClient->generateText($prompts['system'], $prompts['user']);
        $this->promptTokens = $result['prompt_tokens'] ?? 0;
        $this->completionTokens = $result['completion_tokens'] ?? 0;

        $parsed = $this->promptBuilder->parseMetadataAndHtml($result['text']);

        return $parsed['html'];
    }

    protected function rewriteLayoutPreserving(string $rawHtml, ?string $customInstructions = null): string
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($rawHtml, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $elementsToStrip = $xpath->query('//script | //style | //noscript | //svg | //iframe | //header | //footer | //nav | //aside | //img');
        foreach ($elementsToStrip as $element) {
            $element->parentNode->removeChild($element);
        }

        $this->cleanOriginalHtml = $dom->saveHTML();

        $textNodes = [];
        $textMap = [];
        $textIndex = 0;

        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            $this->mapTextNodes($body, $textNodes, $textMap, $textIndex);
        }

        if (empty($textMap)) {
            return $dom->saveHTML();
        }

        $systemPrompt = $this->promptBuilder->buildLayoutPreservingRewriterPrompt($customInstructions);
        $userPrompt = "Rewrite the text content of the following JSON key-value map into distinct, creative copy while preserving the exact JSON keys and structure:\n"
            .json_encode($textMap, JSON_UNESCAPED_UNICODE);

        $result = $this->llmClient->generateText($systemPrompt, $userPrompt);
        $this->promptTokens = $result['prompt_tokens'] ?? 0;
        $this->completionTokens = $result['completion_tokens'] ?? 0;

        // Extract JSON if wrapped in markdown
        $jsonText = $result['text'];
        if (preg_match('/```json\s*(.*?)\s*```/s', $jsonText, $matches)) {
            $jsonText = $matches[1];
        }

        $rewrittenMap = json_decode($jsonText, true);

        if (is_array($rewrittenMap)) {
            foreach ($textNodes as $index => $node) {
                if (isset($rewrittenMap[$index])) {
                    $node->nodeValue = htmlspecialchars($rewrittenMap[$index], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }
        }

        return $dom->saveHTML();
    }

    protected function mapTextNodes($node, &$textNodes, &$textMap, &$textIndex)
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $parent = $node->parentNode;
            if ($parent) {
                $parentName = strtolower($parent->nodeName);
                if (in_array($parentName, ['script', 'style', 'noscript', 'code', 'pre', 'textarea', 'head', 'html'])) {
                    return;
                }
            }

            $text = trim($node->nodeValue);
            if (! empty($text) && strlen($text) > 3) {
                $textNodes[$textIndex] = $node;
                $textMap[$textIndex] = $text;
                $textIndex++;
            }
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $this->mapTextNodes($child, $textNodes, $textMap, $textIndex);
            }
        }
    }
}
