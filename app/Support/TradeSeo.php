<?php

namespace App\Support;

use App\Models\TradeListing;
use App\Models\TradeListingItem;
use App\Services\Seo\SabRenderService;

class TradeSeo
{
    public const BRAND = 'SABExistCount';

    public const ITEM_MAX = 28;

    public const TITLE_MAX = 60;

    public const FALLBACK_TITLE = 'Steal a Brainrot Trade | '.self::BRAND;

    public static function truncateItem(string $name, int $max = self::ITEM_MAX): string
    {
        $name = trim($name);
        if ($max < 2) {
            $max = 2;
        }
        if (mb_strlen($name) <= $max) {
            return $name;
        }

        return rtrim(mb_substr($name, 0, $max - 1)).'…';
    }

    /**
     * @return list<string>
     */
    public static function chromaRarities(): array
    {
        return ['secret', 'brainrot god', 'og'];
    }

    public static function nameSlug(string $rarity): string
    {
        $key = SabRenderService::canonicalRarityKey($rarity);
        if ($key === '') {
            return '';
        }

        return str_replace(' ', '-', $key);
    }

    public static function isChroma(string $rarity): bool
    {
        return in_array(SabRenderService::canonicalRarityKey($rarity), self::chromaRarities(), true);
    }

    /**
     * @param  list<string>  $names
     * @return list<array{name: string, count: int, rarity: string}>
     */
    public static function collapseNames(array $names): array
    {
        return self::collapseRows(array_map(static fn (string $name): array => [
            'name' => $name,
            'rarity' => '',
        ], $names));
    }

    /**
     * @param  list<array{name?: string, rarity?: string}|string>  $rows
     * @return list<array{name: string, count: int, rarity: string}>
     */
    public static function collapseRows(array $rows): array
    {
        $counts = [];
        $order = [];
        $rarities = [];
        foreach ($rows as $row) {
            if (is_string($row)) {
                $name = trim($row);
                $rarity = '';
            } else {
                $name = trim((string) ($row['name'] ?? ''));
                $rarity = SabRenderService::canonicalRarityKey((string) ($row['rarity'] ?? ''));
            }
            if ($name === '') {
                continue;
            }
            if (! isset($counts[$name])) {
                $counts[$name] = 0;
                $order[] = $name;
                $rarities[$name] = $rarity;
            }
            $counts[$name]++;
        }

        return array_map(static fn (string $name): array => [
            'name' => $name,
            'count' => $counts[$name],
            'rarity' => $rarities[$name],
        ], $order);
    }

    /**
     * @param  array{name: string, count: int}  $token
     */
    public static function formatToken(array $token, int $itemMax = self::ITEM_MAX): string
    {
        $label = self::truncateItem((string) ($token['name'] ?? 'Brainrot'), $itemMax);
        $count = (int) ($token['count'] ?? 1);

        return $count > 1 ? $count.'x '.$label : $label;
    }

    /**
     * @param  list<string>  $names
     * @return array{label: string, tokens: list<string>, more: int}
     */
    public static function sideSummary(array $names, int $shown = 2, int $itemMax = self::ITEM_MAX, bool $includeMore = true): array
    {
        $tokens = self::collapseNames($names);
        if ($tokens === []) {
            return [
                'label' => 'Brainrot',
                'tokens' => ['Brainrot'],
                'more' => 0,
            ];
        }

        $visible = array_slice($tokens, 0, max(1, min($shown, count($tokens))));
        $parts = array_map(static fn (array $token): string => self::formatToken($token, $itemMax), $visible);
        $hidden = array_slice($tokens, count($visible));
        $more = 0;
        foreach ($hidden as $token) {
            $more += (int) $token['count'];
        }
        $label = implode(' + ', $parts);
        if ($includeMore && $more > 0) {
            $label .= ' +'.$more.' more';
        }

        return [
            'label' => $label,
            'tokens' => $parts,
            'more' => $includeMore ? $more : 0,
        ];
    }

    /**
     * @param  list<string>  $names
     */
    public static function sideLabel(array $names, int $shown = 2): string
    {
        return self::sideSummary($names, $shown)['label'];
    }

    /**
     * @param  list<string>  $offering
     * @param  list<string>  $looking
     * @return array{
     *     title: string,
     *     h1: string,
     *     h1Offering: list<array{label: string, rarity: string}>,
     *     h1Looking: list<array{label: string, rarity: string}>,
     *     offeringMore: int,
     *     lookingMore: int,
     *     description: string
     * }
     */
    public static function fromNames(array $offering, array $looking): array
    {
        $offering = array_values(array_filter(array_map(static fn ($name) => trim((string) $name), $offering)));
        $looking = array_values(array_filter(array_map(static fn ($name) => trim((string) $name), $looking)));
        $h1Offer = self::sideSummary($offering, 2);
        $h1Look = self::sideSummary($looking, 2);
        $firstOffer = self::truncateItem((string) ($offering[0] ?? 'Brainrot'));
        $firstLook = self::truncateItem((string) ($looking[0] ?? 'Brainrot'));

        return [
            'title' => self::fitTitle($offering, $looking),
            'h1' => self::plainH1($h1Offer, $h1Look),
            'h1Offering' => self::decorateTokens($h1Offer['tokens']),
            'h1Looking' => self::decorateTokens($h1Look['tokens']),
            'offeringMore' => $h1Offer['more'],
            'lookingMore' => $h1Look['more'],
            'description' => $firstOffer.' for '.$firstLook.' Steal a Brainrot trade on SABExistCount. Compare SAB values and finish the swap in Roblox.',
        ];
    }

    /**
     * @return array{
     *     title: string,
     *     h1: string,
     *     h1Offering: list<array{label: string, rarity: string}>,
     *     h1Looking: list<array{label: string, rarity: string}>,
     *     offeringMore: int,
     *     lookingMore: int,
     *     description: string
     * }
     */
    public static function listing(TradeListing $listing): array
    {
        $offeringRows = self::itemRows($listing, TradeListingItem::SIDE_OFFERING);
        $lookingRows = self::itemRows($listing, TradeListingItem::SIDE_LOOKING);
        $seo = self::fromNames(
            array_column($offeringRows, 'name'),
            array_column($lookingRows, 'name'),
        );
        $seo['h1Offering'] = self::sideTokensFromRows($offeringRows);
        $seo['h1Looking'] = self::sideTokensFromRows($lookingRows);

        return $seo;
    }

    /**
     * @param  list<array{name: string, rarity?: string}|string>  $rows
     * @return list<array{label: string, rarity: string}>
     */
    public static function sideTokensFromRows(array $rows, int $shown = 2, int $itemMax = self::ITEM_MAX): array
    {
        $collapsed = self::collapseRows($rows);
        if ($collapsed === []) {
            return [['label' => 'Brainrot', 'rarity' => '']];
        }

        $visible = array_slice($collapsed, 0, max(1, min($shown, count($collapsed))));

        return array_map(static fn (array $token): array => [
            'label' => self::formatToken($token, $itemMax),
            'rarity' => (string) ($token['rarity'] ?? ''),
        ], $visible);
    }

    /**
     * @param  list<string>  $offering
     * @param  list<string>  $looking
     */
    public static function fitTitle(array $offering, array $looking): string
    {
        $attempts = [
            [2, self::ITEM_MAX, true],
            [1, self::ITEM_MAX, true],
            [1, 18, true],
            [1, 18, false],
            [1, 12, false],
        ];
        foreach ($attempts as [$shown, $max, $includeMore]) {
            $title = 'Trading '.self::sideSummary($offering, $shown, $max, $includeMore)['label']
                .' for '.self::sideSummary($looking, $shown, $max, $includeMore)['label']
                .' | '.self::BRAND;
            if (mb_strlen($title) <= self::TITLE_MAX) {
                return $title;
            }
        }

        return self::FALLBACK_TITLE;
    }

    /**
     * @return list<array{q: string, a: string}>
     */
    public static function marketplaceFaqs(): array
    {
        return [
            [
                'q' => 'How do I post a Steal a Brainrot trade?',
                'a' => 'Open Create Trade Ad, fill I Have and I Want, then publish. Guests can build the ad first and sign in when they publish.',
            ],
            [
                'q' => 'How do I send an offer?',
                'a' => 'Open a listing and tap Make Offer. The owner can Accept or Reject. The ad stays open until they accept.',
            ],
            [
                'q' => 'Does SABExistCount complete the trade in Roblox?',
                'a' => 'No. SABExistCount does not hold items or finish swaps. Complete the exchange in Roblox, then both players tap Mark Completed.',
            ],
            [
                'q' => 'When does a listing become Completed?',
                'a' => 'Only after both traders mark it completed. If one player has confirmed, the listing shows Awaiting confirmation.',
            ],
            [
                'q' => 'How do I find a Brainrot I want?',
                'a' => 'Use Filter Trades to search by the Brainrot you want to get or the one you have to give. Each card shows value, demand, mutations, and traits.',
            ],
        ];
    }

    public static function marketplaceFaqJsonLd(): string
    {
        return json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn (array $faq): array => [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['a'],
                ],
            ], self::marketplaceFaqs()),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    /**
     * @param  array{label: string, tokens: list<string>, more: int}  $offer
     * @param  array{label: string, tokens: list<string>, more: int}  $look
     */
    private static function plainH1(array $offer, array $look): string
    {
        return self::plainSide($offer).' for '.self::plainSide($look);
    }

    /**
     * @param  array{tokens: list<string>, more: int}  $side
     */
    private static function plainSide(array $side): string
    {
        $text = implode(' + ', $side['tokens']);
        if (($side['more'] ?? 0) > 0) {
            $text .= ' +'.$side['more'].' more';
        }

        return $text;
    }

    /**
     * @param  list<string|array{label?: string, rarity?: string}>  $tokens
     * @return list<array{label: string, rarity: string}>
     */
    private static function decorateTokens(array $tokens): array
    {
        return array_map(static function (string|array $token): array {
            if (is_array($token)) {
                return [
                    'label' => (string) ($token['label'] ?? 'Brainrot'),
                    'rarity' => SabRenderService::canonicalRarityKey((string) ($token['rarity'] ?? '')),
                ];
            }

            return [
                'label' => $token,
                'rarity' => '',
            ];
        }, $tokens);
    }

    /**
     * @return list<array{name: string, rarity: string}>
     */
    private static function itemRows(TradeListing $listing, string $side): array
    {
        return $listing->items
            ->where('side', $side)
            ->sortBy('slot_no')
            ->map(static function (TradeListingItem $item): array {
                return [
                    'name' => trim((string) $item->brainrot_name_snapshot),
                    'rarity' => (string) ($item->seoItem?->rarity ?? ''),
                ];
            })
            ->filter(static fn (array $row): bool => $row['name'] !== '')
            ->values()
            ->all();
    }
}
