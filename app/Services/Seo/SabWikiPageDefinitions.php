<?php

namespace App\Services\Seo;

final class SabWikiPageDefinitions
{
    public const PAGE_ALL_BRAINROTS = 'wiki/all-brainrots';

    public const PAGE_WIKI_RITUALS = 'wiki/rituals';

    public const PAGE_WIKI_REBIRTHS = 'wiki/steal-a-brainrot-rebirth-list';

    public const PAGE_WIKI_ADMIN_ABUSE = 'wiki/admin-abuse';

    public const PAGE_WIKI_LUCKY_BLOCKS = 'wiki/all-lucky-blocks';

    public const PAGE_WIKI_FUSIONS = 'wiki/all-fusions';

    /**
     * Cloudflare Pages / Netlify `_redirects` rules. Trailing-slash URLs for
     * shipped pages must be hosted as 301s; Laravel .htaccess does not apply to the static mirror.
     *
     * @return list<string>
     */
    public static function staticRedirectRules(): array
    {
        $rules = [];
        foreach (['wiki', ...self::shippingPageSlugs()] as $slug) {
            $rules[] = '/'.$slug.'/ /'.$slug.' 301';
        }

        return $rules;
    }

    /** @var list<string> */
    public const RARITY_ORDER = [
        'common',
        'rare',
        'epic',
        'legendary',
        'mythic',
        'brainrot god',
        'secret',
        'og',
    ];

    /** @var array<string, string> */
    public const RARITY_PAGE_SLUGS = [
        'common' => 'wiki/all-common-brainrots',
        'rare' => 'wiki/all-rare-brainrots',
        'epic' => 'wiki/all-epic-brainrots',
        'legendary' => 'wiki/all-legendary-brainrots',
        'mythic' => 'wiki/all-mythic-brainrots',
        'brainrot god' => 'wiki/all-brainrot-god',
        'secret' => 'wiki/all-secret-brainrots',
        'og' => 'wiki/all-og-brainrots',
    ];

    /** @var array<string, string> */
    public const TOPIC_PAGE_SLUGS = [
        'lucky-blocks' => self::PAGE_WIKI_LUCKY_BLOCKS,
        'fusions' => self::PAGE_WIKI_FUSIONS,
        'rebirths' => self::PAGE_WIKI_REBIRTHS,
        'rituals' => self::PAGE_WIKI_RITUALS,
        'admin-abuse' => self::PAGE_WIKI_ADMIN_ABUSE,
    ];

    /** @var array<string, string> */
    public const SECTION_COPY = [
        'common' => 'Common Brainrots are the most accessible starting units and usually have the lowest in-game cost and income.',
        'rare' => 'Rare Brainrots are early-game units that appear less often and generally earn more than Common Brainrots.',
        'epic' => 'Epic Brainrots sit above the early tiers with stronger income and a higher in-game purchase cost.',
        'legendary' => 'Legendary Brainrots are harder to find and often mark the transition into higher-income collection goals.',
        'mythic' => 'Mythic Brainrots are high-tier units with stronger income and less frequent availability.',
        'brainrot god' => 'Brainrot God units are premium high-tier collectibles that can have limited or update-specific availability.',
        'secret' => 'Secret Brainrots cover a large group of event, machine, crafting, and limited-release collectibles.',
        'og' => 'OG Brainrots are among the rarest named tiers and should be checked carefully against current supply data.',
        'other' => 'Published items whose stored rarity is outside the eight standard tiers. They stay listed here instead of being dropped.',
    ];

    /**
     * @return list<string>
     */
    public static function rarityPageSlugs(): array
    {
        return array_values(self::RARITY_PAGE_SLUGS);
    }

    /**
     * @return list<string>
     */
    public static function catalogPageSlugs(): array
    {
        return [self::PAGE_ALL_BRAINROTS, ...self::rarityPageSlugs()];
    }

    /**
     * @return list<string>
     */
    public static function topicPageSlugs(): array
    {
        return array_values(self::TOPIC_PAGE_SLUGS);
    }

    /**
     * @return list<string>
     */
    public static function newPageSlugs(): array
    {
        return [...self::catalogPageSlugs(), ...self::topicPageSlugs()];
    }

    /**
     * Topic pages that ship this release. Other topic pages stay preview-only.
     *
     * @return list<string>
     */
    public static function shippingTopicPageSlugs(): array
    {
        return [self::PAGE_WIKI_REBIRTHS, self::PAGE_WIKI_ADMIN_ABUSE];
    }

    /**
     * Catalog pages plus the topic pages that ship this release.
     *
     * @return list<string>
     */
    public static function shippingPageSlugs(): array
    {
        return [...self::catalogPageSlugs(), ...self::shippingTopicPageSlugs()];
    }

    /**
     * @return list<string>
     */
    public static function nestedPreviewSlugs(): array
    {
        return array_values(array_unique(array_map(
            static fn (string $slug): string => basename($slug),
            self::newPageSlugs()
        )));
    }

    public static function rarityKeyForPage(string $slug): ?string
    {
        $match = array_search($slug, self::RARITY_PAGE_SLUGS, true);

        return $match === false ? null : (string) $match;
    }

    public static function demandCssClass(?string $label): string
    {
        $key = strtolower(trim((string) $label));

        return match (true) {
            in_array($key, ['amazing', 'insane', 'high', 'booming'], true) => 'is-high',
            in_array($key, ['normal', 'medium'], true) => 'is-mid',
            $key === 'low' => 'is-low',
            $key === 'terrible' => 'is-bad',
            default => 'is-flat',
        };
    }

    public static function trendCssClass(?string $label): string
    {
        return match (strtolower(trim((string) $label))) {
            'rising' => 'is-up',
            'lowering' => 'is-down',
            default => 'is-flat',
        };
    }

    public static function rarityCssClass(?string $key): string
    {
        return match ($key) {
            'common' => 'brainrot-rarity-common',
            'rare' => 'brainrot-rarity-rare',
            'epic' => 'brainrot-rarity-epic',
            'legendary' => 'brainrot-rarity-legendary',
            'mythic' => 'brainrot-rarity-mythic',
            'brainrot god' => 'brainrot-rarity-brainrot-god',
            'secret' => 'brainrot-rarity-secret',
            'og' => 'brainrot-rarity-og',
            default => 'brainrot-rarity-default',
        };
    }

    public static function topicKeyForPage(string $slug): ?string
    {
        $match = array_search($slug, self::TOPIC_PAGE_SLUGS, true);

        return $match === false ? null : (string) $match;
    }

    /**
     * @return array{title: string, description: string, h1: string, lead: string, og_alt: string, faqs: list<array{question: string, answer: string}>}
     */
    public static function copy(string $pageKey, int $count, ?string $rarestName = null, ?string $rarestCount = null): array
    {
        $listed = number_format($count);
        $hub = [
            'title' => 'Steal a Brainrot Wiki | SAB Values, Calculator & Exist Count',
            'description' => 'Steal a Brainrot Wiki with all Brainrots, rarity lists, SAB Values, trade calculator, and live exist counts. Check cost, income, updates, and FAQs.',
            'h1' => 'Steal a Brainrot Wiki: SAB Values, Calculator & Exist Count',
            'lead' => 'Use this Steal a Brainrot Wiki to browse every Brainrot rarity, then open SAB Values, the SAB Calculator, or SAB Exist Count tools to compare trade value, income, and supply across '.$listed.' published Brainrots.',
            'og_alt' => 'Steal a Brainrot Wiki with SAB Values, Calculator, and Exist Count tools.',
            'faqs' => [
                [
                    'question' => 'What is Steal a Brainrot?',
                    'answer' => 'Steal a Brainrot is a Roblox experience where players collect Brainrots that generate income and build a collection. This independent Wiki focuses on stored catalog, value, and supply data.',
                ],
                [
                    'question' => 'What are SAB Values?',
                    'answer' => 'SAB Values are community trade-value references from this site. Open the SAB Values list to review current value, previous value, demand, and trend. They are not official Roblox prices or guaranteed trade outcomes.',
                ],
                [
                    'question' => 'How does the SAB Calculator work?',
                    'answer' => 'The SAB Calculator compares the items in Your Offer and You Receive using stored values, mutations, traits, and income, then returns a W/F/L reference. It does not guarantee that another player will accept a trade.',
                ],
                [
                    'question' => 'What is SAB Exist Count?',
                    'answer' => 'SAB Exist Count is the tracked number of copies of a Brainrot in player possession. A confirmed count is shown as a number, an estimate is labeled, and missing data is shown as Unknown.',
                ],
                [
                    'question' => 'What is the difference between Cost, Income, Exist Count, and Trade Value?',
                    'answer' => 'Cost is the in-game purchase or acquisition price, Income is the cash generated per second, Exist Count tracks supply, and Trade Value is a community market signal used when comparing offers.',
                ],
                [
                    'question' => 'How often are SAB Values and Exist Counts updated?',
                    'answer' => 'SAB Values and the SAB Exist Count List update when their stored sources refresh and confirmed data changes. The page date reflects actual stored updates rather than the page render time.',
                ],
                [
                    'question' => 'How can I find the rarest Brainrot?',
                    'answer' => $rarestName
                        ? 'Open the SAB Exist Count List and sort from the lowest confirmed count. '.$rarestName.' currently has the lowest confirmed count shown in this Wiki at '.$rarestCount.', but counts can change and estimates are not treated as confirmed totals.'
                        : 'Open the SAB Exist Count List and sort from the lowest confirmed count. There is not enough confirmed data to name one rarest Brainrot, and estimated ranges are not treated as confirmed totals.',
                ],
                [
                    'question' => 'Are SAB Values official Roblox prices?',
                    'answer' => 'No. SAB Values are independent community references for comparing trades. They are not official Roblox prices, guaranteed offers, or a promise that another player will accept a trade.',
                ],
            ],
        ];

        $pages = [
            'wiki' => $hub,
            self::PAGE_ALL_BRAINROTS => [
                'title' => 'All Brainrots in Steal a Brainrot | Cost, Income & Exist Count',
                'description' => $listed.' published Brainrots with stored cost, income, exist count, and trade value. Search or sort any row. Missing data stays Unknown.',
                'h1' => 'All Brainrots in Steal a Brainrot',
                'lead' => $listed.' published items with stored cost, income, exist count, and trade value. Search or sort any row. Missing values stay Unknown.',
                'og_alt' => 'Complete Steal a Brainrot catalog with cost, income, exist count, and trade value.',
                'faqs' => [
                    [
                        'question' => 'Does this page list every published Brainrot?',
                        'answer' => 'Yes. This catalog currently lists '.$listed.' published items that pass the SAB index rules. Open a rarity page when you only want one tier.',
                    ],
                    [
                        'question' => 'Why is a value shown as Unknown?',
                        'answer' => 'Unknown means the stored field is empty. This page does not invent a zero or an estimate for a missing cost, income, exist count, or trade value.',
                    ],
                ],
            ],
            self::RARITY_PAGE_SLUGS['og'] => [
                'title' => 'All OG Brainrots in Steal a Brainrot | Exist Count & Value',
                'description' => $listed.' published OG Brainrots with stored cost, income, exist count, and trade value. OG is the top named tier. Missing values stay Unknown.',
                'h1' => 'All OG Brainrots',
                'lead' => $listed.' published OG Brainrots. OG is the top named tier; exist count and trade value still have to be checked on the row.',
                'og_alt' => 'OG Brainrot list with exist count and trade value.',
                'faqs' => [
                    [
                        'question' => 'How many OG Brainrots are listed?',
                        'answer' => 'This page currently lists '.$listed.' published OG Brainrots from stored SAB data. The count is not a fixed lore number.',
                    ],
                    [
                        'question' => 'Does OG rarity prove a low Exist Count?',
                        'answer' => 'No. OG is a game label. Confirm the stored exist count and trade value before treating an OG item as scarce or expensive.',
                    ],
                ],
            ],
            self::RARITY_PAGE_SLUGS['secret'] => [
                'title' => 'All Secret Brainrots in Steal a Brainrot | Exist Count',
                'description' => $listed.' published Secret Brainrots with stored cost, income, exist count, and trade value. Secret is a label, not a guaranteed low supply.',
                'h1' => 'All Secret Brainrots',
                'lead' => $listed.' published Secret Brainrots. Secret often covers event or limited items; exist count and trade value still have to be checked on the row.',
                'og_alt' => 'Secret Brainrot list with exist count and trade value.',
                'faqs' => [
                    [
                        'question' => 'Are all Secret Brainrots rare in supply?',
                        'answer' => 'Not automatically. Secret is a rarity label. Use the stored exist count; an empty count stays Unknown.',
                    ],
                    [
                        'question' => 'Where do Secret Brainrots come from?',
                        'answer' => 'Stored obtain methods may mention events, machines, or limited drops. If obtain is empty, this page does not invent a route.',
                    ],
                ],
            ],
            self::RARITY_PAGE_SLUGS['brainrot god'] => [
                'title' => 'All Brainrot God Items in Steal a Brainrot | Exist Count',
                'description' => $listed.' published Brainrot God items with stored cost, income, exist count, and trade value. This tier sits below OG and is not “Godly”.',
                'h1' => 'All Brainrot God Brainrots',
                'lead' => $listed.' published Brainrot God items. This premium tier sits below OG; exist count and trade value still have to be checked on the row.',
                'og_alt' => 'Brainrot God list with exist count and trade value.',
                'faqs' => [
                    [
                        'question' => 'Is Brainrot God the same as OG?',
                        'answer' => 'No. Brainrot God sits below OG in the stored rarity order. Check each row’s exist count instead of assuming one tier is always scarcer.',
                    ],
                    [
                        'question' => 'Can Brainrot God items be update-specific?',
                        'answer' => 'Yes. Availability can be limited. This page only shows stored catalog fields, not a live spawn timer.',
                    ],
                ],
            ],
            self::RARITY_PAGE_SLUGS['mythic'] => [
                'title' => 'All Mythic Brainrots in Steal a Brainrot | Exist Count',
                'description' => $listed.' published Mythic Brainrots with stored cost, income, exist count, and trade value. Mythic sits above Legendary. Missing values stay Unknown.',
                'h1' => 'All Mythic Brainrots',
                'lead' => $listed.' published Mythic Brainrots. Mythic sits above Legendary; exist count and trade value still have to be checked on the row.',
                'og_alt' => 'Mythic Brainrot catalog with exist count and trade value.',
                'faqs' => [
                    [
                        'question' => 'Is Mythic more valuable than Legendary?',
                        'answer' => 'Not by label alone. Compare stored income and trade value. A missing trade value stays Unknown.',
                    ],
                    [
                        'question' => 'Why is an Exist Count labeled Estimate?',
                        'answer' => 'Estimate means the stored supply is a range, not a confirmed total. Confirmed counts have no Estimate badge.',
                    ],
                ],
            ],
            self::RARITY_PAGE_SLUGS['legendary'] => [
                'title' => 'All Legendary Brainrots in Steal a Brainrot | Exist Count',
                'description' => $listed.' published Legendary Brainrots with stored cost, income, exist count, and trade value. Legendary is a mid-high tier. Missing values stay Unknown.',
                'h1' => 'All Legendary Brainrots',
                'lead' => $listed.' published Legendary Brainrots. Legendary is a mid-high tier; exist count and trade value still have to be checked on the row.',
                'og_alt' => 'Legendary Brainrot list with exist count and trade value.',
                'faqs' => [
                    [
                        'question' => 'Does Legendary rarity set trade value?',
                        'answer' => 'No. Trade value is a separate market signal. A Legendary with Unknown trade value is not priced at zero.',
                    ],
                    [
                        'question' => 'How is this different from the full catalog?',
                        'answer' => 'This page only includes Legendary rows. Open All Brainrots to search every published tier.',
                    ],
                ],
            ],
            self::RARITY_PAGE_SLUGS['epic'] => [
                'title' => 'All Epic Brainrots in Steal a Brainrot | Cost & Exist Count',
                'description' => $listed.' published Epic Brainrots with stored cost, income, exist count, and trade value. Epic sits above Rare. Missing values stay Unknown.',
                'h1' => 'All Epic Brainrots',
                'lead' => $listed.' published Epic Brainrots. Epic sits above Rare; exist count and trade value still have to be checked on the row.',
                'og_alt' => 'Epic Brainrot list with cost and exist count.',
                'faqs' => [
                    [
                        'question' => 'Are Epic Brainrots mainly conveyor units?',
                        'answer' => 'Many are easier to find than later tiers, but this page only shows stored obtain methods. Empty obtain cells stay empty.',
                    ],
                    [
                        'question' => 'Can an Epic have a high trade value?',
                        'answer' => 'Yes, if the stored market signal says so. Rarity alone does not cap or guarantee trade value.',
                    ],
                ],
            ],
            self::RARITY_PAGE_SLUGS['rare'] => [
                'title' => 'All Rare Brainrots in Steal a Brainrot | Cost & Income',
                'description' => $listed.' published Rare Brainrots with stored cost, income, exist count, and trade value. Rare sits above Common. Missing values stay Unknown.',
                'h1' => 'All Rare Brainrots',
                'lead' => $listed.' published Rare Brainrots. Rare sits above Common; exist count and trade value still have to be checked on the row.',
                'og_alt' => 'Rare Brainrot list with cost and income.',
                'faqs' => [
                    [
                        'question' => 'Does Rare mean low Exist Count?',
                        'answer' => 'No. Rare is an early-game label. A high exist count can sit on this page next to Unknown supply.',
                    ],
                    [
                        'question' => 'Why include Cost and Income here?',
                        'answer' => 'Rare items are often compared as starters. Stored cost and income stay on the row so rarity is not the only signal.',
                    ],
                ],
            ],
            self::RARITY_PAGE_SLUGS['common'] => [
                'title' => 'All Common Brainrots in Steal a Brainrot | Cost & Income',
                'description' => $listed.' published Common Brainrots with stored cost, income, exist count, and trade value. Common is the starting tier. Missing values stay Unknown.',
                'h1' => 'All Common Brainrots',
                'lead' => $listed.' published Common Brainrots. Common is the starting tier; exist count and trade value still have to be checked on the row.',
                'og_alt' => 'Common Brainrot list with cost and income.',
                'faqs' => [
                    [
                        'question' => 'Are Common Brainrots only useful early?',
                        'answer' => 'They are the starting tier for cost and income, and some stored obtain notes later mention fusion or craft uses. This page does not invent recipes.',
                    ],
                    [
                        'question' => 'Why track Exist Count on Commons?',
                        'answer' => 'Supply still matters for trades and index completion. Missing counts stay Unknown instead of looking like zero copies.',
                    ],
                ],
            ],
            self::PAGE_WIKI_LUCKY_BLOCKS => [
                'title' => 'All Steal a Brainrot Lucky Blocks | Drops & Exist Count',
                'description' => 'Lucky Block items and Brainrots stored with a Lucky Block obtain path, plus cost, income, exist count, and trade value when available.',
                'h1' => 'All Lucky Blocks',
                'lead' => 'This list includes Lucky Block items and Brainrots whose stored obtain path mentions a Lucky Block. Drop rates are not invented.',
                'og_alt' => 'Lucky Block items and drops with exist count.',
                'faqs' => [
                    [
                        'question' => 'Does this page show drop chances?',
                        'answer' => 'No. Only stored obtain paths and catalog fields are shown. A missing drop chance stays unlisted.',
                    ],
                    [
                        'question' => 'Why is a Lucky Block also on a rarity page?',
                        'answer' => 'Rarity pages group by stored rarity. This page groups by Lucky Block obtain or name.',
                    ],
                ],
            ],
            self::PAGE_WIKI_FUSIONS => [
                'title' => 'All Steal a Brainrot Fusions | Confirmed Fuse & Craft Items',
                'description' => 'Brainrots stored with a confirmed fusion, fuse, or craft obtain method. Recipes appear only when confirmed. This is not a copied machine table.',
                'h1' => 'All Confirmed Fusions',
                'lead' => 'Items appear here only when a stored obtain method mentions fusion, fuse, or crafting. Recipes are shown only when that confirmed field exists.',
                'og_alt' => 'Confirmed Steal a Brainrot fusion and craft items.',
                'faqs' => [
                    [
                        'question' => 'Where are the fusion recipes?',
                        'answer' => 'Recipes appear only when a confirmed recipe field is stored. This page does not copy another site’s machine table.',
                    ],
                    [
                        'question' => 'Can an item be both a fusion and a rarity-page row?',
                        'answer' => 'Yes. Rarity pages filter by tier. This page filters by a stored fuse or craft obtain method.',
                    ],
                ],
            ],
            self::PAGE_WIKI_REBIRTHS => [
                'title' => 'Steal a Brainrot Rebirth List ({month}) — All 19 Levels',
                'description' => 'All 19 Steal a Brainrot Rebirth requirements and rewards, including cash, Brainrots, multipliers, floors, Rebirth 19, and max Rebirth tips. Updated {month}.',
                'h1' => 'Steal a Brainrot Rebirth List — All 19 Levels',
                'lead' => 'Check the cash, Brainrot, and rarity for Rebirth 1–19 before you reset. Compare the multiplier, starting cash, and floor unlocks, then use SAB Exist Count and SAB Values if the required copy is hard to replace.',
                'og_alt' => 'Steal a Brainrot Rebirth list with all 19 requirements and rewards.',
                'faqs' => [
                    [
                        'question' => 'How many Rebirths are in Steal a Brainrot?',
                        'answer' => 'There are currently 19 Rebirth levels. Older pages that stop at 15 or 16 are outdated. Requirements have changed before, so confirm the live in-game Rebirth menu before you reset.',
                    ],
                    [
                        'question' => 'What is the max Rebirth in Steal a Brainrot?',
                        'answer' => 'Rebirth 19 is the current maximum. It requires $30Qa and La Grande Combinasion in your base.',
                    ],
                    [
                        'question' => 'Is there a Rebirth 20 in Steal a Brainrot?',
                        'answer' => 'No Rebirth 20 is confirmed in the current Update 64 snapshot. Update 64 added Secret Brainrots and Bee content, but Rebirth 19 remains the latest listed level.',
                    ],
                    [
                        'question' => 'What do you need for Rebirth 19?',
                        'answer' => 'You need $30Qa and La Grande Combinasion parked in your base, not walking in from the Red Carpet.',
                    ],
                    [
                        'question' => 'What do you get from Rebirth 19?',
                        'answer' => 'Rebirth 19 gives x19 income, $250T starting cash, +1 base slot, +10 seconds of base lock time, and the Grief Shield.',
                    ],
                    [
                        'question' => 'When do you unlock the second floor?',
                        'answer' => 'The second floor unlocks at Rebirth 2. That extra floor and slot are why most players reset as soon as the $1.5M and Brr Brr Patapim + Boneca Ambalabu check is ready.',
                    ],
                    [
                        'question' => 'When do you unlock the third floor?',
                        'answer' => 'The third floor unlocks at Rebirth 10, after $125B and Girafa Celestre. The extra floor is what lets the x9 multiplier hold more parked income.',
                    ],
                    [
                        'question' => 'What is the first Rebirth that needs a Secret?',
                        'answer' => 'Rebirth 16 is the first current level that requires a Secret: Los Tralaleritos plus $1Qa. For many players this is a harder gate than Rebirth 19’s cash line.',
                    ],
                    [
                        'question' => 'Do you lose your Brainrots when you Rebirth?',
                        'answer' => 'Yes. Parked Brainrots and cash reset with the run. A Brainrot still walking to your base can bounce back to the Red Carpet, and a copy mid-steal can disappear. Check the confirmation screen before you press it.',
                    ],
                    [
                        'question' => 'When is the best time to Rebirth?',
                        'answer' => 'Early on, reset as soon as Rebirth 2 or 10 is ready so the extra floor starts working. From Rebirth 9, secure the named Brainrot before you finish the cash. From Rebirth 16, treat the Secret as the first requirement, not the last.',
                    ],
                    [
                        'question' => 'Should you move rare Brainrots to an alt before Rebirth?',
                        'answer' => 'If a parked copy is hard to replace, some players move it to a second account before the reset and steal it back after. Check Exist Count and SAB Values first. A low-supply or high-value copy may be worth keeping off the reset.',
                    ],
                ],
            ],
            self::PAGE_WIKI_RITUALS => [
                'title' => 'Steal a Brainrot Rituals | Confirmed Ritual Routes',
                'description' => 'Ritual obtain routes listed only when a confirmed ritual method is stored. Steps appear only when ritual data is complete. Missing steps stay unlisted.',
                'h1' => 'Steal a Brainrot Rituals',
                'lead' => 'Ritual routes appear only when a stored obtain method mentions a ritual. Steps are omitted unless that confirmed data exists.',
                'og_alt' => 'Confirmed Steal a Brainrot ritual obtain routes.',
                'faqs' => [
                    [
                        'question' => 'How do I perform a ritual?',
                        'answer' => 'This page lists items with a confirmed ritual obtain method. Step-by-step HowTo content is added only when those steps are stored.',
                    ],
                    [
                        'question' => 'Why are some rituals missing?',
                        'answer' => 'Unconfirmed community routes are not written as facts. Missing ritual data stays unlisted.',
                    ],
                ],
            ],
            self::PAGE_WIKI_ADMIN_ABUSE => [
                'title' => 'Steal a Brainrot Admin Abuse Time Today | Event Schedule',
                'description' => 'Find the next Steal a Brainrot Admin Abuse time, Taco Tuesday schedule, timezone conversions, event countdown, rewards, and confirmed updates.',
                'h1' => 'Steal a Brainrot Admin Abuse Time Today',
                'lead' => 'Check the next confirmed Admin Abuse and Taco Tuesday time for Steal a Brainrot, including Eastern Time, your local time, a timezone table, countdown, and what typically happens in the window. Admin Abuse is a developer-hosted event, not an exploit. Community schedule data is labeled and never guessed.',
                'og_alt' => 'Steal a Brainrot Admin Abuse time and Taco Tuesday event schedule.',
                'faqs' => [
                    [
                        'question' => 'What time is Admin Abuse in Steal a Brainrot today?',
                        'answer' => 'The current Admin Abuse card shows the next confirmed time in Eastern Time and your local browser time. If no reliable time is stored, it says Not confirmed.',
                    ],
                    [
                        'question' => 'When is the next Steal a Brainrot Admin Abuse?',
                        'answer' => 'Use the next Admin Abuse card for the stored recurring schedule and next occurrence. Past dates are rejected and never displayed as upcoming.',
                    ],
                    [
                        'question' => 'What time is Taco Tuesday in Steal a Brainrot?',
                        'answer' => 'Taco Tuesday is shown in Eastern Time with an automatic local-time conversion when a community schedule is confirmed. Otherwise the page says Not confirmed.',
                    ],
                    [
                        'question' => 'Is Taco Tuesday an Admin Abuse event?',
                        'answer' => 'Taco Tuesday is a separate recurring event that can overlap with Admin Abuse content. This page keeps both schedules in one place without treating them as the same event.',
                    ],
                    [
                        'question' => 'How long does Admin Abuse last?',
                        'answer' => 'The duration card uses the latest stored source range. Missing duration data stays Not confirmed.',
                    ],
                    [
                        'question' => 'What happens during Admin Abuse?',
                        'answer' => 'Only mechanics confirmed in the stored source are listed, such as luck changes, Lucky Blocks, limited machines, or rare Brainrot spawns. Unconfirmed claims are omitted.',
                    ],
                    [
                        'question' => 'Which Brainrots and Lucky Blocks can appear?',
                        'answer' => 'The related list only includes Brainrots whose stored obtain method matches Admin Abuse or a confirmed limited event. Open each product for its current rarity, Exist Count, and SAB Value.',
                    ],
                    [
                        'question' => 'Can the Admin Abuse or Taco Tuesday schedule change?',
                        'answer' => 'Yes. Events can be delayed, canceled, or moved. The page shows the source checked time and keeps an old successful schedule when a refresh fails instead of inventing a replacement.',
                    ],
                ],
            ],
        ];

        return $pages[$pageKey] ?? $hub;
    }
}
