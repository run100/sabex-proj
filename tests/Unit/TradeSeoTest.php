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

    public function test_marketplace_faqs_cover_live_trade_flow(): void
    {
        $faqs = TradeSeo::marketplaceFaqs();
        $questions = array_column($faqs, 'q');
        $answers = implode(' ', array_column($faqs, 'a'));

        $this->assertCount(5, $faqs);
        $this->assertContains('How do I send an offer?', $questions);
        $this->assertStringContainsString('Make Offer', $answers);
        $this->assertStringContainsString('Mark Completed', $answers);
        $this->assertStringContainsString('Awaiting confirmation', $answers);
        $this->assertStringNotContainsString('Open Post a Trade', $answers);

        $json = TradeSeo::marketplaceFaqJsonLd();
        $this->assertStringContainsString('"@type":"FAQPage"', $json);
        $this->assertStringContainsString('Make Offer', $json);
    }
}
