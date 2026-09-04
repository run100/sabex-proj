<?php

namespace Tests\Unit;

use App\Services\Seo\SabWikiAdminAbuseScheduleService;
use App\Services\Seo\SabWikiPageDefinitions;
use App\Services\Seo\SabWikiTopicSourceContract;
use PHPUnit\Framework\TestCase;

class SabWikiTopicSourceContractTest extends TestCase
{
    public function test_admin_abuse_topic_has_a_collectable_community_source(): void
    {
        $topics = SabWikiTopicSourceContract::topics();

        $this->assertSame(array_keys(SabWikiPageDefinitions::TOPIC_PAGE_SLUGS), array_keys($topics));

        foreach ($topics as $key => $topic) {
            $this->assertSame(SabWikiPageDefinitions::TOPIC_PAGE_SLUGS[$key], $topic['page']);
            $this->assertSame('manual_update', $topic['confirmed_lock']);
            if ($key === 'admin-abuse') {
                $this->assertSame(SabWikiAdminAbuseScheduleService::ADMIN_ABUSE_SOURCE, $topic['source_url']);
                $this->assertSame('active', $topic['status']);
                $this->assertTrue(SabWikiTopicSourceContract::hasCollectableSource($key));
            } else {
                $this->assertNull($topic['source_url']);
                $this->assertSame('deferred', $topic['status']);
                $this->assertFalse(SabWikiTopicSourceContract::hasCollectableSource($key));
            }
        }
    }

    public function test_static_redirects_cover_trailing_slashes_and_shipped_legacy_urls(): void
    {
        $rules = implode("\n", SabWikiPageDefinitions::staticRedirectRules());

        $this->assertStringContainsString('/wiki/ /wiki 301', $rules);
        $this->assertStringContainsString('/wiki/all-brainrots/ /wiki/all-brainrots 301', $rules);
        $this->assertStringContainsString('/wiki/all-og-brainrots/ /wiki/all-og-brainrots 301', $rules);
        $this->assertStringContainsString('/wiki/steal-a-brainrot-rebirth-list/ /wiki/steal-a-brainrot-rebirth-list 301', $rules);
        $this->assertStringNotContainsString('/wiki/events', $rules);
        $this->assertStringNotContainsString('/wiki/admin-event', $rules);
        $this->assertStringNotContainsString('/wiki/rituals', $rules);
        $this->assertStringNotContainsString('/wiki/all-rebirths', $rules);
        $this->assertStringNotContainsString('/all-og-brainrots/ /all-og-brainrots 301', $rules);
        $this->assertDoesNotMatchRegularExpression('/(?:^|\n)\/all-rebirths(?:\/|\s)/', $rules);
    }
}
