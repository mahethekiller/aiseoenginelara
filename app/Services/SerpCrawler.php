<?php

namespace App\Services;

use DOMDocument;
use Illuminate\Support\Facades\Http;

class SerpCrawler
{
    public function fetchCompetitors(string $topic): array
    {
        // Simple organic scraper using DuckDuckGo HTML mode as a fallback
        $query = urlencode($topic);
        $url = 'https://html.duckduckgo.com/html/?q='.$query;

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(4)->get($url);

            if ($response->failed()) {
                return [];
            }

            $dom = new DOMDocument;
            libxml_use_internal_errors(true);
            $dom->loadHTML($response->body());
            libxml_clear_errors();

            $links = [];
            $elements = $dom->getElementsByTagName('a');
            foreach ($elements as $el) {
                $href = $el->getAttribute('href');
                if (str_contains($href, 'uddg=')) {
                    $parts = explode('uddg=', $href);
                    if (isset($parts[1])) {
                        $links[] = urldecode(explode('&', $parts[1])[0]);
                    }
                }
                if (count($links) >= 5) {
                    break;
                }
            }

            return $links;
        } catch (\Exception $e) {
            return [];
        }
    }
}
