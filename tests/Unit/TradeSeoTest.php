<?php

namespace Tests\Unit;

use App\Models\SeoItem;
use App\Models\TradeListing;
use App\Models\TradeListingItem;
use App\Support\TradePresenter;
use App\Support\TradeSeo;
use Tests\TestCase;

class TradeSeoTest extends TestCase
{
    public function test_side_label_collapses_duplicates_and_spaces_more(): void
    {
        $label = TradeSeo::sideLabel([
            'Garama and Madundung',
            'Bumbatron',
            'Garama and Madundung',
        ], 1);

        $this->assertSame('2x Garama and Madundung +1 more', $label);
    }

    public function test_side_label_joins_two_tokens_without_more(): void
    {
        $this->assertSame(
            '2x Garama and Madundung + Bumbatron',
            TradeSeo::sideLabel(['Garama and Madundung', 'Bumbatron', 'Garama and Madundung'], 2),
        );
    }

    public function test_title_stays_within_max_length(): void
    {
        $seo = TradeSeo::fromNames(
            ['Garama and Madundung', 'Bumbatron', 'Garama and Madundung'],
            ['Chicleteira Surfeiteira', 'Esok Goala'],
        );

        $this->assertLessThanOrEqual(TradeSeo::TITLE_MAX, mb_strlen($seo['title']));
        $this->assertStringStartsWith('Trading ', $seo['title']);
        $this->assertStringEndsWith('| '.TradeSeo::BRAND, $seo['title']);
        $this->assertStringNotContainsString('+More', $seo['title']);
        $this->assertStringNotContainsString('+1 More', $seo['title']);
    }

    public function test_h1_splits_sides_and_keeps_more_count(): void
    {
        $seo = TradeSeo::fromNames(
            ['Alpha', 'Beta', 'Gamma', 'Delta'],
            ['One', 'Two', 'Three'],
        );

        $this->assertSame([
            ['label' => 'Alpha', 'rarity' => ''],
            ['label' => 'Beta', 'rarity' => ''],
        ], $seo['h1Offering']);
        $this->assertSame(2, $seo['offeringMore']);
        $this->assertSame([
            ['label' => 'One', 'rarity' => ''],
            ['label' => 'Two', 'rarity' => ''],
        ], $seo['h1Looking']);
        $this->assertSame(1, $seo['lookingMore']);
        $this->assertSame('Alpha + Beta +2 more for One + Two +1 more', $seo['h1']);
    }

    public function test_secret_and_og_map_to_chroma(): void
    {
        $this->assertTrue(TradeSeo::isChroma('Secret'));
        $this->assertTrue(TradeSeo::isChroma('brainrot god'));
        $this->assertTrue(TradeSeo::isChroma('OG'));
        $this->assertFalse(TradeSeo::isChroma('common'));
        $this->assertFalse(TradeSeo::isChroma('mythic'));
        $this->assertSame('brainrot-god', TradeSeo::nameSlug('Brainrot God'));
        $this->assertSame('secret', TradeSeo::nameSlug('Secret'));
        $this->assertSame('', TradeSeo::nameSlug(''));
    }

    public function test_listing_h1_tokens_keep_first_rarity_and_chroma(): void
    {
        $listing = new TradeListing;
        $listing->setRelation('items', collect([
            self::h1Item(TradeListingItem::SIDE_OFFERING, 1, 'Noobini', 'Secret'),
            self::h1Item(TradeListingItem::SIDE_OFFERING, 2, 'Noobini', 'Common'),
            self::h1Item(TradeListingItem::SIDE_OFFERING, 3, 'Bumbatron', 'Legendary'),
            self::h1Item(TradeListingItem::SIDE_LOOKING, 1, 'Cappuccino', 'Common'),
        ]));

        $seo = TradeSeo::listing($listing);

        $this->assertSame([
            ['label' => '2x Noobini', 'rarity' => 'secret'],
            ['label' => 'Bumbatron', 'rarity' => 'legendary'],
        ], $seo['h1Offering']);
        $this->assertSame([
            ['label' => 'Cappuccino', 'rarity' => 'common'],
        ], $seo['h1Looking']);
        $this->assertTrue(TradeSeo::isChroma($seo['h1Offering'][0]['rarity']));
        $this->assertFalse(TradeSeo::isChroma($seo['h1Offering'][1]['rarity']));
    }

    private static function h1Item(string $side, int $slot, string $name, string $rarity): TradeListingItem
    {
        $item = new TradeListingItem([
            'side' => $side,
            'slot_no' => $slot,
            'brainrot_name_snapshot' => $name,
        ]);
        $item->setRelation('seoItem', new SeoItem(['rarity' => $rarity]));

        return $item;
    }

    public function test_short_trade_keeps_full_names_in_title(): void
    {
        $seo = TradeSeo::fromNames(['Noobini'], ['Cappuccino']);

        $this->assertSame('Trading Noobini for Cappuccino | SABExistCount', $seo['title']);
        $this->assertLessThanOrEqual(TradeSeo::TITLE_MAX, mb_strlen($seo['title']));
    }

    public function test_compact_value_matches_site_abbreviations(): void
    {
        $this->assertSame('420M', TradePresenter::compactValue(420_000_000));
        $this->assertSame('1.2k', TradePresenter::compactValue(1200));
        $this->assertSame('12', TradePresenter::compactValue(12));
    }

    public function test_card_side_title_includes_counts_and_joins_items(): void
    {
        $this->assertSame("They're offering", TradeSeo::cardSideTitle("They're offering", []));
        $this->assertSame(
            "They're offering 1x Happy Rock",
            TradeSeo::cardSideTitle("They're offering", ['Happy Rock']),
        );
        $this->assertSame(
            "They're looking for 2x Happy Rock",
            TradeSeo::cardSideTitle("They're looking for", ['Happy Rock', 'Happy Rock']),
        );
        $this->assertSame(
            "They're offering 2x Happy Rock + 1x Laser Cat",
            TradeSeo::cardSideTitle("They're offering", ['Happy Rock', 'Laser Cat', 'Happy Rock']),
        );
    }

    public function test_marketplace_faqs_cover_live_trade_flow(): void
    {
        $faqs = TradeSeo::marketplaceFaqs();
        $questions = array_column($faqs, 'q');
        $answers = implode(' ', array_column($faqs, 'a'));

        $this->assertCount(6, $faqs);
        $this->assertContains('What is Steal a Brainrot trades?', $questions);
        $this->assertContains('Is trading on SABExistCount free?', $questions);
        $this->assertStringContainsString('exist count', $answers);
        $this->assertStringContainsString('win, fair or loss', $answers);
        $this->assertStringContainsString('are free', $answers);
        $this->assertStringNotContainsString('How do I send an offer?', implode(' ', $questions));

        $json = TradeSeo::marketplaceFaqJsonLd();
        $this->assertStringContainsString('"@type":"FAQPage"', $json);
        $this->assertStringContainsString('What is Steal a Brainrot trades?', $json);
        $this->assertStringContainsString('exist count', $json);
    }

    public function test_breadcrumb_json_ld_has_home_and_trades(): void
    {
        config(['sab.hosts.www' => 'www.sabex.lab']);

        $html = TradeSeo::breadcrumbJsonLd();

        $this->assertStringContainsString('<script type="application/ld+json">', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
        $this->assertStringContainsString('"name":"Home"', $html);
        $this->assertStringContainsString('"name":"Trades"', $html);
        $this->assertStringContainsString('/trading', $html);
        $this->assertStringNotContainsString('"position":3', $html);
    }

    public function test_breadcrumb_json_ld_adds_current_page(): void
    {
        config(['sab.hosts.www' => 'www.sabex.lab']);

        $html = TradeSeo::breadcrumbJsonLd('Create Trade Ad', 'http://www.sabex.lab/trading/new');

        $this->assertStringContainsString('"position":3', $html);
        $this->assertStringContainsString('"name":"Create Trade Ad"', $html);
        $this->assertStringContainsString('/trading/new', $html);
    }
}
