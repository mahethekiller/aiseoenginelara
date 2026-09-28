<?php

namespace App\Services;

use App\Models\ContentBrief;
use App\Models\PublishingRecord;
use App\Models\TopicDiscovery;
use Illuminate\Support\Str;

class ExistingBlogDatabaseService
{
    /**
     * Step 2: Cross-check Client database to prevent topic duplication & cannibalization
     */
    public function isDuplicateTopic(int $clientId, string $topicName): bool
    {
        $slug = Str::slug($topicName);

        // 1. Check existing topic discoveries
        $existingTopic = TopicDiscovery::where('client_id', $clientId)
            ->where('topic_name', 'LIKE', "%{$topicName}%")
            ->exists();

        if ($existingTopic) {
            return true;
        }

        // 2. Check existing briefs
        $existingBrief = ContentBrief::where('client_id', $clientId)
            ->where(function ($query) use ($topicName, $slug) {
                $query->where('working_title', 'LIKE', "%{$topicName}%")
                    ->orWhere('url_slug', 'LIKE', "%{$slug}%");
            })->exists();

        if ($existingBrief) {
            return true;
        }

        // 3. Check published articles
        $existingPublished = PublishingRecord::where('client_id', $clientId)
            ->where('title', 'LIKE', "%{$topicName}%")
            ->exists();

        return $existingPublished;
    }
}
