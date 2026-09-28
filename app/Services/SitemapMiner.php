<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class SitemapMiner
{
    public function mineLinks(string $sitemapUrl): array
    {
        try {
            $response = Http::get($sitemapUrl);
            if ($response->failed()) {
                return [];
            }

            $xml = new SimpleXMLElement($response->body());
            $urls = [];
            foreach ($xml->url as $urlElement) {
                if (isset($urlElement->loc)) {
                    $urls[] = (string) $urlElement->loc;
                }
            }

            return $urls;
        } catch (\Exception $e) {
            return [];
        }
    }
}
