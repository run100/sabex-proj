<?php

namespace App\Services\Seo;

/**
 * Wiki topic collector contract. Catalog pages read existing seo_items fields;
 * Admin Abuse schedule data is refreshed independently from community sources.
 */
final class SabWikiTopicSourceContract
{
    /**
     * @return array<string, array{page: string, source_url: ?string, fields: list<string>, ttl_hours: int, confirmed_lock: string, status: string}>
     */
    public static function topics(): array
    {
        return [
            'lucky-blocks' => [
                'page' => SabWikiPageDefinitions::TOPIC_PAGE_SLUGS['lucky-blocks'],
                'source_url' => null,
                'fields' => ['obtain_method', 'drop_table'],
                'ttl_hours' => 24,
                'confirmed_lock' => 'manual_update',
                'status' => 'deferred',
            ],
            'fusions' => [
                'page' => SabWikiPageDefinitions::TOPIC_PAGE_SLUGS['fusions'],
                'source_url' => null,
                'fields' => ['obtain_method', 'recipe'],
                'ttl_hours' => 24,
                'confirmed_lock' => 'manual_update',
                'status' => 'deferred',
            ],
            'rebirths' => [
                'page' => SabWikiPageDefinitions::TOPIC_PAGE_SLUGS['rebirths'],
                'source_url' => null,
                'fields' => ['obtain_method', 'rebirth_level'],
                'ttl_hours' => 24,
                'confirmed_lock' => 'manual_update',
                'status' => 'deferred',
            ],
            'rituals' => [
                'page' => SabWikiPageDefinitions::TOPIC_PAGE_SLUGS['rituals'],
                'source_url' => null,
                'fields' => ['obtain_method', 'steps'],
                'ttl_hours' => 24,
                'confirmed_lock' => 'manual_update',
                'status' => 'deferred',
            ],
            'admin-abuse' => [
                'page' => SabWikiPageDefinitions::TOPIC_PAGE_SLUGS['admin-abuse'],
                'source_url' => SabWikiAdminAbuseScheduleService::ADMIN_ABUSE_SOURCE,
                'fields' => ['weekday', 'time', 'timezone', 'next_event_at', 'duration', 'status'],
                'ttl_hours' => 12,
                'confirmed_lock' => 'manual_update',
                'status' => 'active',
            ],
        ];
    }

    public static function hasCollectableSource(string $topicKey): bool
    {
        $source = self::topics()[$topicKey]['source_url'] ?? null;

        return is_string($source) && $source !== '';
    }
}
