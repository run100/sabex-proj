<?php

namespace App\Services\Seo;

use App\Models\SeoGame;
use App\Models\SeoItem;
use App\Models\SeoItemCurrentValue;
use App\Models\SeoItemObservation;
use App\Models\SeoItemSourcePool;
use App\Models\SeoItemVariant;
use App\Models\SeoNewsArticle;
use App\Models\SeoSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SabRenderService
{
    public const SITE_SLUG = 'sab-exist-count';

    /** @var list<string> */
    public const HOME_LOCALES = ['en', 'pt', 'es', 'de', 'ru', 'fr', 'tr', 'pl'];

    /** @var list<string> */
    public const MULTILINGUAL_PAGE_LOCALES = self::HOME_LOCALES;

    /** Laravel preview: path from site root. */
    public const CSS_HREF_LARAVEL = '/static/css/sabexistcount.css';

    public const CSS_HREF_SAB_CALCULATOR = '/static/css/sabcalculator.css';

    public const SITE_SLUG_SAB_CALCULATOR = 'sabcalculator';

    /** List page URL segments are extensionless; canonical/hreflang/JSON-LD use the same paths without `.html`. */
    public const PAGE_EXIST_COUNTS_LIST = 'sab-exist-count-list';

    /** Client/SSR page size for sab-exist-count-list (JSON pagination). */
    public const EXIST_COUNTS_LIST_PER_PAGE = 50;

    /** Client/SSR page size for sab-value-list (load-more batch). */
    public const VALUE_LIST_PER_PAGE = 48;

    public const PAGE_EXIST_COUNT_GALLERY = 'exist-count-gallery';

    public const PAGE_VALUE_LIST = 'sab-value-list';

    public const PAGE_VALUE_CHANGES = 'value-changes';

    public const PAGE_TRADING_CALCULATOR = 'steal-a-brainrot-trading-calculator';

    public const PAGE_CODES = 'steal-a-brainrot-codes';

    public const PAGE_WIKI = 'wiki';

    /** @var list<array{slug: string, title: string, desc: string}> */
    public const NEWS_POPULAR_GUIDES = [
        [
            'slug' => 'mutation-and-trait-value-guide-steal-a-brainrot',
            'title' => 'How Mutations Affect Value',
            'desc' => 'Mutations, traits & trade value',
        ],
        [
            'slug' => 'steal-a-brainrot-trading-value-update',
            'title' => 'Why SAB Values Change',
            'desc' => 'Demand, supply & update effects',
        ],
        [
            'slug' => 'how-to-trade-in-steal-a-brainrot-sab-trading-guide',
            'title' => 'SAB Trading Guide 2026',
            'desc' => 'Trade smarter and avoid bad deals',
        ],
        [
            'slug' => 'how-exist-count-affects-trade-value-steal-a-brainrot',
            'title' => 'Understanding Exist Count',
            'desc' => 'Why supply matters before trading',
        ],
    ];

    public const PAGE_GAG2_CALCULATOR = 'grow-a-garden-2-calculator';

    public const PAGE_BRAINROTS_LIST = 'brainrots';

    public const PAGE_GAMES_DIR = 'games';

    public const PAGE_GAME = 'steal-a-brainrot-simulator';

    public const PAGE_GAME_ROB = 'rob-brainrot';

    public const GAME_EMBED_URL = 'https://steal-brainrots.1games.io/';

    public const GAME_ROB_EMBED_URL = 'https://gamea.azgame.io/rob-brainrot-2/';

    public const GAME_COVER_SRC = '/uploads/images/sab/games/steal-a-brainrot-simulator-cover.png';

    public const GAME_ROB_COVER_SRC = '/uploads/images/sab/games/rob-brainrot-cover.png';

    public const GAME_LOGO_SRC = '/uploads/images/sab/games/steal-a-brainrot-simulator-logo.png';

    /** @var list<string> */
    public const STATIC_PAGE_SLUGS = ['about-us', 'privacy-policy', 'terms-of-service', 'faq'];

    /** @var list<string> */
    private const MULTILINGUAL_PAGE_SLUGS = [
        'index',
        self::PAGE_EXIST_COUNTS_LIST,
        self::PAGE_VALUE_LIST,
        self::PAGE_TRADING_CALCULATOR,
        self::PAGE_CODES,
        self::PAGE_GAMES_DIR,
        self::PAGE_GAMES_DIR . '/' . self::PAGE_GAME,
        self::PAGE_GAMES_DIR . '/' . self::PAGE_GAME_ROB,
    ];

    /** @var list<string> English value-list SEO/FAQ keys that always win over stale sab_copy / sab-i18n. */
    private const VALUE_LIST_EN_OVERRIDE_KEYS = [
        'value_list_meta_title',
        'value_list_meta_description',
        'value_list_h1',
        'value_list_intro',
        'value_list_updated',
        'value_list_faq_h2',
        'value_list_faq_exist_count_link',
        'value_list_faq_calculator_link',
        'value_list_faq_q1',
        'value_list_faq_a1',
        'value_list_faq_q2',
        'value_list_faq_a2',
        'value_list_faq_a2_empty',
        'value_list_faq_q3',
        'value_list_faq_a3',
        'value_list_faq_q4',
        'value_list_faq_a4',
        'value_list_faq_q5',
        'value_list_faq_a5',
        'value_list_faq_q6',
        'value_list_faq_a6',
        'value_list_faq_q7',
        'value_list_faq_a7',
        'value_list_faq_q8',
        'value_list_faq_a8',
    ];

    /** `seo:sab-render --module=` — partial scopes skip rewriting `sitemap.xml`, legal *.html, and `data/*.json`. */
    public const RENDER_MODULE_ALL = 'all';

    public const RENDER_MODULE_NEWS = 'news';

    public const RENDER_MODULE_PRODUCTS = 'products';

    public const RENDER_MODULE_CALCULATOR = 'calculator';

    /** @var list<string> Thin wiki/meta/user pages that should not be published as product detail pages. */
    private const NON_INDEXABLE_PRODUCT_SLUGS = [
        'disclaimer',
        'moonvalues',
        'leaks',
        'bicicleteira-family',
        'brainrot-god',
        'brainrot-trader',
        'dragon-family',
        'hotspot-disambiguation',
        'mastodontico-family',
        'matteo-family',
        'megalodon-family',
        'prehistoric-family',
        'taco-brainrots',
        'tralalero-disambiguation',
        'unused-content',
        'user-lincmon99',
        'limited-quantity-brainrots',
        'user-swaq38-sandbox',
        'user-localneer-sandbox-newformatting',
        'user-blog-theoourynewaccount-los-tralaledonitos',
        'user-blog-fandelostmediapococonocida-i-come-back',
        'user-blog-notyuiopeem-bomb-pig-rebalance',
        'user-pluh-teh-valleh',
        'legendary',
        'user-blog-notyuiopeem-pipi-rebalance',
        'arachnid-family',
        'user-blog-notyuiopeem-burbaloni-rebalance',
        'user-blog-notyuiopeem-tim-cheese-rebalance',
        'user-blog-pluh-teh-valleh-la-aquatic-combonasion',
        'combinasion-disambiguation',
        'user-blog-notyuiopeem-noobini-rebalance',
        'user-blog-notyuiopeem-pipi-corni-rebalance',
        'user-blog-notyuiopeem-talpa-di-fer-rebalance',
        'pipi-family',
        'karkerkur-family',
        'brainrots',
        'user-sin-mei-chan-page-archive',
        'user-matheuszinho-games',
        'duo-brainrots',
        'slap-or-hug',
    ];

    /** @var list<string> Real item pages kept live but temporarily removed from Google index. */
    private const TEMPORARY_NOINDEX_PRODUCT_SLUGS = [
        'meowl-2',
        'strawberry-elephant-2',
    ];

    /** @var array<string, string> Duplicate product slugs mapped to the preferred product URL slug. */
    private const PRODUCT_CANONICAL_SLUG_MAP = [
        'liril-laril' => 'lirili-larila',
    ];

    private const GAME_SLUG = 'steal-a-brainrot';

    private const DEFAULT_LOCALE = 'en';

    private const CSS_HREF_STATIC_HOME = '/static/css/sabexistcount.css';

    private const CSS_HREF_STATIC_PRODUCT = '/static/css/sabexistcount.css';

    /** Last CSS sync outcome (static so Artisan can read after nested app() calls). */
    private static ?array $lastCssSyncResult = null;

    /** @var list<string> 首页、布局页头和页脚实际会用到的可翻译文案键。 */
    private const HOME_I18N_KEYS = [
        'site_name',
        'nav_exist_counts_list',
        'nav_exist_count_gallery',
        'nav_value_list',
        'nav_news',
        'nav_codes',
        'nav_games',
        'nav_calculator',
        'hero_h1_prefix',
        'hero_h1_cyan',
        'hero_h1_daily_note',
        'hero_body',
        'exist_counts_list_meta_title',
        'exist_counts_list_meta_description',
        'exist_counts_list_h1',
        'exist_counts_list_h1_prefix',
        'exist_counts_list_h1_cyan',
        'exist_counts_list_month_badge',
        'exist_counts_list_intro',
        'exist_counts_list_table_h2',
        'exist_counts_list_table_caption',
        'exist_counts_list_updated',
        'exist_counts_list_stat_label',
        'exist_counts_list_page_info',
        'exist_counts_list_page_prev',
        'exist_counts_list_page_next',
        'exist_counts_list_tools_label',
        'exist_counts_list_tool_calculator',
        'exist_counts_list_tool_value_list',
        'exist_counts_list_tool_codes',
        'exist_counts_list_tool_gallery',
        'exist_counts_list_faq_h2',
        'exist_counts_list_faq_calculator_link',
        'exist_counts_list_faq_value_list_link',
        'exist_counts_list_faq_q1',
        'exist_counts_list_faq_a1',
        'exist_counts_list_faq_q2',
        'exist_counts_list_faq_a2',
        'exist_counts_list_faq_q3',
        'exist_counts_list_faq_a3',
        'exist_counts_list_faq_q4',
        'exist_counts_list_faq_a4',
        'exist_counts_list_faq_q5',
        'exist_counts_list_faq_a5',
        'exist_counts_list_faq_q6',
        'exist_counts_list_faq_a6',
        'exist_counts_list_faq_q7',
        'exist_counts_list_faq_a7',
        'exist_counts_list_faq_q8',
        'exist_counts_list_faq_a8',
        'home_intro',
        'home_overview_h2',
        'home_show_all',
        'home_show_top_10',
        'home_latest_h2',
        'home_latest_intro',
        'home_recent_h2',
        'home_recent_intro',
        'home_update_h2',
        'home_update_p1',
        'home_update_p2',
        'date_updated_recently',
        'stats_total',
        'stats_lowest',
        'stats_highest',
        'stats_snapshot',
        'table_h2',
        'table_updated',
        'table_caption',
        'search_placeholder',
        'search_showing_all',
        'search_showing_filtered',
        'search_empty',
        'search_empty_title',
        'search_empty_body',
        'rarity_filter_all',
        'rarity_filter_og',
        'rarity_filter_legendary',
        'rarity_filter_mythic',
        'rarity_filter_rare',
        'rarity_filter_secret',
        'rarity_filter_epic',
        'rarity_filter_common',
        'rarity_filter_brainrot_god',
        'col_brainrot',
        'col_exist_count',
        'col_exist_count_header',
        'col_mutations_traits',
        'col_mutations_traits_short',
        'col_value',
        'col_current_value',
        'col_previous_value',
        'col_change',
        'col_demand',
        'col_trend',
        'col_rarity',
        'col_signal',
        'signal_very_high',
        'signal_medium',
        'signal_low',
        'signal_very_low',
        'signal_extremely_rare',
        'signal_near_unique',
        'signal_lowest',
        'how_h2',
        'how_p1',
        'how_p2',
        'faq_h2',
        'faq_items',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'nav_faq',
        'footer_home',
        'footer_calculator',
        'footer_exist_counts',
        'footer_faq',
        'footer_about',
        'footer_privacy',
        'footer_terms',
        'footer_copyright_rest',
        'footer_disclaimer',
        'language_label',
        'language_current',
        'calculator_offer_title',
        'calculator_meta_title',
        'calculator_meta_description',
        'calculator_h1',
        'calculator_intro',
        'calculator_receive_title',
        'calculator_count_singular',
        'calculator_count_plural',
        'calculator_swap_title',
        'calculator_compare_empty',
        'calculator_compare_both_empty',
        'calculator_compare_need_receive',
        'calculator_compare_need_offer',
        'calculator_clear_all',
        'calculator_help_title',
        'calculator_help_subtitle',
        'calculator_income_check_title',
        'calculator_income_formula',
        'calculator_income_check_body',
        'calculator_value_check_title',
        'calculator_value_formula',
        'calculator_value_check_body',
        'calculator_trait_value_bonuses',
        'calculator_trait_bonus_empty',
        'calculator_select_brainrot',
        'calculator_change',
        'calculator_search_brainrots',
        'calculator_mutation',
        'calculator_traits',
        'calculator_traits_selected',
        'calculator_search_traits',
        'calculator_calculated_income',
        'calculator_recalculate',
        'calculator_add_item',
        'calculator_update_item',
        'calculator_total_income',
        'calculator_total_value',
        'calculator_income_label',
        'calculator_value_label',
        'calculator_base_label',
        'calculator_total_multiplier',
        'calculator_default_mutation',
        'calculator_no_brainrots_found',
        'calculator_fair_trade',
        'calculator_win_trade',
        'calculator_lose_trade',
        'calculator_seo_title',
        'calculator_seo_steps',
        'calculator_values_title',
        'calculator_values_body',
        'calculator_wfl_title',
        'calculator_wfl_body',
        'calculator_mutations_title',
        'calculator_mutations_body',
        'calculator_exist_count_title',
        'calculator_exist_count_body',
        'calculator_exist_count_link',
        'calculator_value_list_link',
        'calculator_tips_title',
        'calculator_tips',
        'calculator_popular_title',
        'calculator_popular_body',
        'calculator_faq_h2',
        'calculator_faq_items',
    ];

    private const I18N_EN = [
        'site_name'           => 'SAB Exist Count',
        'nav_list'            => 'Exist Count List',
        'nav_exist_counts_list' => 'Exist Count List',
        'nav_exist_count_gallery' => 'Exist Count Gallery',
        'nav_value_list'      => 'SAB Values',
        'nav_news'            => 'News',
        'nav_codes'           => 'Codes',
        'nav_games'           => 'Games',
        'nav_calculator'      => 'Calculator',
        'nav_how'             => 'How It Works',
        'nav_rarity'          => 'Rarity Guide',
        'hero_badge'          => 'Community-tracked Steal a Brainrot rarity data',
        'hero_h1_prefix'      => 'Steal a Brainrot',
        'hero_h1_cyan'        => 'Exist Count Tracker',
        'hero_h1_daily_note'  => 'Updated daily',
        'hero_body'           => 'Track Steal a Brainrot exist counts, rarity signals, mutation variants, and trading value notes in one place. Updated regularly with public community data and manual tracking.',
        'home_intro'          => '',
        'hero_cta'            => 'View Exist Counts',
        'hero_disclaimer'     => 'Disclaimer: SAB exist counts may change after spawns, updates, fusing, deletion, voiding, or new feature releases. This page is a community reference, not an official Roblox or developer database.',
        'home_search_h2'      => 'Search Brainrot',
        'home_show_all'       => 'MORE',
        'home_show_top_10'    => 'LESS',
        'home_full_list_cta'  => 'View Full Exist Count List',
        'home_overview_h2'    => 'Overview',
        'home_latest_h2'      => 'Top OG Brainrots',
        'home_latest_intro'   => 'OG-tier Brainrots ranked by the lowest known exist counts.',
        'home_recent_h2'      => 'Recently Changed Exist Counts',
        'home_recent_intro'   => 'Items with the most recent count or value updates.',
        'home_update_h2'      => 'How We Update the Data',
        'home_update_p1'      => 'The tracker combines public community sources, item pages, gallery count records, value references, and automated sync runs.',
        'home_update_p2'      => 'Counts may move when new copies appear, when variants are discovered, or when older entries are corrected by source updates.',
        'date_updated_recently' => 'Updated recently',
        'stats_total'         => 'Items Tracked',
        'stats_lowest'        => 'Lowest Known Count',
        'stats_highest'       => 'Highest Known Count',
        'stats_snapshot'      => 'Data Snapshot',
        'table_h2'            => 'Known Steal a Brainrot Exist Counts',
        'table_intro'         => 'The table follows the public count list from the Steal a Brainrot Wiki and Eldorado. Counts reflect the most recent daily sync.',
        'table_updated'       => 'Updated daily via automated sync.',
        'table_caption'       => 'Steal a Brainrot exist count list — brainrot name, rarity tier, known count, and rarity signal',
        'search_placeholder'  => 'Search Brainrot name, rarity, or count…',
        'search_showing_all'  => 'Showing {total} items',
        'search_showing_filtered' => 'Showing {visible} of {total} items',
        'search_empty'        => 'No matching Brainrots found.',
        'search_empty_title'  => 'No matching Brainrots found.',
        'search_empty_body'   => 'Try another Brainrot name, rarity, or count.',
        'rarity_filter_all'          => 'All',
        'rarity_filter_og'           => 'OG',
        'rarity_filter_legendary'    => 'Legendary',
        'rarity_filter_mythic'       => 'Mythic',
        'rarity_filter_rare'         => 'Rare',
        'rarity_filter_secret'       => 'Secret',
        'rarity_filter_epic'         => 'Epic',
        'rarity_filter_common'       => 'Common',
        'rarity_filter_brainrot_god' => 'Brainrot God',
        'col_brainrot'        => 'Brainrot',
        'col_exist_count'     => 'Exist Count',
        'col_exist_count_header' => 'Exist<br>Count',
        'col_mutations_traits' => 'Mutations<br>Traits',
        'col_mutations_traits_short' => 'M/T',
        'col_value'           => 'Value',
        'col_current_value'   => 'Current Value',
        'col_previous_value'  => 'Previous Value',
        'col_change'          => 'Change',
        'col_demand'          => 'Demand',
        'col_trend'           => 'Trend',
        'col_rarity'          => 'Rarity',
        'col_signal'          => 'Rarity Signal',
        'signal_very_high'    => 'Very high supply',
        'signal_medium'       => 'Medium supply',
        'signal_low'          => 'Low supply',
        'signal_very_low'     => 'Very low supply',
        'signal_extremely_rare' => 'Extremely rare',
        'signal_near_unique'  => 'Near unique',
        'signal_lowest'       => 'Lowest known',
        'how_h2'              => 'What Does Exist Count Mean?',
        'how_p1'              => 'In Steal a Brainrot, <strong>exist count</strong> refers to how many copies of a specific brainrot are known or believed to exist across the game. When a brainrot has a lower exist count, players often treat it as rarer and potentially more valuable in trades.',
        'how_p2'              => 'The count can change when new copies spawn or when units are removed through fusing, deletion, voiding, or other game systems. That is why two public pages may show different numbers if updated on different dates.',
        'rarity_h2'           => 'SAB Exist Count Rarity Guide',
        'rarity_high_title'   => 'High Exist Count',
        'rarity_high_body'    => 'Usually easier to find and less likely to be treated as an ultra-rare collector item. These brainrots are useful as baseline comparisons.',
        'rarity_medium_title' => 'Medium Exist Count',
        'rarity_medium_body'  => 'These can become valuable if demand grows, if the source becomes unavailable, or if specific mutations become harder to obtain.',
        'rarity_low_title'    => 'Low Exist Count',
        'rarity_low_body'     => 'Often the strongest rarity signal. Low-count brainrots are usually watched closely by traders, collectors, and value list communities.',
        'faq_h2'              => 'Steal a Brainrot Exist Count FAQ',
        'faq_items'           => [
            ['What is a good exist count in Steal a Brainrot?', 'A lower exist count usually means stronger rarity. However, "good" depends on demand, source, mutation, and how many players want that specific brainrot.'],
            ['Is exist count the same as value?', 'No. Exist count is supply data. Value depends on supply, demand, trading activity, rarity perception, event history, and player interest.'],
            ['Where does the full exist count table come from?', 'The current table is based on the public Steal a Brainrot Wiki Exist Counts page and Eldorado. FandomWire is used as supporting context, not as the primary table source.'],
            ['How often should SAB exist counts be updated?', 'The list is updated after major Steal a Brainrot patches, Admin Abuse events, Lucky Block changes, new limited brainrots, or confirmed wiki and community count corrections.'],
            ['Can exist count go up over time in Steal a Brainrot?', 'Yes. Exist count can increase when new copies spawn through regular gameplay, Admin Abuse events, Lucky Block changes, or game updates. It can also decrease when copies are removed through fusing, deletion, or voiding.'],
            ['Does fusing or deleting a brainrot reduce its exist count?', 'Yes. Fusing, deletion, and voiding remove copies from the game, which can lower the total exist count over time. This is one reason why the count shown on different pages or at different dates may not match.'],
        ],
        'exist_count'         => 'Exist Count',
        'value'               => 'Value',
        'demand'              => 'Demand',
        'rarity'              => 'Rarity',
        'last_changed'        => 'Last Changed',
        'variants'            => 'Variants',
        'history'             => 'History',
        'sources'             => 'Data Sources',
        'variant_name'        => 'Variant',

        'meta_title'          => 'Steal a Brainrot Exist Count Tracker | SABExistCount.com',
        'meta_description'    => 'Check updated SAB exist count data for Steal a Brainrot, including rarity tiers, value trends, mutation variants, and update history in one simple tracker.',
        'meta_keywords'       => '',

        'exist_counts_list_meta_title'       => 'Steal a Brainrot Exist Count List ({month}) | SAB Brainrots',
        'exist_counts_list_meta_description' => 'Updated Steal a Brainrot exist count list (updated {month}) for all SAB brainrots. Search counts by name or rarity, compare supply signals, and open item pages for variants and history.',
        'exist_counts_list_h1'               => 'Steal a Brainrot Exist Count List (Updated {month})',
        'exist_counts_list_h1_prefix'        => 'Steal a Brainrot',
        'exist_counts_list_h1_cyan'          => 'Exist Count List (Updated {month})',
        'exist_counts_list_month_badge'      => '{month}',
        'exist_counts_list_intro'            => 'Updated SAB exist count list for all Steal a Brainrot items. Search by Brainrot name or rarity, compare known counts and supply signals, then open any item for variants, history, and source notes.',
        'exist_counts_list_table_h2'         => 'Brainrots with a known exist count',
        'exist_counts_list_table_caption'    => 'Steal a Brainrot exist count list — Brainrot item, exist count, rarity tier, and rarity signal',
        'exist_counts_list_updated'          => 'Counts sync with the main tracker and update daily when sources refresh.',
        'exist_counts_list_stat_label'       => 'Items with exist count',
        'exist_counts_list_page_info'        => '{start}–{end} of {total}',
        'exist_counts_list_page_prev'        => 'Prev',
        'exist_counts_list_page_next'        => 'Next',
        'exist_counts_list_tools_label'        => 'Tools',
        'exist_counts_list_tool_calculator'    => 'Calculator',
        'exist_counts_list_tool_value_list'    => 'Value List',
        'exist_counts_list_tool_codes'         => 'Codes',
        'exist_counts_list_tool_gallery'       => 'Gallery',
        'exist_counts_list_faq_h2'             => 'Frequently Asked Questions About Steal a Brainrot Exist Count',
        'exist_counts_list_faq_calculator_link' => 'SAB Trade Calculator',
        'exist_counts_list_faq_value_list_link' => 'Value List',
        'exist_counts_list_faq_q1' => 'What is exist count in Steal a Brainrot?',
        'exist_counts_list_faq_a1' => 'Exist count is a supply signal: how many copies of a Brainrot are known or believed to exist at a given time. Lower exist counts usually mean stronger scarcity and more attention in trades. This list covers Brainrots with known counts and clearly marks estimated ranges when a firm count is not available.',
        'exist_counts_list_faq_q2' => 'How do I check a Brainrot exist count?',
        'exist_counts_list_faq_a2' => 'In-game, open your Index and inspect a Brainrot you own — the tooltip may show an exist count when one has been assigned. On this page, use the search bar and rarity filters to find any listed Brainrot, then open its detail page for variants, history, and supply notes. The tracker also covers items you may not own yet, so you can research supply before trading.',
        'exist_counts_list_faq_q3' => 'How often is this exist count list updated?',
        'exist_counts_list_faq_a3' => 'We refresh the list after major Steal a Brainrot patches, Admin Abuse events, Lucky Block changes, and other supply-moving updates. Check the month badge near the page title to see the current update window. When a firm public count is missing, the row may show an estimated range with a clear marker instead of inventing a single number.',
        'exist_counts_list_faq_q4' => 'Where does the exist count data come from?',
        'exist_counts_list_faq_a4' => 'Counts are based on official public releases and in-game Index information, then checked against recent patch notes when sources disagree. We prefer the newest official figure. Unverified rumor numbers are not treated as confirmed counts.',
        'exist_counts_list_faq_q5' => 'What is the rarest Brainrot by exist count?',
        'exist_counts_list_faq_a5' => 'Sort this list by Exist Count to surface the lowest-supply rows first. Brainrots with very low counts are usually treated as extremely scarce, but totals change when copies are fused, voided, destroyed, or newly spawned. Use rarity filters to narrow tiers, then judge scarcity from the Exist Count column.',
        'exist_counts_list_faq_q6' => 'Do exist counts ever go down?',
        'exist_counts_list_faq_a6' => 'Yes. Exist counts can fall when copies leave circulation through fusing, voiding, deletion, or other removal systems. They can also rise when new copies appear through gameplay, events, Lucky Blocks, or Admin Abuse spawns. Tracking direction over time matters as much as the current number.',
        'exist_counts_list_faq_q7' => 'What is the difference between exist count and mutation?',
        'exist_counts_list_faq_a7' => 'Exist count measures supply — how many copies are known. Mutation and traits describe which version of a Brainrot you have. The list column is a Brainrot-level exist reference; detail pages add mutation and trait context so you can judge both overall supply and variant-specific interest.',
        'exist_counts_list_faq_q8' => 'How does exist count affect trade value?',
        'exist_counts_list_faq_a8' => 'Lower exist count usually supports higher trade interest, and a count that keeps falling can feel scarcer than one that is rising. Mutation and trait rarity can stack on top of supply. Use the {calculator_link} with this list — and the {value_list_link} when you need price context — before accepting a trade.',

        'exist_count_gallery_meta_title'       => 'Steal a Brainrot Exist Count Gallery | SabExistCount',
        'exist_count_gallery_meta_description' => 'Browse the Steal a Brainrot exist count gallery with local images, rarity filters, and timely updates for SAB Brainrot exist count references.',
        'exist_count_gallery_h1'               => 'Steal a Brainrot Exist Count Gallery',
        'exist_count_gallery_intro'            => 'A visual gallery for Brainrots listed on the public Exist Counts gallery. Filter by rarity and open any image for a larger view.',
        'exist_count_gallery_update_note'      => 'SABExistCount.com is an independent Steal a Brainrot exist count website. Gallery images and exist count references are updated regularly when public community sources change.',
        'exist_count_gallery_stat_label'       => 'Gallery items',
        'exist_count_gallery_all'              => 'All',
        'exist_count_gallery_empty'            => 'No gallery data found. Run php artisan seo:sab-fandom-gallery first.',
        'exist_count_gallery_image_missing'    => 'Image not available',
        'exist_count_gallery_faq_h2'           => 'Steal a Brainrot Exist Count Gallery FAQ',
        'exist_count_gallery_faq_items'        => [
            ['What is the best Steal a Brainrot exist count website?', 'SABExistCount.com is an independent community reference website for checking Steal a Brainrot exist count information, rarity signals, values, and gallery images in one place. It is not affiliated with Roblox or the game developers.'],
            ['How often is this Steal a Brainrot exist count gallery updated?', 'The gallery and exist count references are updated regularly when public community sources change. This helps players check timely images and supply references without relying on old screenshots.'],
            ['What does exist count mean in Steal a Brainrot?', 'Exist count usually means how many copies of a Brainrot are known or believed to exist. Lower counts often make an item feel rarer, but trading value also depends on demand, events, mutations, and player interest.'],
            ['Why do some exist counts or images change?', 'Counts and images can change after game updates, new public leaks, source corrections, spawns, deletions, fusing, or community page edits. Treat the gallery as a current reference, not an official Roblox database.'],
        ],

        'value_list_meta_title'       => 'Steal a Brainrot Value List ({month}) – Live Values & Rarity',
        'value_list_meta_description' => 'Check the latest Steal a Brainrot Value List (updated {month}) — Brainrot values, rarity rankings, mutation notes, and demand trends before you trade.',
        'value_list_h1'               => 'Steal a Brainrot Value List (Updated {month})',
        'value_list_intro'            => 'This page shows current value, previous value, change, demand, and trend for each tracked Brainrot. All data comes from the SAB Trading Calculator on this site. Previous values and movement are shown when history is available. These are community references — not official prices or guaranteed trade outcomes.',
        'value_list_table_h2'         => 'Brainrots with a listed value',
        'value_list_table_caption'    => 'Steal a Brainrot value list — Brainrot item, current value, previous value, demand, and trend',
        'value_list_updated'          => 'Values sync with the SAB Trading Calculator data on this site. Previous values and movement are shown when calculator history is available.',
        'value_list_stat_label'       => 'Brainrots tracked',
        'value_list_page_info'        => '{start}–{end} of {total}',
        'value_list_page_prev'        => 'Prev',
        'value_list_page_next'        => 'Next',
        'value_list_faq_h2'           => 'Steal a Brainrot Value List FAQ',
        'value_list_faq_exist_count_link' => 'Exist Count List',
        'value_list_faq_calculator_link' => 'SAB Trading Calculator',
        'value_list_faq_q1' => 'What is the Steal a Brainrot Values List?',
        'value_list_faq_a1' => 'The Steal a Brainrot Values List is a searchable list of Brainrots showing current value, previous value, value change, demand, and trend information.',
        'value_list_faq_q2' => 'What is the most valuable Brainrot right now?',
        'value_list_faq_a2' => 'Based on current tracked data, {top_items} rank among the highest tracked values. Demand and mutations can shift rankings quickly, so confirm the latest number before you trade.',
        'value_list_faq_a2_empty' => 'The most valuable Brainrot changes frequently based on market conditions. Check the list above sorted by current value to see the latest rankings.',
        'value_list_faq_q3' => 'Where does the value data come from and how often is it updated?',
        'value_list_faq_a3' => 'All data comes from the {calculator_link} on this site. Previous values and movement are shown when calculator history is available. Values can refresh multiple times per day as community estimates update.',
        'value_list_faq_q4' => 'What determines a Brainrot\'s value?',
        'value_list_faq_a4' => 'A Brainrot\'s value depends on rarity, supply and demand, exist count, mutations, traits, and player interest. Lower exist counts usually signal stronger rarity — check the {exist_count_link} for supply data.',
        'value_list_faq_q5' => 'Are these official Steal a Brainrot values?',
        'value_list_faq_a5' => 'No. The values are community reference data for comparing Brainrots and are not official prices or guaranteed trade outcomes.',
        'value_list_faq_q6' => 'How should I use the value list before a trade?',
        'value_list_faq_a6' => 'Search for the Brainrots involved and compare their current values, recent changes, demand, and trends. For a side-by-side comparison, use the {calculator_link} to check the W/F/L result before accepting.',
        'value_list_faq_q7' => 'Why do two Brainrots with the same rarity have different values?',
        'value_list_faq_a7' => 'Even within the same rarity tier, differences in exist count, mutations, traits, demand, and income potential create different trade values. Compare details on the {exist_count_link} and use the {calculator_link} to evaluate specific trades.',
        'value_list_faq_q8' => 'Why do some Brainrots have limited previous-value information?',
        'value_list_faq_a8' => 'Historical information is shown when available. Some Brainrots may not have enough recent history for a complete previous-value or movement comparison.',

        'nav_faq'             => 'FAQ',
        'breadcrumb_home'     => 'Home',
        'breadcrumb_news'     => 'News',

        'news_list_meta_title'       => 'Steal a Brainrot Exist Count News',
        'news_list_meta_description' => 'Read the latest Steal a Brainrot exist count news, tracker updates, rarity notes, and explanations behind SAB counts and value changes.',
        'news_list_h1'               => 'Steal a Brainrot Exist Count News',
        'news_list_intro'            => 'Latest articles and tracker updates, newest first.',
        'news_list_empty'            => 'No published articles yet.',
        'news_toc_title'             => 'On this page',
        'news_sidebar_aria'          => 'On this page, SAB tools and popular guides',
        'news_tools_title'           => 'SAB Tools',
        'news_tools_subtitle'        => 'Check values, trades and exist counts',
        'news_tools_value_list'      => 'SAB Values',
        'news_tools_value_list_desc' => 'Current trading values & trends',
        'news_tools_calculator'      => 'Trading Calculator',
        'news_tools_calculator_desc' => 'Compare offers and check W/F/L',
        'news_tools_exist_count'     => 'Exist Count List',
        'news_tools_exist_count_desc' => 'Check supply and rarity signals',
        'news_tools_codes'           => 'Steal a Brainrot Codes',
        'news_tools_codes_desc'      => 'Latest codes, rewards and updates',
        'news_tools_gallery'         => 'Exist Count Gallery',
        'news_tools_gallery_desc'    => 'Browse Brainrots visually',
        'news_tools_cta'             => 'Check Your Trade',
        'news_guides_title'          => 'Popular Guides',
        'news_guides_subtitle'       => 'Trading, values and rarity guides',
        'news_guides_all'            => 'View All SAB Guides',
        'news_share_title'           => 'Social Share or Summarize with AI',

        'item_page_exist_count_label' => 'Exist Count',

        'footer_home'         => 'Home',
        'footer_calculator'   => 'SAB Calculator',
        'footer_exist_counts' => 'Exist Counts',
        'footer_faq'          => 'FAQ',
        'footer_about'        => 'About Us',
        'footer_privacy'      => 'Privacy Policy',
        'footer_terms'        => 'Terms of Service',
        'footer_copyright_rest' => 'SABExistCount.com. Community reference site for Steal a Brainrot players.',
        'footer_disclaimer'   => 'This website is not affiliated with Roblox, Steal a Brainrot, or the game developers. All names, game references, and related assets belong to their respective owners. Data is collected from public community references and should be treated as informational.',

        'history_empty'       => 'No history data yet. Check back after the first daily sync.',
        'history_not_enough'  => 'Not enough history yet.',
        'item_gallery_images_h2' => 'Exist Count Gallery Images',
        'language_label'      => 'Language',
        'language_current'    => 'EN',

        'calculator_offer_title' => 'Your Offer',
        'calculator_meta_title' => 'SAB Calculator - Steal a Brainrot',
        'calculator_meta_description' => 'Free SAB trading values calculator for Steal a Brainrot. Compare brainrot trade value side-by-side, check W/F/L results, and calculate income with mutations and traits.',
        'calculator_h1' => 'Steal a Brainrot Trading Calculator',
        'calculator_intro' => 'Check SAB trading values and compare Brainrot trade value with mutations, traits, income and W/F/L before you accept.',
        'calculator_receive_title' => 'You Receive',
        'calculator_count_singular' => 'item',
        'calculator_count_plural' => 'items',
        'calculator_swap_title' => 'Swap sides',
        'calculator_compare_empty' => 'Add items to compare trades',
        'calculator_compare_both_empty' => 'Add items to both sides to check W/F/L.',
        'calculator_compare_need_receive' => 'Add items to You Receive to compare the trade.',
        'calculator_compare_need_offer' => 'Add items to Your Offer to compare the trade.',
        'calculator_clear_all' => 'Clear All',
        'calculator_help_title' => 'How values are calculated',
        'calculator_help_subtitle' => 'Formulas and trait bonuses',
        'calculator_income_check_title' => 'Income Formula',
        'calculator_income_formula' => 'Base × (Mutation + Traits)',
        'calculator_income_check_body' => 'Trait income multipliers stack additively with the mutation multiplier. Traits with a multiplier below 1x are applied multiplicatively after the sum.',
        'calculator_value_check_title' => 'Value Formula',
        'calculator_value_formula' => 'Mutation Value × (1 + Trait Bonuses × Streak)',
        'calculator_value_check_body' => 'Each trait adds a percentage bonus to the mutation value. Bonuses stack additively and are multiplied by the streak bonus when 3 or 6 traits are selected.',
        'calculator_trait_value_bonuses' => 'Trait Value Bonuses',
        'calculator_trait_bonus_empty' => 'Trait bonus data will appear after calculator sync.',
        'calculator_select_brainrot' => 'Select Brainrot',
        'calculator_change' => 'Change',
        'calculator_search_brainrots' => 'Search brainrots...',
        'calculator_mutation' => 'Mutation',
        'calculator_traits' => 'Traits',
        'calculator_traits_selected' => '{count} selected',
        'calculator_search_traits' => 'Search traits...',
        'calculator_calculated_income' => 'Calculated Income',
        'calculator_recalculate' => 'Recalculate',
        'calculator_add_item' => 'Add Item',
        'calculator_update_item' => 'Update Item',
        'calculator_total_income' => 'Total Income',
        'calculator_total_value' => 'Total Value',
        'calculator_income_label' => 'Income',
        'calculator_value_label' => 'Value',
        'calculator_base_label' => 'Base',
        'calculator_total_multiplier' => 'Total Multiplier',
        'calculator_default_mutation' => 'Default',
        'calculator_no_brainrots_found' => 'No matching Brainrots found.',
        'calculator_fair_trade' => 'Fair trade',
        'calculator_win_trade' => 'Win trade',
        'calculator_lose_trade' => 'Lose trade',
        'calculator_seo_title' => 'How to Use the SAB Calculator',
        'calculator_seo_steps' => [
            'Add the Brainrots you are offering.',
            'Add the Brainrots you want to receive.',
            'Select the correct mutation for each Brainrot.',
            'Add traits if the Brainrot has any.',
            'Compare total value and income to see if the trade looks like a win, fair trade or lose.',
        ],
        'calculator_values_title' => 'What Are SAB Trading Values?',
        'calculator_values_body' => 'SAB trading values are estimated player-to-player references for Steal a Brainrot items. Values can move when rarity, demand, income, mutations, traits or exist count change. Use them as a trading guide, then compare both sides again before accepting a deal.',
        'calculator_wfl_title' => 'How to Check W/F/L in Steal a Brainrot',
        'calculator_wfl_body' => 'W/F/L means Win, Fair or Lose. A win trade means the Brainrots you receive are estimated to be worth more than your offer. A fair trade means both sides are close in value. A lose trade means your offer may be worth more than what you receive.',
        'calculator_mutations_title' => 'How Mutations and Traits Affect SAB Trade Value',
        'calculator_mutations_body' => 'Mutations and traits can greatly change a Brainrot trade value. A rare mutation may increase income, demand and estimated value. Traits can also add extra bonuses, especially when several traits are selected together.',
        'calculator_exist_count_title' => 'Why Exist Count Matters in SAB Trading',
        'calculator_exist_count_body' => 'Exist count is an important signal when checking rare Brainrots. A lower exist count usually means fewer copies are available, which can make the Brainrot more desirable in trades.',
        'calculator_exist_count_link' => 'View Steal a Brainrot Exist Count List',
        'calculator_value_list_link' => 'View Steal a Brainrot Value List',
        'calculator_tips_title' => 'SAB Trading Tips',
        'calculator_tips' => [
            'Check both value and income before trading.',
            'Do not judge rare Brainrots only by name.',
            'Always check mutations and traits.',
            'Compare low exist count Brainrots carefully.',
            'Recheck values after major game updates.',
            'Be careful with trades that look too good to be true.',
        ],
        'calculator_popular_title' => 'Popular Brainrots to Check Before Trading',
        'calculator_popular_body' => 'Popular and rare Brainrots are worth checking carefully because small differences in mutations, traits, income or exist count can affect their SAB trading values.',
        'calculator_faq_h2' => 'SAB Calculator FAQ',
        'calculator_faq_items' => [
            ['What is a SAB trading values calculator?', 'It is a free Steal a Brainrot trading calculator that compares SAB trading values on both sides of a trade, including income, mutations, traits and the W/F/L result, before you accept.'],
            ['How do I check Brainrot trade value before trading?', 'Add the Brainrots on both sides of the trade, choose the correct mutation and traits, then compare total value, income and the W/F/L result.'],
            ['What does W/F/L mean in SAB trading?', 'W/F/L means Win, Fair or Lose. It helps players understand whether the Brainrots they receive are worth more than, close to, or lower than the Brainrots they offer.'],
            ['Do mutations and traits change trade value?', 'Yes. Mutations and traits can change income and estimated trade value, so two Brainrots with the same name may have different results.'],
            ['Are SAB trading values official?', 'No. SAB trading values are player-to-player references, not official prices. Use them as a guide and recheck demand, exist count, mutations and traits before trading.'],
            ['Is this Steal a Brainrot trading calculator free?', 'Yes. The SABExistCount trading calculator is free to use and helps players compare SAB trading values before accepting a trade.'],
        ],
    ];

    /**
     * @return array<string, array<string, string>>
     */
    public static function defaultSabCopy(): array
    {
        return [
            'home' => [
                'meta_title' => self::I18N_EN['meta_title'],
                'meta_description' => self::I18N_EN['meta_description'],
                'hero_body' => self::I18N_EN['hero_body'],
            ],
            'exist_counts_list' => [
                'meta_title' => self::I18N_EN['exist_counts_list_meta_title'],
                'meta_description' => self::I18N_EN['exist_counts_list_meta_description'],
                'h1' => self::I18N_EN['exist_counts_list_h1'],
                'intro' => self::I18N_EN['exist_counts_list_intro'],
                'table_h2' => self::I18N_EN['exist_counts_list_table_h2'],
                'table_caption' => self::I18N_EN['exist_counts_list_table_caption'],
                'updated' => self::I18N_EN['exist_counts_list_updated'],
            ],
            'value_list' => [
                'meta_title' => self::I18N_EN['value_list_meta_title'],
                'meta_description' => self::I18N_EN['value_list_meta_description'],
                'h1' => self::I18N_EN['value_list_h1'],
                'intro' => self::I18N_EN['value_list_intro'],
                'table_h2' => self::I18N_EN['value_list_table_h2'],
                'table_caption' => self::I18N_EN['value_list_table_caption'],
                'updated' => self::I18N_EN['value_list_updated'],
            ],
            'calculator' => self::defaultCalculatorCopy(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function defaultCalculatorCopy(): array
    {
        return [
            'meta_title' => 'SAB Calculator - Steal a Brainrot',
            'meta_description' => 'Free SAB trading values calculator for Steal a Brainrot. Compare brainrot trade value side-by-side, check WFL results, and calculate income with mutations and traits.',
            'h1' => 'Steal a Brainrot Trading Calculator',
            'intro' => 'Check SAB trading values and compare brainrot trade value with mutations, traits, income and WFL before you accept.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultSiteSettings(): array
    {
        return [
            'site_mode' => SabSiteContext::SITE_MODE_FULL,
            'data_site_slug' => '',
            'local_preview_base_url' => '',
            'sab_copy' => self::defaultSabCopy(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultSabcalculatorSiteSettings(): array
    {
        return [
            'site_mode' => SabSiteContext::SITE_MODE_CALCULATOR_ONLY,
            'data_site_slug' => self::SITE_SLUG,
            'local_preview_base_url' => 'http://127.0.0.1:8083',
            'brand' => [
                'site_name' => 'SAB Calculator',
                'logo_html' => 'SAB<span class="sab-brand-accent">Calculator</span>.com',
                'og_site_name' => 'SAB Calculator',
            ],
            'sab_copy' => [
                'calculator' => [
                    'meta_title' => 'SAB Calculator | SABCalculator.com',
                    'meta_description' => 'Compare Steal a Brainrot trades side-by-side. Add brainrots with mutations and traits to see total income, Robux value and WFL before you trade.',
                    'h1' => 'Steal a Brainrot Calculator',
                    'intro' => 'Use the SAB trade calculator to compare Brainrot values, income, mutations, and traits before you accept a trade.',
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedRenderModules(): array
    {
        return [
            self::RENDER_MODULE_ALL,
            self::RENDER_MODULE_NEWS,
            self::RENDER_MODULE_PRODUCTS,
            self::RENDER_MODULE_CALCULATOR,
        ];
    }

    /**
     * @return list<string>
     */
    public static function supportedLocales(): array
    {
        return self::MULTILINGUAL_PAGE_LOCALES;
    }

    public static function defaultLocale(): string
    {
        return self::DEFAULT_LOCALE;
    }

    /**
     * Public origin for canonical / sitemap / robots on the www host.
     * Production www is normalized to apex sabexistcount.com.
     */
    public function publicWwwOrigin(?SeoSite $site = null): string
    {
        if ($site === null) {
            try {
                $site = SeoSite::query()->where('slug', self::SITE_SLUG)->first();
            } catch (\Throwable) {
                $site = null;
            }
        }
        $base = rtrim((string) ($site?->base_url ?: ''), '/');
        if ($base !== '') {
            return $this->normalizeWwwOrigin($base);
        }

        $host = (string) config('sab.hosts.www', 'www.sabexistcount.com');
        $scheme = str_contains($host, '.lab') || str_contains($host, 'localhost') ? 'http' : 'https';

        return $scheme.'://'.$host;
    }

    public function buildLiveSitemapXml(): string
    {
        return app(SabSitemapBuilder::class)->indexXml();
    }

    public function buildLiveSitemapShardXml(string $name): string
    {
        return app(SabSitemapBuilder::class)->shardXml($name);
    }

    public function loadItemsForSitemap(SeoGame $game): Collection
    {
        return $this->loadItems($game);
    }

    public function loadPublishedNewsForSitemap(SeoSite $site): Collection
    {
        return $this->loadPublishedNews($site);
    }

    public function loadPublishedStaticPagesForSitemap(SeoSite $site): Collection
    {
        return $this->loadPublishedStaticPages($site);
    }

    public function codesDataForSitemap(): array
    {
        return $this->codesData();
    }

    public function localePublicUrlForSitemap(string $baseUrl, string $locale, string $relativePath): string
    {
        return $this->localePublicUrl($baseUrl, $locale, $relativePath);
    }

    public static function sitemapGameSlug(): string
    {
        return self::GAME_SLUG;
    }

    public static function sitemapDefaultLocale(): string
    {
        return self::DEFAULT_LOCALE;
    }

    public static function sitemapShouldRenderProduct(SeoItem $item): bool
    {
        return self::shouldRenderProductHtml($item);
    }

    public static function sitemapShouldIndexProductSlug(string $slug): bool
    {
        return self::shouldIndexProductSlug($slug);
    }

    private function normalizeWwwOrigin(string $origin): string
    {
        $parts = parse_url($origin);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        if ($host === 'www.sabexistcount.com') {
            $host = 'sabexistcount.com';
        }

        return $scheme.'://'.$host.$port;
    }

    public static function normalizeLocale(?string $locale): string
    {
        $locale = trim((string) $locale);

        return in_array($locale, self::MULTILINGUAL_PAGE_LOCALES, true)
            ? $locale
            : self::DEFAULT_LOCALE;
    }

    public static function localeLabel(string $locale): string
    {
        return match ($locale) {
            'pt' => 'PT',
            'es' => 'ES',
            'de' => 'DE',
            'ru' => 'RU',
            'fr' => 'FR',
            'tr' => 'TR',
            'pl' => 'PL',
            default => 'EN',
        };
    }

    public static function localeHreflang(string $locale): string
    {
        return $locale === 'pt' ? 'pt-BR' : $locale;
    }

    public static function localizedPreviewPath(string $locale, string $pageSlug = 'index'): string
    {
        $locale = self::normalizeLocale($locale);
        $pageSlug = in_array($pageSlug, self::MULTILINGUAL_PAGE_SLUGS, true) ? $pageSlug : 'index';
        $localePrefix = self::publicUrlPrefix($locale);

        if ($pageSlug === 'index') {
            return $localePrefix;
        }

        return $localePrefix === '' ? "/{$pageSlug}" : "{$localePrefix}/{$pageSlug}";
    }

    public static function publicUrlPrefix(string $locale = self::DEFAULT_LOCALE): string
    {
        $locale = self::normalizeLocale($locale);
        $base = rtrim((string) config('sab.url_prefix', ''), '/');

        return $locale === self::DEFAULT_LOCALE
            ? $base
            : ($base === '' ? "/{$locale}" : "{$base}/{$locale}");
    }

    private function observationsTableExists(): bool
    {
        static $exists;

        return $exists ??= Schema::hasTable('seo_item_observations');
    }

    public static function gamePublicPath(string $slug = self::PAGE_GAME): string
    {
        return self::PAGE_GAMES_DIR . '/' . $slug;
    }

    public static function gamesIndexPublicPath(): string
    {
        return self::PAGE_GAMES_DIR;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function gamesCatalog(): array
    {
        return [
            self::PAGE_GAME => [
                'slug' => self::PAGE_GAME,
                'name' => 'Steal a Brainrot Simulator',
                'category' => 'Simulator',
                'hot' => true,
                'isNew' => false,
                'cardBlurb' => 'Learn the buy, steal, and earn loop in your browser.',
                'embedUrl' => self::GAME_EMBED_URL,
                'coverSrc' => self::GAME_COVER_SRC,
                'title' => 'Steal a Brainrot Simulator – Play Free Online | SABExistCount',
                'description' => 'Play Steal a Brainrot Simulator in your browser. Learn the buy, steal, and earn loop, then check exist counts, values, and codes on SAB Exist Count.',
                'h1' => 'Steal a Brainrot Simulator',
            ],
            self::PAGE_GAME_ROB => [
                'slug' => self::PAGE_GAME_ROB,
                'name' => 'Rob Brainrot 2',
                'category' => 'Simulator',
                'hot' => true,
                'isNew' => true,
                'cardBlurb' => 'Rob meme brainrots from the conveyor and grow a chaotic base.',
                'embedUrl' => self::GAME_ROB_EMBED_URL,
                'coverSrc' => self::GAME_ROB_COVER_SRC,
                'title' => 'Rob Brainrot 2 – Play Online Free | SABExistCount',
                'description' => 'Play Rob Brainrot 2 online free. Rob meme brainrots, spend cash in the shop, then check exist counts, codes, and rarity on SAB Exist Count. No download, instant play.',
                'h1' => 'Rob Brainrot 2',
            ],
        ];
    }

    public static function gameSlugPattern(): string
    {
        return implode('|', array_map(static fn (string $slug): string => preg_quote($slug, '/'), array_keys(self::gamesCatalog())));
    }

    /**
     * Full /games list copy. Only catalog slugs are playable; the rest stay planned text.
     *
     * @return list<array{name: string, slug: ?string, status: string, body: string}>
     */
    public static function gamesRoadmap(): array
    {
        return [
            [
                'name' => 'Steal a Brainrot Simulator',
                'slug' => self::PAGE_GAME,
                'status' => 'live',
                'body' => 'Steal a Brainrot Simulator is the unofficial browser version of the Roblox meme tycoon. You buy brainrots from the conveyor, steal from unlocked bases, and lock your own collection so it keeps earning. Use this page to learn the loop, then check exist counts, rarity, and codes on SAB Exist Count before you trade.',
            ],
            [
                'name' => 'Rob Brainrot 2',
                'slug' => self::PAGE_GAME_ROB,
                'status' => 'live',
                'body' => 'Rob Brainrot 2 is the shorter rob-first loop with a shop, weapons, and a luck spin. Grab meme brainrots from the conveyor, carry them home, and grow cash while other bases stay unlocked. Play it when you want a faster session than the full simulator, then come back to values and exist counts on this site.',
            ],
            [
                'name' => 'Italian Brainrot Clicker',
                'slug' => null,
                'status' => 'planned',
                'body' => 'Italian Brainrot Clicker is a planned tap-to-collect page: one click adds another Italian brainrot and the pile grows. It is not playable here yet. When it ships, the card will still point at exist counts and rarity so a clicker session can end on real supply data.',
            ],
            [
                'name' => 'Plants vs Brainrots',
                'slug' => null,
                'status' => 'planned',
                'body' => 'Plants vs Brainrots is a planned tower-defense take on the same roster: hold a lane while meme brainrots push in. It is listed for the plants vs brainrots search cluster, not as a live player. Until a page exists, use the exist count list and calculator on this site.',
            ],
            [
                'name' => 'Brainrot Randomizer',
                'slug' => null,
                'status' => 'planned',
                'body' => 'Brainrot Randomizer is a planned draw tool that would roll a character card with live exist count and rarity from this site. That is the data moat a generic randomizer cannot copy. The page is not built yet, so use the exist count list if you want to pick a brainrot by supply today.',
            ],
            [
                'name' => 'Guess the Exist Count',
                'slug' => null,
                'status' => 'planned',
                'body' => 'Guess the Exist Count is a planned quiz: see a brainrot, guess how many copies are tracked, then reveal the live number. It stays on the roadmap because the answers have to come from this site’s exist counts. There is no guess page yet.',
            ],
            [
                'name' => 'Steal a Brainrot Tycoon',
                'slug' => null,
                'status' => 'planned',
                'body' => 'Steal a Brainrot Tycoon is a planned longer management loop around the same buy-and-earn fantasy. It is not live on SAB Exist Count. Play the simulator for the current browser version, then use values and exist counts when you go back to Roblox.',
            ],
        ];
    }

    public static function normalizeRenderModuleOption(?string $raw): string
    {
        $m = strtolower(trim((string) $raw));
        if ($m === '') {
            return self::RENDER_MODULE_ALL;
        }
        if (! in_array($m, self::allowedRenderModules(), true)) {
            throw new \InvalidArgumentException(
                'Invalid module "' . ($raw ?? '') . '". Allowed: ' . implode(', ', self::allowedRenderModules())
            );
        }

        return $m;
    }

    /**
     * @param  ?string  $outputPathOverride  Non-empty: write static files here for this run only (absolute path, or relative to backend `base_path()`). Overrides `seo_sites.output_path`.
     * @param  string  $module  {@see self::RENDER_MODULE_ALL} full site; partial modules only render their HTML files and skip `sitemap.xml`, legal pages, `data/*.json`.
     */
    public function render(?string $outputPathOverride = null, string $module = self::RENDER_MODULE_ALL, string $siteSlug = self::SITE_SLUG): void
    {
        $ctx = new SabSiteContext($siteSlug);
        if ($ctx->isCalculatorOnly()) {
            $this->renderCalculatorSite($outputPathOverride, $ctx);

            return;
        }

        $module = self::normalizeRenderModuleOption($module);

        $site = $ctx->resolve();
        $game = SeoGame::where('seo_site_id', $site->id)
            ->where('slug', self::GAME_SLUG)
            ->firstOrFail();

        $outputPath = $this->filesystemOutputForRender($site, $outputPathOverride);
        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');

        $items = $module === self::RENDER_MODULE_NEWS ? new Collection : $this->loadItems($game);
        $news = in_array($module, [self::RENDER_MODULE_PRODUCTS, self::RENDER_MODULE_CALCULATOR], true)
            ? new Collection
            : $this->loadPublishedNews($site);
        $i18n = $this->loadI18n($site);

        $this->renderLocale(self::DEFAULT_LOCALE, $outputPath, $baseUrl, $items, $news, $i18n, $module);

        if ($module === self::RENDER_MODULE_ALL) {
            foreach ($this->nonDefaultHomeLocales() as $locale) {
                $this->renderLocaleMarketingPages($locale, $outputPath, $baseUrl, $items, $i18n);
            }
        }

        if ($module === self::RENDER_MODULE_ALL) {
            $this->renderStaticPages($outputPath, $baseUrl);
            $this->renderSitemap($outputPath, $baseUrl, $items, $news, $this->loadPublishedStaticPages($site), self::HOME_LOCALES, [self::DEFAULT_LOCALE]);
            $this->renderRobotsTxt($outputPath, $baseUrl);
            $this->renderDataFiles($outputPath, $items);
        } else {
            Log::warning("seo:sab-render: module={$module} — skipped sitemap.xml, robots.txt, legal *.html, data/*.json; run full `seo:sab-render` before deploy.");
        }

        $this->syncStaticCssToWebsite($outputPath, $ctx);
        $this->syncSabExistCountFavicon($outputPath);
        $this->syncSabGag2CalculatorImages($outputPath);
        $this->syncSabCodesImages($outputPath);
        $this->syncSabGameImages($outputPath);

        if ($module === self::RENDER_MODULE_ALL) {
            Log::info('seo:sab-render [en] rendered ' . $items->count() . ' items; locale home pages: ' . implode(',', $this->nonDefaultHomeLocales()));
        } elseif ($module === self::RENDER_MODULE_NEWS) {
            Log::info('seo:sab-render [en] news only: ' . $news->where('locale', self::DEFAULT_LOCALE)->count() . ' articles + list');
        } elseif ($module === self::RENDER_MODULE_CALCULATOR) {
            Log::info('seo:sab-render [en] calculator only: ' . self::PAGE_TRADING_CALCULATOR . '.html');
        } else {
            Log::info('seo:sab-render [en] products only: ' . $items->count() . ' items');
        }
    }

    public function renderCalculatorSite(?string $outputPathOverride = null, ?SabSiteContext $ctx = null): void
    {
        $ctx ??= new SabSiteContext(self::SITE_SLUG_SAB_CALCULATOR);
        $site = $ctx->resolve();
        $game = $ctx->dataGame($site);
        $outputPath = $this->filesystemOutputForRender($site, $outputPathOverride);
        $baseUrl = rtrim($site->base_url ?: 'https://sabcalculator.com', '/');
        $items = $this->loadItems($game);
        $i18n = $this->loadI18n($site);
        $locale = self::DEFAULT_LOCALE;
        $t = $this->mergeSabTranslations($locale, $i18n);

        $this->ensureDir($outputPath);

        // index.html — calculator (write immediately then free the rendered string)
        file_put_contents(
            "{$outputPath}/index.html",
            view('seo.sab.calculator', $this->calculatorViewPayload('', $locale, $baseUrl, $t, $items, $ctx->cssHrefForRender(), $site, $ctx))->render()
        );

        // brainrots.html — share pre-loaded $items to avoid a second DB round-trip
        file_put_contents(
            "{$outputPath}/" . self::PAGE_BRAINROTS_LIST . ".html",
            view('seo.sab.brainrots-list', $this->brainrotsListViewPayload('', $baseUrl, $items, $ctx->cssHrefForRender(), $site, $ctx))->render()
        );

        // Product detail pages for every brainrot that appears in the list (income > 0).
        $calcData      = $this->calculatorData($items, $site);
        $priceBySlug   = $this->brainrotPriceHistoryForItems($items);
        $itemsBySlug   = $items->keyBy('slug');
        $productSlugs  = [];
        $this->ensureDir("{$outputPath}/products");
        foreach ($calcData['brainrots'] as $brainrotData) {
            $slug = $brainrotData['slug'] ?? '';
            $item = $itemsBySlug->get($slug);
            if (! $item || ! self::shouldLinkProduct($item)) {
                continue;
            }
            $brainrotData['priceHistory'] = $priceBySlug[$slug] ?? [];
            $this->renderCalculatorItem($outputPath, $item, $brainrotData, $site, $ctx, $baseUrl);
            $productSlugs[] = self::productPublicSlug((string) $item->slug);
        }
        $productCount = count($productSlugs);

        $staticPages = $this->loadPublishedStaticPages($site);
        foreach ($staticPages as $article) {
            $this->renderCalculatorStaticPage($outputPath, $baseUrl, $article, $site, $ctx);
        }

        $this->renderRobotsTxt($outputPath, $baseUrl);
        $this->renderCalculatorAdsTxt($outputPath);
        $this->renderCalculatorSitemap($outputPath, $baseUrl, $staticPages, $productSlugs);
        $this->syncStaticCssToWebsite($outputPath, $ctx);
        $this->syncCalculatorBaseCss($outputPath);
        $this->syncCalculatorFavicon($outputPath);
        $copiedImages = $this->syncCalculatorImagesToOutput($outputPath);

        Log::info("seo:sab-render [{$ctx->siteSlug()}] calculator-only site → {$outputPath}/index.html + brainrots.html + {$productCount} products + {$staticPages->count()} legal pages (calculator images copied: {$copiedImages})");
    }

    private function renderCalculatorAdsTxt(string $outputPath): void
    {
        $templatePath = resource_path('seo/sab/sabcalculator-ads.txt');
        if (! is_readable($templatePath)) {
            throw new \RuntimeException("SAB calculator ads.txt template is missing: {$templatePath}");
        }

        $content = trim((string) file_get_contents($templatePath));
        if ($content === '') {
            throw new \RuntimeException("SAB calculator ads.txt template is empty: {$templatePath}");
        }

        file_put_contents("{$outputPath}/ads.txt", $content."\n");
    }

    private function syncCalculatorBaseCss(string $outputPath): void
    {
        $cssDir = "{$outputPath}/static/css";
        $this->ensureDir($cssDir);
        $dest = "{$cssDir}/sabexistcount.css";
        foreach ([public_path('static/css/sabexistcount.css'), resource_path('seo/sab/sabexistcount.css')] as $src) {
            if (is_readable($src) && @copy($src, $dest)) {
                return;
            }
        }
    }

    private function syncCalculatorFavicon(string $outputPath): void
    {
        $assets = [
            SabSiteContext::FAVICON_SAB_CALCULATOR,
            SabSiteContext::APPLE_TOUCH_SAB_CALCULATOR,
        ];

        foreach ($assets as $href) {
            $relative = ltrim($href, '/');
            $source = public_path($relative);
            if (! is_readable($source)) {
                continue;
            }

            $dest = "{$outputPath}/{$relative}";
            $this->ensureDir(dirname($dest));
            @copy($source, $dest);
        }

        $ico = public_path(ltrim(SabSiteContext::FAVICON_SAB_CALCULATOR, '/'));
        if (is_readable($ico)) {
            $this->ensureDir($outputPath);
            @copy($ico, "{$outputPath}/favicon.ico");
        }
    }

    private function syncSabExistCountFavicon(string $outputPath): void
    {
        $assets = [
            'uploads/images/sab/favicon.ico',
            'uploads/images/sab/favicon-512.png',
            'uploads/images/sab/apple-touch-icon.png',
        ];

        foreach ($assets as $relative) {
            $source = public_path($relative);
            if (! is_readable($source)) {
                continue;
            }

            $dest = "{$outputPath}/{$relative}";
            $this->ensureDir(dirname($dest));
            @copy($source, $dest);
        }

        $ico = public_path('uploads/images/sab/favicon.ico');
        if (is_readable($ico)) {
            $this->ensureDir($outputPath);
            @copy($ico, "{$outputPath}/favicon.ico");
        }
    }

    private function syncSabGag2CalculatorImages(string $outputPath): void
    {
        $relative = 'uploads/images/sab/gag2-calculator';
        $source = public_path($relative);
        if (! is_dir($source)) {
            return;
        }

        $destination = "{$outputPath}/{$relative}";
        $this->ensureDir(dirname($destination));
        File::copyDirectory($source, $destination);
    }

    private function syncSabCodesImages(string $outputPath): void
    {
        $relative = 'uploads/images/sab/codes';
        $source = public_path($relative);
        if (! is_dir($source)) {
            return;
        }

        $destination = "{$outputPath}/{$relative}";
        $this->ensureDir(dirname($destination));
        File::copyDirectory($source, $destination);
    }

    private function syncSabGameImages(string $outputPath): void
    {
        $relative = 'uploads/images/sab/games';
        $source = public_path($relative);
        if (! is_dir($source)) {
            return;
        }

        $destination = "{$outputPath}/{$relative}";
        $this->ensureDir(dirname($destination));
        File::copyDirectory($source, $destination);
    }

    /**
     * Copy calculator brainrot/mutation/trait images into the static site output tree.
     * Images are shared across sites (stored under sab-exist-count data); only the deploy path is per-site.
     */
    private function syncCalculatorImagesToOutput(string $outputPath): int
    {
        $sourceRoot = public_path(SabRotCalculatorSyncService::IMAGE_ROOT);
        if (! is_dir($sourceRoot)) {
            Log::warning('seo:sab-render calculator images missing; run: php artisan seo:sab-rot-calculator-images', [
                'path' => $sourceRoot,
            ]);

            return 0;
        }

        $destRoot = "{$outputPath}/" . SabRotCalculatorSyncService::IMAGE_ROOT;
        $copied = 0;

        foreach (File::allFiles($sourceRoot) as $file) {
            $relative = $file->getRelativePathname();
            $src = $file->getPathname();
            $dest = "{$destRoot}/{$relative}";

            if (is_file($dest) && filesize($dest) === $file->getSize() && filemtime($dest) >= filemtime($src)) {
                continue;
            }

            $this->ensureDir(dirname($dest));
            if (@copy($src, $dest)) {
                $copied++;
            }
        }

        return $copied;
    }

    /**
     * @param  Collection<int, SeoNewsArticle>|null  $staticPages
     * @param  list<string>  $productSlugs  Public product slugs already rendered to disk
     */
    private function renderCalculatorSitemap(
        string $outputPath,
        string $baseUrl,
        ?Collection $staticPages = null,
        array $productSlugs = [],
    ): void {
        $base = rtrim($baseUrl, '/');
        $urls = [
            ['loc' => $base.'/', 'priority' => '1.0'],
            ['loc' => $base.'/'.self::PAGE_BRAINROTS_LIST, 'priority' => '0.9'],
        ];
        foreach ($staticPages ?? [] as $article) {
            $slug = (string) $article->slug;
            if (! in_array($slug, self::STATIC_PAGE_SLUGS, true)) {
                continue;
            }
            $urls[] = [
                'loc' => $this->calculatorStaticPagePublicUrl($base, $slug),
                'priority' => '0.4',
            ];
        }
        foreach ($productSlugs as $slug) {
            $slug = trim((string) $slug);
            if ($slug === '') {
                continue;
            }
            $urls[] = [
                'loc' => $base.'/products/'.$slug,
                'priority' => '0.8',
            ];
        }
        $xml = $this->buildSitemapXml($urls);
        file_put_contents("{$outputPath}/sitemap.xml", $xml);
    }

    /**
     * @param  list<array{loc: string, priority?: string}>  $urls
     */
    private function buildSitemapXml(array $urls): string
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
        foreach ($urls as $row) {
            $loc = htmlspecialchars($row['loc'], ENT_XML1);
            $priority = $row['priority'] ?? '0.8';
            $lines[] = '  <url>';
            $lines[] = "    <loc>{$loc}</loc>";
            $lines[] = "    <priority>{$priority}</priority>";
            $lines[] = '  </url>';
        }
        $lines[] = '</urlset>';

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param  ?string  $outputPathOverride  Same as {@see render()}
     * @param  string  $module  Same as {@see render()}
     */
    public function renderLocales(array $locales, ?string $outputPathOverride = null, string $module = self::RENDER_MODULE_ALL): void
    {
        $module = self::normalizeRenderModuleOption($module);

        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $game = SeoGame::where('seo_site_id', $site->id)
            ->where('slug', self::GAME_SLUG)
            ->firstOrFail();

        $outputPath = $this->filesystemOutputForRender($site, $outputPathOverride);
        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');

        $items = $module === self::RENDER_MODULE_NEWS ? new Collection : $this->loadItems($game);
        $news = in_array($module, [self::RENDER_MODULE_PRODUCTS, self::RENDER_MODULE_CALCULATOR], true)
            ? new Collection
            : $this->loadPublishedNews($site);
        $i18n = $this->loadI18n($site);

        foreach ($locales as $locale) {
            $locale = self::normalizeLocale($locale);
            if ($locale === self::DEFAULT_LOCALE) {
                $this->renderLocale($locale, $outputPath, $baseUrl, $items, $news, $i18n, $module);
            } elseif ($module === self::RENDER_MODULE_ALL) {
                $this->renderLocaleMarketingPages($locale, $outputPath, $baseUrl, $items, $i18n);
            }
            Log::info("seo:sab-render [{$locale}] module={$module}");
        }

        if ($module === self::RENDER_MODULE_ALL) {
            $this->renderStaticPages($outputPath, $baseUrl);
            $this->renderSitemap($outputPath, $baseUrl, $items, $news, $this->loadPublishedStaticPages($site), array_values(array_unique([self::DEFAULT_LOCALE, ...$locales])));
        } else {
            Log::warning("seo:sab-render-locales: module={$module} — skipped sitemap.xml, legal *.html; run full render before deploy.");
        }

        $this->syncStaticCssToWebsite($outputPath);
        $this->syncSabExistCountFavicon($outputPath);
    }

    /** @param ?string $outputPathOverride Same as {@see render()} */
    public function renderHomeLocales(array $locales, ?string $outputPathOverride = null): void
    {
        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $game = SeoGame::where('seo_site_id', $site->id)
            ->where('slug', self::GAME_SLUG)
            ->firstOrFail();

        $outputPath = $this->filesystemOutputForRender($site, $outputPathOverride);
        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $items = $this->loadItems($game);
        $i18n = $this->loadI18n($site);

        $generatedLocales = $this->homeLocalesForRender($i18n, $locales);
        foreach ($generatedLocales as $locale) {
            $locale = self::normalizeLocale($locale);
            if (! in_array($locale, self::HOME_LOCALES, true)) {
                continue;
            }

            if ($locale === self::DEFAULT_LOCALE) {
                $this->ensureDir($outputPath);
                $this->renderHome(
                    $outputPath,
                    '',
                    $locale,
                    $this->mergeSabTranslations($locale, $i18n),
                    $items,
                    $baseUrl
                );
            } else {
                $this->renderLocaleMarketingPages($locale, $outputPath, $baseUrl, $items, $i18n);
            }
            Log::info("seo:sab-render-home [{$locale}] rendered");
        }

        Log::warning('seo:sab-render-home skipped sitemap.xml rewrite; run full seo:sab-render.');
        $this->syncStaticCssToWebsite($outputPath);
        $this->syncSabExistCountFavicon($outputPath);
    }

    /**
     * @return array<string, mixed>
     */
    public function homeTranslationSource(): array
    {
        $site = SeoSite::where('slug', self::SITE_SLUG)->first();
        $en = $this->applySiteCopyOverrides(self::I18N_EN, $site);

        return array_intersect_key($en, array_flip(self::HOME_I18N_KEYS));
    }

    /**
     * @param array<string, mixed> $i18n
     * @param list<string> $requestedLocales
     * @return list<string>
     */
    private function homeLocalesForRender(array $i18n, array $requestedLocales): array
    {
        $storedLocales = array_values(array_filter(
            array_keys($i18n),
            fn (string $locale) => $locale === self::DEFAULT_LOCALE
                || (in_array($locale, self::MULTILINGUAL_PAGE_LOCALES, true) && is_array($i18n[$locale] ?? null))
        ));

        return array_values(array_unique([self::DEFAULT_LOCALE, ...$storedLocales, ...$requestedLocales]));
    }

    /**
     * Stored `storage/app/seo/sab-i18n.json` messages merged onto English defaults. For locale `en`,
     * compact home-toggle strings always follow PHP so deploy-time copy updates are visible
     * even when the JSON bundle still carries older wording.
     *
     * @param array<string, array<string, mixed>> $i18n
     * @return array<string, mixed>
     */
    private function mergeSabTranslations(string $locale, array $i18n): array
    {
        $t = array_merge(
            self::I18N_EN,
            $i18n[$locale] ?? $i18n[self::DEFAULT_LOCALE] ?? []
        );

        if ($locale === self::DEFAULT_LOCALE) {
            $t['home_show_all'] = self::I18N_EN['home_show_all'];
            $t['home_show_top_10'] = self::I18N_EN['home_show_top_10'];
            $t['nav_value_list'] = self::I18N_EN['nav_value_list'];
            $t['nav_exist_counts_list'] = self::I18N_EN['nav_exist_counts_list'];
            foreach (self::VALUE_LIST_EN_OVERRIDE_KEYS as $key) {
                if (array_key_exists($key, self::I18N_EN)) {
                    $t[$key] = self::I18N_EN[$key];
                }
            }
        }

        return $t;
    }

    public function homeViewContext(string $locale = self::DEFAULT_LOCALE): array
    {
        $locale = self::normalizeLocale($locale);

        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $game = SeoGame::where('seo_site_id', $site->id)
            ->where('slug', self::GAME_SLUG)
            ->firstOrFail();

        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $items = $this->loadItems($game);
        $i18n = $this->loadI18n($site);
        $t = $this->mergeSabTranslations($locale, $i18n);
        // Preview runs on the local dev server; use preview-rooted prefix so
        // product links resolve to /seo/sab/preview/products/xxx.html
        $urlPrefix = self::localizedPreviewPath($locale);

        return $this->homeViewPayload($urlPrefix, $locale, $baseUrl, $t, $items, self::CSS_HREF_LARAVEL);
    }

    public function existCountsListViewContext(string $locale = self::DEFAULT_LOCALE): array
    {
        $locale = self::normalizeLocale($locale);

        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $game = SeoGame::where('seo_site_id', $site->id)
            ->where('slug', self::GAME_SLUG)
            ->firstOrFail();

        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $items = $this->loadItems($game);
        $i18n = $this->loadI18n($site);
        $t = $this->mergeSabTranslations($locale, $i18n);
        $urlPrefix = self::localizedPreviewPath($locale);

        return $this->existCountsListViewPayload($urlPrefix, $locale, $baseUrl, $t, $items, self::CSS_HREF_LARAVEL);
    }

    public function valueListViewContext(string $locale = self::DEFAULT_LOCALE): array
    {
        $locale = self::normalizeLocale($locale);

        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $game = SeoGame::where('seo_site_id', $site->id)
            ->where('slug', self::GAME_SLUG)
            ->firstOrFail();

        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $items = $this->loadItems($game);
        $i18n = $this->loadI18n($site);
        $t = $this->mergeSabTranslations($locale, $i18n);
        $urlPrefix = self::localizedPreviewPath($locale);

        return $this->valueListViewPayload($urlPrefix, $locale, $baseUrl, $t, $items, self::CSS_HREF_LARAVEL);
    }

    /**
     * @return array<string, mixed>
     */
    public function valueChangesViewContext(
        int $days = 7,
        ?string $direction = null,
        string $sort = 'recent',
        string $locale = self::DEFAULT_LOCALE,
    ): array {
        $locale = self::DEFAULT_LOCALE;

        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $i18n = $this->loadI18n($site);
        $t = $this->mergeSabTranslations($locale, $i18n);
        $urlPrefix = self::publicUrlPrefix($locale);

        return $this->valueChangesViewPayload($urlPrefix, $locale, $baseUrl, $t, $days, $direction, $sort, self::CSS_HREF_LARAVEL);
    }

    public function wikiViewContext(): array
    {
        return $this->wikiPreviewPayload('wiki');
    }

    public function wikiCatalogViewContext(string $pageSlug): array
    {
        $pageSlug = preg_replace('/\.html$/', '', $pageSlug) ?: '';
        abort_unless(in_array($pageSlug, SabWikiPageDefinitions::catalogPageSlugs(), true), 404);

        return $this->wikiPreviewPayload($pageSlug);
    }

    public function wikiTopicViewContext(string $pageSlug): array
    {
        $pageSlug = preg_replace('/\.html$/', '', $pageSlug) ?: '';
        abort_unless(in_array($pageSlug, SabWikiPageDefinitions::topicPageSlugs(), true), 404);

        return $this->wikiPreviewPayload($pageSlug);
    }

    /**
     * @return array<string, mixed>
     */
    private function wikiPreviewPayload(string $pageSlug): array
    {
        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $game = SeoGame::where('seo_site_id', $site->id)
            ->where('slug', self::GAME_SLUG)
            ->firstOrFail();

        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $i18n = $this->loadI18n($site);

        return $this->wikiPagePayload(
            $pageSlug,
            self::publicUrlPrefix(),
            $baseUrl,
            $this->mergeSabTranslations(self::DEFAULT_LOCALE, $i18n),
            $this->loadItems($game),
            self::CSS_HREF_LARAVEL,
            $this->loadPublishedNews($site),
            $game,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function wikiPagePayload(
        string $pageSlug,
        string $urlPrefix,
        string $baseUrl,
        array $t,
        Collection $items,
        string $cssHref,
        ?Collection $news = null,
        ?SeoGame $game = null,
    ): array {
        $catalog = $this->buildWikiCatalog($urlPrefix, $items, $news);
        $pageSlug = $pageSlug === '' ? self::PAGE_WIKI : $pageSlug;
        $rarityKey = SabWikiPageDefinitions::rarityKeyForPage($pageSlug);
        $topicKey = SabWikiPageDefinitions::topicKeyForPage($pageSlug);
        $isHub = $pageSlug === self::PAGE_WIKI;
        $isCatalog = in_array($pageSlug, SabWikiPageDefinitions::catalogPageSlugs(), true);
        $isAdminAbuse = $pageSlug === SabWikiPageDefinitions::PAGE_WIKI_ADMIN_ABUSE;
        $pageRows = collect($catalog['rows']);
        if ($rarityKey !== null) {
            $pageRows = $pageRows->where('rarityKey', $rarityKey)->values();
        } elseif ($topicKey !== null) {
            $pageRows = $pageRows
                ->filter(fn (array $row): bool => in_array($topicKey, $row['topicKeys'] ?? [], true))
                ->values();
        }

        $pageGroups = $this->wikiGroupsForRows($pageRows, $rarityKey);
        $copyCount = $isHub ? $catalog['total'] : $pageRows->count();
        $copy = SabWikiPageDefinitions::copy(
            $pageSlug,
            $copyCount,
            $catalog['knownRarest']['name'] ?? null,
            $catalog['knownRarest']['existCountLabel'] ?? null,
        );
        $isRebirthGuide = $pageSlug === SabWikiPageDefinitions::PAGE_WIKI_REBIRTHS;
        $adminAbuse = $isAdminAbuse && $game
            ? app(SabWikiAdminAbuseScheduleService::class)->pageData($game)
            : null;
        if ($adminAbuse !== null) {
            $adminAbuse['related_items_count'] = $pageRows->count();
        }
        $rebirths = $isRebirthGuide ? $this->rebirthsData() : null;
        $rebirthsVerifiedAt = $isRebirthGuide
            ? Carbon::createFromFormat('!Y-m-d', (string) $rebirths['verified_at'])->startOfDay()
            : null;
        if ($isRebirthGuide && $rebirthsVerifiedAt) {
            $monthLabel = $rebirthsVerifiedAt->locale('en')->isoFormat('MMM YYYY');
            $copy['title'] = str_replace('{month}', $monthLabel, $copy['title']);
            $copy['description'] = str_replace('{month}', $monthLabel, $copy['description']);
        }
        $t['meta_keywords'] = '';
        $wikiHref = $this->wikiPageHref($urlPrefix, self::PAGE_WIKI);
        $allBrainrotsHref = $this->wikiPageHref($urlPrefix, SabWikiPageDefinitions::PAGE_ALL_BRAINROTS);
        $rebirthsHref = $this->wikiPageHref($urlPrefix, SabWikiPageDefinitions::PAGE_WIKI_REBIRTHS);
        $existCountHref = rtrim($urlPrefix, '/').'/'.self::PAGE_EXIST_COUNTS_LIST;
        $valueListHref = rtrim($this->productUrlPrefix($urlPrefix), '/').'/'.self::PAGE_VALUE_LIST;
        $calculatorHref = rtrim($urlPrefix, '/').'/'.self::PAGE_TRADING_CALCULATOR;
        $faqItems = $isAdminAbuse
            ? $this->adminAbuseFaqItems($adminAbuse ?? [], $valueListHref, $calculatorHref, $existCountHref)
            : ($isHub
                ? $this->wikiFaqItemsWithInternalLinks($copy['faqs'], $valueListHref, $calculatorHref, $existCountHref)
                : $copy['faqs']);
        $canonical = rtrim($baseUrl, '/').'/'.$pageSlug;
        $isPreview = str_starts_with($urlPrefix, '/seo/sab/preview');
        $crumbs = $this->wikiCrumbs($urlPrefix, $pageSlug, $copy['h1']);
        $jsonRows = $isHub
            ? array_map(fn (array $link): array => [
                'name' => $link['name'],
                'slug' => $link['slug'],
            ], $this->wikiHubListItems($urlPrefix, $catalog))
            : ($isRebirthGuide
                ? array_map(fn (array $level): array => [
                    'name' => 'Rebirth '.$level['level'],
                    'slug' => 'rebirth-'.$level['level'],
                    'url' => $canonical.'#rebirth-'.$level['level'],
                ], $rebirths['levels'] ?? [])
                : $pageRows->all());
        $adminCheckedAt = $adminAbuse && ! empty($adminAbuse['source_checked_at'])
            ? Carbon::parse((string) $adminAbuse['source_checked_at'])
            : null;
        $wikiUpdatedAt = $isRebirthGuide
            ? $rebirthsVerifiedAt
            : ($isAdminAbuse ? ($adminCheckedAt ?: $catalog['updatedAt']) : $catalog['updatedAt']);

        $topics = array_map(function (array $topic) use ($urlPrefix): array {
            $topic['href'] = $this->wikiPageHref($urlPrefix, $topic['page']);

            return $topic;
        }, $catalog['topics']);
        $topicLinks = $isHub
            ? array_values(array_map(
                fn (array $topic): array => [
                    'href' => $this->wikiPageHref($urlPrefix, $topic['page']),
                    'label' => $topic['label'],
                ],
                array_filter($topics, static fn (array $topic): bool => $topic['key'] === 'admin-abuse'),
            ))
            : [];

        return [
            'locale' => self::DEFAULT_LOCALE,
            'urlPrefix' => $urlPrefix,
            'productUrlPrefix' => $this->productUrlPrefix($urlPrefix),
            'baseUrl' => $baseUrl,
            't' => $t,
            'seoTitle' => $copy['title'],
            'seoDescription' => $copy['description'],
            'canonical' => $canonical,
            'robots' => $isPreview ? 'noindex,follow' : 'index,follow,max-image-preview:large',
            'ogImageAlt' => $copy['og_alt'],
            'hreflangLinks' => [],
            'cssHref' => $cssHref,
            'wikiPageSlug' => $pageSlug,
            'wikiH1' => $copy['h1'],
            'wikiLead' => $copy['lead'],
            'wikiCatalogTitle' => $isRebirthGuide
                ? 'Confirmed rebirth Brainrots'
                : ($isAdminAbuse
                    ? 'Related Brainrots and Lucky Blocks'
                : ($isCatalog
                    ? ($rarityKey ? $copy['h1'].' by stored data' : 'All Brainrots by rarity')
                    : $copy['h1'])),
            'wikiCatalogDesc' => $isRebirthGuide
                ? 'Items whose stored obtain method mentions rebirth. This is a catalog of confirmed obtain rows, not the 19-level requirement table above.'
                : ($isAdminAbuse
                    ? 'Only items whose stored obtain method mentions Admin Abuse, an Admin Event, or Taco Tuesday. Taco Merchant rows stay on their news and product pages unless that obtain field matches.'
                : ($isCatalog
                    ? 'Search every server-rendered row. Sorting keeps Unknown values after confirmed values.'
                    : 'Only items with a stored match for this topic. Missing obtain methods stay off this list.')),
            'wikiRows' => $catalog['rows'],
            'wikiGroups' => $pageGroups,
            'wikiNewest' => $catalog['newest'],
            'wikiTopics' => $topics,
            'wikiNews' => $catalog['news'],
            'wikiTotal' => $catalog['total'],
            'wikiPageTotal' => $pageRows->count(),
            'wikiUpdatedAt' => $wikiUpdatedAt?->toIso8601String(),
            'wikiUpdatedLabel' => $isRebirthGuide
                ? $wikiUpdatedAt?->locale('en')->isoFormat('MMM D, YYYY')
                : $wikiUpdatedAt?->copy()->setTimezone('America/Los_Angeles')->locale('en')->isoFormat('MMM D, YYYY h:mm A z'),
            'wikiUpdatedDate' => $isRebirthGuide
                ? $wikiUpdatedAt?->locale('en')->isoFormat('MMM D, YYYY')
                : $wikiUpdatedAt?->copy()->setTimezone('America/Los_Angeles')->locale('en')->isoFormat('MMM D, YYYY'),
            'wikiMeta' => $isRebirthGuide
                ? number_format((int) ($rebirths['max_level'] ?? 0)).' Rebirth levels · Updated '.($wikiUpdatedAt?->locale('en')->isoFormat('MMM D, YYYY') ?: 'Unknown')
                : ($isAdminAbuse
                    ? (($adminAbuse['status_label'] ?? 'Not Confirmed').' · Source checked '.($adminAbuse['source_checked_label'] ?? 'Unknown'))
                    : number_format($pageRows->count()).' confirmed items · Updated '.($wikiUpdatedAt?->copy()->setTimezone('America/Los_Angeles')->locale('en')->isoFormat('MMM D, YYYY') ?: 'Unknown')),
            'rebirthsGuide' => $isRebirthGuide,
            'rebirths' => $rebirths,
            'rebirth19NewsHref' => $isRebirthGuide
                ? rtrim($urlPrefix, '/').'/news/'.($rebirths['rebirth_19_news_slug'] ?? '')
                : null,
            'newsIndexHref' => rtrim($urlPrefix, '/').'/news',
            'tacoTuesdayNewsHref' => $isAdminAbuse
                ? rtrim($urlPrefix, '/').'/news/steal-a-brainrot-august-18-2026-taco-tuesday-taco-merchant-sammyni-truckini'
                : null,
            'saturdayUpdateNews' => $isAdminAbuse
                ? $this->adminAbuseSaturdayUpdateNews($news, $urlPrefix)
                : [],
            'tacoMerchantItems' => $isAdminAbuse
                ? $this->adminAbuseTacoMerchantItems($urlPrefix)
                : [],
            'wikiFaqItems' => $faqItems,
            'adminAbuse' => $adminAbuse,
            'wikiHref' => $wikiHref,
            'allBrainrotsHref' => $allBrainrotsHref,
            'rebirthsHref' => $rebirthsHref,
            'wikiCrumbs' => $crumbs,
            'wikiRarityLinks' => $this->wikiRarityLinks($urlPrefix, $catalog['groups']),
            'wikiTopicLinks' => $topicLinks,
            'existCountHref' => $existCountHref,
            'valueListHref' => $valueListHref,
            'calculatorHref' => $calculatorHref,
            'websiteJsonLd' => $this->websiteJsonLd($baseUrl, $copy['description']),
            'jsonLd' => $this->wikiPageJsonLd(
                $copy['title'],
                $copy['description'],
                $canonical,
                $jsonRows,
                $faqItems,
                $wikiUpdatedAt,
                $crumbs,
                $isHub ? 'SAB Wiki pages' : $copy['h1'],
                $isHub,
                $isRebirthGuide ? [
                    'headline' => $copy['h1'],
                    'description' => $copy['description'],
                    'dateModified' => $wikiUpdatedAt?->toDateString(),
                    'keywords' => 'steal a brainrot rebirth list, steal a brainrot rebirth requirements, steal a brainrot rebirth rewards, steal a brainrot max rebirth, rebirth 19',
                ] : null,
                $isAdminAbuse ? $this->adminAbuseEventSchemas($adminAbuse ?? [], $canonical) : [],
                $isAdminAbuse ? 'WebPage' : null,
            ),
        ];
    }

    /**
     * Admin Abuse FAQ uses the same visible answer and JSON-LD answer. Links
     * are added only to the rendered answer_html copy.
     *
     * @param  array<string, mixed>  $schedule
     * @return list<array{question: string, answer: string, answer_html: string}>
     */
    private function adminAbuseFaqItems(array $schedule, string $valueListHref, string $calculatorHref, string $existCountHref): array
    {
        $admin = is_array($schedule['admin_abuse'] ?? null) ? $schedule['admin_abuse'] : [];
        $taco = is_array($schedule['taco_tuesday'] ?? null) ? $schedule['taco_tuesday'] : [];
        $adminTime = (string) ($admin['eastern_time'] ?? 'Not confirmed');
        $tacoTime = (string) ($taco['eastern_time'] ?? 'Not confirmed');
        $adminDuration = (string) ($admin['duration_label'] ?? 'Not confirmed');
        $mechanics = $schedule['mechanics'] ?? [];
        $mechanicsText = $mechanics === [] ? 'No mechanics are confirmed in the stored source.' : implode(', ', $mechanics).'.';
        $related = (int) ($schedule['related_items_count'] ?? 0);
        $adminStatus = (string) ($admin['status'] ?? '');
        $todayAnswer = match ($adminStatus) {
            'live' => 'Admin Abuse is Live Now on the status card. The stored start is '.$adminTime.'.',
            'today' => 'Yes. The stored Admin Abuse window is today, '.$adminTime.'.',
            default => $adminTime === 'Not confirmed'
                ? 'Not confirmed. The page does not guess a date when the stored schedule is missing.'
                : 'Not today. The next stored Admin Abuse is '.$adminTime.'. Taco Tuesday is listed separately.',
        };
        $faqs = [
            ['question' => 'What time is Admin Abuse in Steal a Brainrot today?', 'answer' => 'The next stored Admin Abuse time is '.$adminTime.'. Check the SAB Exist Count status card for Today, Live Now, Upcoming, or Not Confirmed.'],
            ['question' => 'Is there Admin Abuse in Steal a Brainrot today?', 'answer' => $todayAnswer],
            ['question' => 'When is the next Steal a Brainrot Admin Abuse?', 'answer' => 'The next stored Admin Abuse occurrence is '.$adminTime.'. Past dates are rejected; the schedule is recalculated from its recurring weekday when possible. Use the SAB Calculator when you prepare a trade around a limited event.'],
            ['question' => 'What time is Taco Tuesday in Steal a Brainrot?', 'answer' => 'The next stored Taco Tuesday time is '.$tacoTime.'. Eastern Time is shown first and the page adds your local browser time.'],
            ['question' => 'Is Taco Tuesday an Admin Abuse event?', 'answer' => 'Taco Tuesday is a separate recurring event. It is listed beside Admin Abuse so both search intents resolve without treating them as one event.'],
            ['question' => 'How do I convert Admin Abuse time to my timezone?', 'answer' => 'Eastern Time is the source display. The timezone table converts the next stored occurrence into Hawaii, Pacific, Mountain, Central, UTC, London, Paris, Dubai, India, Singapore, Japan, and Sydney. The browser also adds your local time on the status card. Daylight saving time is applied automatically.'],
            ['question' => 'How long does Admin Abuse last?', 'answer' => 'The latest stored Admin Abuse duration is '.$adminDuration.'. Missing duration data stays Not confirmed.'],
            ['question' => 'What happens during Admin Abuse?', 'answer' => $mechanicsText.' Unconfirmed community claims are omitted.'],
            ['question' => 'Can you get banned for joining Admin Abuse?', 'answer' => 'No. Admin Abuse is a developer-hosted window, not a glitch or exploit. Joining the event is not a ban reason. This site is still an independent reference and not an official Roblox page.'],
            ['question' => 'What if I join Admin Abuse late?', 'answer' => 'You can still join after the stored start time. The window is only '.$adminDuration.', so a late join leaves less time to contest spawns. The page does not invent extra minutes.'],
            ['question' => 'Does Admin Abuse run on a private server?', 'answer' => 'The stored window applies to servers that are already open, including a private server. Some players use a private server to reduce steal contests. It is a community preference, not a requirement.'],
            ['question' => 'Which Brainrots and Lucky Blocks can appear?', 'answer' => $related > 0 ? $related.' related '.($related === 1 ? 'Brainrot is' : 'Brainrots are').' linked below from confirmed obtain methods. Open a product, then compare its Exist Count and SAB Values before trading.' : 'No related Brainrots or Lucky Blocks have a confirmed Admin Abuse obtain method in the current database.'],
            ['question' => 'What should I check after Admin Abuse ends?', 'answer' => 'Open the product page for any new copy, then compare SAB Exist Count and SAB Values before you trade. Use the SAB Calculator if you are offering a limited drop. Supply and trade value can move after a busy window.'],
            ['question' => 'Can the Admin Abuse or Taco Tuesday schedule change?', 'answer' => 'Yes. Events can be delayed, canceled, or moved. The page shows the source checked time and keeps the last successful schedule when a refresh fails.'],
        ];
        $links = [
            'SAB Values' => $valueListHref,
            'SAB Calculator' => $calculatorHref,
            'SAB Exist Count' => $existCountHref,
        ];

        return array_map(function (array $faq) use ($links): array {
            $html = e($faq['answer']);
            foreach ($links as $label => $href) {
                $html = str_replace(e($label), '<a href="'.e($href).'">'.e($label).'</a>', $html);
            }
            $faq['answer_html'] = $html;

            return $faq;
        }, $faqs);
    }

    /**
     * Saturday weekly update notes already published on this site. Guides,
     * comparisons, and Tuesday logs stay out of this list.
     *
     * @param  Collection<int, SeoNewsArticle>|null  $news
     * @return list<array{title: string, href: string, dateLabel: ?string}>
     */
    private function adminAbuseSaturdayUpdateNews(?Collection $news, string $urlPrefix): array
    {
        return collect($news ?? [])
            ->filter(fn ($article): bool => $article instanceof SeoNewsArticle
                && $article->locale === self::DEFAULT_LOCALE
                && $article->status === 'published'
                && (int) $article->type === SeoNewsArticle::TYPE_NEWS
                && $this->isSaturdayWeeklyUpdateNote($article))
            ->sortByDesc(fn (SeoNewsArticle $article): int => optional($article->published_at)->getTimestamp() ?? 0)
            ->take(5)
            ->map(fn (SeoNewsArticle $article): array => [
                'title' => (string) $article->title,
                'href' => rtrim($urlPrefix, '/').'/news/'.$article->slug,
                'dateLabel' => $article->published_at
                    ? $article->published_at->copy()->setTimezone('America/Los_Angeles')->locale('en')->isoFormat('MMM D, YYYY')
                    : null,
            ])
            ->values()
            ->all();
    }

    private function isSaturdayWeeklyUpdateNote(SeoNewsArticle $article): bool
    {
        if ($article->published_at === null) {
            return false;
        }
        if ($article->published_at->copy()->setTimezone(SabWikiAdminAbuseScheduleService::TIMEZONE)->format('l') !== 'Saturday') {
            return false;
        }
        $haystack = mb_strtolower(trim($article->slug.' '.$article->title));

        return preg_match('/calculator|comparison|how-to|trade-watch|brand-guide|\bvs\b|vote/', $haystack) !== 1;
    }

    /**
     * Dated Taco Merchant examples from the August 18 Taco Tuesday note.
     * These are news/product links, not a weekly drop table.
     *
     * @return list<array{name: string, href: string}>
     */
    private function adminAbuseTacoMerchantItems(string $urlPrefix): array
    {
        $prefix = rtrim($this->productUrlPrefix($urlPrefix), '/');

        return [
            ['name' => 'Sammyni Truckini', 'href' => $prefix.'/products/sammyni-truckini'],
            ['name' => 'Nachorilla', 'href' => $prefix.'/products/nachorilla'],
            ['name' => 'Tacoturbo Tacorito', 'href' => $prefix.'/products/tacoturbo-tacorito'],
            ['name' => 'Burrito Bat', 'href' => $prefix.'/products/burrito-bat'],
        ];
    }

    /**
     * Event schema is deliberately omitted when the collector has no
     * confirmed future occurrence. The page and FAQ schema remain present.
     *
     * @return list<array<string, mixed>>
     */
    private function adminAbuseEventSchemas(array $schedule, string $canonical): array
    {
        if (($schedule['source_status'] ?? 'unconfirmed') !== 'confirmed') {
            return [];
        }
        $events = [];
        foreach ((array) ($schedule['events'] ?? []) as $event) {
            if (! is_array($event) || empty($event['next_event_at'])) {
                continue;
            }
            try {
                $start = Carbon::parse((string) $event['next_event_at']);
            } catch (\Throwable) {
                continue;
            }
            if ($start->isPast() && ($event['status'] ?? '') !== 'live') {
                continue;
            }
            $end = null;
            if (! empty($event['duration_max_minutes'])) {
                $end = $start->copy()->addMinutes((int) $event['duration_max_minutes'])->toIso8601String();
            }
            $node = [
                '@type' => 'Event',
                '@id' => $canonical.'#'.strtolower(str_replace(' ', '-', (string) $event['label'])),
                'name' => 'Steal a Brainrot '.$event['label'],
                'description' => 'Community-confirmed '.$event['label'].' schedule for Steal a Brainrot.',
                'startDate' => $start->toIso8601String(),
                'eventStatus' => ($event['event_status'] ?? 'confirmed') === 'postponed'
                    ? 'https://schema.org/EventPostponed'
                    : 'https://schema.org/EventScheduled',
                'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
                'location' => ['@type' => 'VirtualLocation', 'url' => $canonical],
                'url' => $canonical,
            ];
            if ($end !== null) {
                $node['endDate'] = $end;
            }
            $events[] = $node;
        }

        return $events;
    }

    /**
     * 可见 FAQ 与 JSON-LD 共用同一份文本，仅在页面答案中加入站内链接。
     *
     * @param  list<array{question: string, answer: string}>  $faqItems
     * @return list<array{question: string, answer: string, answer_html: string}>
     */
    private function wikiFaqItemsWithInternalLinks(
        array $faqItems,
        string $valueListHref,
        string $calculatorHref,
        string $existCountHref,
    ): array {
        $links = [
            'SAB Exist Count List' => $existCountHref,
            'SAB Values list' => $valueListHref,
            'SAB Calculator' => $calculatorHref,
            'SAB Exist Count' => $existCountHref,
            'SAB Values' => $valueListHref,
        ];

        return array_map(function (array $faq) use ($links): array {
            $html = e($faq['answer']);
            $replacements = [];
            foreach ($links as $label => $href) {
                $placeholder = '%%SAB_WIKI_LINK_'.count($replacements).'%%';
                $html = str_replace(e($label), $placeholder, $html);
                $replacements[$placeholder] = '<a href="'.e($href).'">'.e($label).'</a>';
            }

            $faq['answer_html'] = strtr($html, $replacements);

            return $faq;
        }, $faqItems);
    }

    /**
     * @return array{rows: list<array<string, mixed>>, groups: list<array<string, mixed>>, topics: list<array<string, mixed>>, newest: list<array<string, mixed>>, news: list<array<string, mixed>>, total: int, updatedAt: ?Carbon, knownRarest: ?array<string, mixed>}
     */
    private function buildWikiCatalog(string $urlPrefix, Collection $items, ?Collection $news = null): array
    {
        $rarityOrder = SabWikiPageDefinitions::RARITY_ORDER;
        $changesBySlug = collect(app(SabValueChangesService::class)->changes(7, null, 'recent', 1000))
            ->keyBy(fn (array $change): string => (string) ($change['itemSlug'] ?? ''));
        $rows = $items
            ->filter(fn (SeoItem $item): bool => self::shouldRenderProductHtml($item)
                && self::shouldIndexProductSlug((string) $item->slug))
            ->map(function (SeoItem $item) use ($urlPrefix, $rarityOrder, $changesBySlug): array {
                $rarityKey = self::canonicalRarityKey($item->rarity ?? null);
                if ($rarityKey === '' || ! in_array($rarityKey, $rarityOrder, true)) {
                    $rarityKey = 'other';
                }
                $rarityLabel = $rarityKey === 'og'
                    ? 'OG'
                    : ($rarityKey === 'other' ? 'Other' : self::canonicalRarityLabel($rarityKey));
                $rot = data_get($item->attributes_json, 'rot_rocks', []);
                $cost = is_numeric(data_get($rot, 'base_cost')) && (float) data_get($rot, 'base_cost') > 0
                    ? (float) data_get($rot, 'base_cost')
                    : null;
                $income = is_numeric(data_get($rot, 'base_income')) && (float) data_get($rot, 'base_income') > 0
                    ? (float) data_get($rot, 'base_income')
                    : null;
                $tradeValue = self::valueListValueForItem($item);
                if ($tradeValue !== null && $tradeValue <= 0) {
                    $tradeValue = null;
                }
                $baseVariant = $item->variants->firstWhere('variant_key', 'base')
                    ?? $item->variants->firstWhere('variant_type', 'base');
                $baseValue = $baseVariant
                    ? $baseVariant->currentValues->first(fn ($cv) => ($cv->source?->slug) === SabRotCalculatorSyncService::SOURCE_SLUG)
                    : null;
                $demand = trim((string) (data_get($rot, 'demand') ?: $baseValue?->demand ?: ''));
                $demandLabel = $demand !== '' ? Str::title($demand) : null;
                $trend = trim((string) data_get($rot, 'trend'));
                $trendLabel = $trend !== '' ? Str::title(strtolower(str_replace('_', ' ', $trend))) : null;
                $change = $changesBySlug->get((string) $item->slug);
                $previousValue = is_numeric($change['beforeValue'] ?? null) ? (float) $change['beforeValue'] : null;
                $delta = ($previousValue !== null && $tradeValue !== null)
                    ? round($tradeValue - $previousValue, 4)
                    : null;
                $deltaPct = ($previousValue !== null && $previousValue != 0.0 && $delta !== null)
                    ? round(($delta / $previousValue) * 100, 1)
                    : null;
                $changeDirection = 'stable';
                if ($delta !== null && $delta > 0) {
                    $changeDirection = 'up';
                } elseif ($delta !== null && $delta < 0) {
                    $changeDirection = 'down';
                }
                $deltaPctLabel = $deltaPct === null ? null : (($deltaPct > 0 ? '+' : '').$deltaPct.'%');
                $existDisplay = self::resolveExistCountDisplay($item);
                $updatedAt = $this->wikiItemUpdatedAt($item);
                $obtainMethod = self::wikiConfirmedObtainMethod($item);
                $addedAt = self::homeNewReferenceDate($item);
                $topicKeys = self::wikiTopicKeys((string) $item->slug, $obtainMethod);

                return [
                    'name' => (string) $item->name,
                    'slug' => self::productPublicSlug((string) $item->slug),
                    'productUrl' => rtrim($this->productUrlPrefix($urlPrefix), '/').'/products/'.self::productPublicSlug((string) $item->slug),
                    'imageSrc' => $this->wikiLocalImageSrc($item),
                    'rarityKey' => $rarityKey,
                    'rarityLabel' => $rarityLabel,
                    'cost' => $cost,
                    'costLabel' => self::formatWikiGameMoney($cost),
                    'income' => $income,
                    'incomeLabel' => self::formatWikiGameMoney($income, true),
                    'existCount' => $existDisplay['sort_value'],
                    'existCountLabel' => match ($existDisplay['kind'] ?? 'none') {
                        'none' => '-',
                        'estimated' => $item->exist_estimate_low !== null
                            ? number_format((int) $item->exist_estimate_low)
                            : '-',
                        default => $existDisplay['primary'],
                    },
                    'existCountKind' => (string) ($existDisplay['kind'] ?? 'none'),
                    'existCountBadge' => ($existDisplay['kind'] ?? 'none') === 'estimated' ? 'Estimate' : null,
                    'tradeValue' => $tradeValue,
                    'tradeValueLabel' => $tradeValue === null ? '-' : self::formatWikiCompactRobux($tradeValue),
                    'demandLabel' => $demandLabel,
                    'trendLabel' => $trendLabel,
                    'changeDirection' => $deltaPctLabel === null ? null : $changeDirection,
                    'deltaPctLabel' => $deltaPctLabel,
                    'obtainMethod' => $obtainMethod,
                    'isNew' => self::isNewHomeItem($item),
                    'addedAt' => $addedAt,
                    'topicKeys' => $topicKeys,
                    'updatedAt' => $updatedAt,
                    'search' => mb_strtolower(implode(' ', array_filter([
                        $item->name,
                        $item->rarity,
                        $rarityKey,
                        $cost,
                        $income,
                        $existDisplay['primary'] ?? null,
                        $tradeValue,
                        $demandLabel,
                        $trendLabel,
                        $deltaPctLabel,
                        $obtainMethod,
                    ], fn ($value): bool => trim((string) $value) !== ''))),
                ];
            })
            ->sortBy(fn (array $row): string => strtolower($row['name']))
            ->values();

        $groups = $this->wikiGroupsForRows($rows, null);
        $latest = $rows->pluck('updatedAt')->filter()->sortDesc()->first();
        $knownRarest = $rows
            ->filter(fn (array $row): bool => ($row['existCountKind'] ?? '') === 'known' && $row['existCount'] !== null)
            ->sortBy('existCount')
            ->first();
        $updatedAt = $latest instanceof Carbon ? $latest : null;
        $newest = $rows
            ->filter(fn (array $row): bool => (bool) ($row['isNew'] ?? false))
            ->sortByDesc(fn (array $row): int => $row['addedAt'] instanceof Carbon ? $row['addedAt']->timestamp : 0)
            ->take(12)
            ->values()
            ->all();
        $topics = collect(self::wikiTopicDefinitions())
            ->map(function (array $topic) use ($rows): array {
                $topicRows = $rows
                    ->filter(fn (array $row): bool => in_array($topic['key'], $row['topicKeys'] ?? [], true))
                    ->values()
                    ->all();

                return [
                    ...$topic,
                    'rows' => $topicRows,
                    'count' => count($topicRows),
                ];
            })
            ->all();
        $newsItems = collect($news ?? [])
            ->filter(fn ($article): bool => $article instanceof SeoNewsArticle
                && $article->locale === self::DEFAULT_LOCALE
                && $article->status === 'published')
            ->take(6)
            ->map(fn (SeoNewsArticle $article): array => [
                'title' => (string) $article->title,
                'href' => rtrim($urlPrefix, '/').'/news/'.$article->slug,
                'dateLabel' => $article->published_at
                    ? $article->published_at->copy()->setTimezone('America/Los_Angeles')->locale('en')->isoFormat('MMM D, YYYY')
                    : null,
            ])
            ->values()
            ->all();

        return [
            'rows' => $rows->all(),
            'groups' => $groups,
            'topics' => $topics,
            'newest' => $newest,
            'news' => $newsItems,
            'total' => $rows->count(),
            'updatedAt' => $updatedAt,
            'knownRarest' => $knownRarest,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function wikiGroupsForRows(Collection $rows, ?string $onlyRarity): array
    {
        $order = $onlyRarity !== null
            ? [$onlyRarity]
            : [...SabWikiPageDefinitions::RARITY_ORDER, 'other'];

        return collect($order)
            ->map(function (string $key) use ($rows): array {
                $groupRows = $rows
                    ->where('rarityKey', $key)
                    ->sort(function (array $a, array $b): int {
                        $av = $a['tradeValue'] ?? null;
                        $bv = $b['tradeValue'] ?? null;
                        $aHas = $av !== null;
                        $bHas = $bv !== null;
                        if ($aHas !== $bHas) {
                            return $aHas ? -1 : 1;
                        }
                        if ($aHas && $bHas) {
                            $cmp = ((float) $bv) <=> ((float) $av);
                            if ($cmp !== 0) {
                                return $cmp;
                            }
                        }

                        return strcasecmp((string) $a['name'], (string) $b['name']);
                    })
                    ->values()
                    ->all();

                return [
                    'key' => $key,
                    'slug' => str_replace(' ', '-', $key),
                    'label' => $key === 'og' ? 'OG' : ($key === 'other' ? 'Other' : self::canonicalRarityLabel($key)),
                    'description' => SabWikiPageDefinitions::SECTION_COPY[$key] ?? '',
                    'rows' => $groupRows,
                    'count' => count($groupRows),
                ];
            })
            ->filter(fn (array $group): bool => $group['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @return list<array{href: string, label: string, key: string, count: int}>
     */
    private function wikiRarityLinks(string $urlPrefix, array $groups): array
    {
        $links = [];
        foreach (SabWikiPageDefinitions::RARITY_PAGE_SLUGS as $key => $slug) {
            $group = collect($groups)->firstWhere('key', $key);
            $count = (int) ($group['count'] ?? 0);
            $links[] = [
                'href' => $this->wikiPageHref($urlPrefix, $slug),
                'label' => ($key === 'og' ? 'OG' : self::canonicalRarityLabel($key)).' ('.number_format($count).')',
                'key' => $key,
                'count' => $count,
            ];
        }

        return $links;
    }

    /**
     * Published wiki catalog counts by the eight standard rarities. Does not
     * build catalog rows. Home filter counts are a different universe.
     *
     * @param  Collection<int, SeoItem>|null  $items
     * @return array<string, int>
     */
    public function wikiPublishedRarityCounts(?Collection $items = null): array
    {
        $counts = array_fill_keys(array_keys(SabWikiPageDefinitions::RARITY_PAGE_SLUGS), 0);
        $rarityOrder = SabWikiPageDefinitions::RARITY_ORDER;

        if ($items === null) {
            $site = SeoSite::query()->where('slug', self::SITE_SLUG)->first();
            $game = $site
                ? SeoGame::query()
                    ->where('seo_site_id', $site->id)
                    ->where('slug', self::GAME_SLUG)
                    ->first()
                : null;
            $items = $game
                ? SeoItem::query()
                    ->where('seo_game_id', $game->id)
                    ->get(['id', 'slug', 'rarity', 'is_publish_html'])
                : collect();
        }

        foreach ($items as $item) {
            if (! $item instanceof SeoItem
                || ! self::shouldRenderProductHtml($item)
                || ! self::shouldIndexProductSlug((string) $item->slug)) {
                continue;
            }

            $key = self::canonicalRarityKey($item->rarity ?? null);
            if ($key === '' || ! in_array($key, $rarityOrder, true) || ! array_key_exists($key, $counts)) {
                continue;
            }

            $counts[$key]++;
        }

        return $counts;
    }

    /**
     * @param  array{groups: list<array<string, mixed>>, topics: list<array<string, mixed>>}  $catalog
     * @return list<array{name: string, slug: string}>
     */
    private function wikiHubListItems(string $urlPrefix, array $catalog): array
    {
        $items = [[
            'name' => 'All Brainrots',
            'slug' => SabWikiPageDefinitions::PAGE_ALL_BRAINROTS,
        ]];
        foreach (SabWikiPageDefinitions::RARITY_PAGE_SLUGS as $key => $slug) {
            $items[] = [
                'name' => $key === 'og' ? 'OG' : self::canonicalRarityLabel($key),
                'slug' => $slug,
            ];
        }

        return $items;
    }

    /**
     * @return list<array{name: string, href: string, current?: bool}>
     */
    private function wikiCrumbs(string $urlPrefix, string $pageSlug, string $currentName): array
    {
        $homeHref = $urlPrefix === '' ? '/' : $urlPrefix;
        $crumbs = [
            ['name' => 'Home', 'href' => $homeHref],
            ['name' => 'Wiki', 'href' => $this->wikiPageHref($urlPrefix, self::PAGE_WIKI)],
        ];
        if ($pageSlug !== self::PAGE_WIKI) {
            $crumbName = $pageSlug === SabWikiPageDefinitions::PAGE_ALL_BRAINROTS ? 'All Brainrots' : $currentName;
            $crumbs[] = ['name' => $crumbName, 'href' => $this->wikiPageHref($urlPrefix, $pageSlug), 'current' => true];
        } else {
            $crumbs[1]['current'] = true;
        }

        return $crumbs;
    }

    private function wikiPageHref(string $urlPrefix, string $pageSlug): string
    {
        return rtrim($this->productUrlPrefix($urlPrefix), '/').'/'.$pageSlug;
    }

    private function wikiLocalImageSrc(SeoItem $item): ?string
    {
        $thumbPath = 'uploads/images/sab/thumbs/'.$item->slug.'-64.webp';
        if (file_exists(public_path($thumbPath))) {
            return '/'.$thumbPath;
        }

        $localPath = ltrim((string) $item->local_image_url, '/');
        if ($localPath !== '' && file_exists(public_path($localPath))) {
            return '/'.$localPath;
        }

        return null;
    }

    private function wikiItemUpdatedAt(SeoItem $item): ?Carbon
    {
        $timestamps = collect([$item->updated_at])
            ->merge($item->variants->flatMap(fn (SeoItemVariant $variant) => $variant->currentValues)
                ->flatMap(fn (SeoItemCurrentValue $value) => [$value->changed_at, $value->collected_at]))
            ->filter()
            ->map(fn ($value): Carbon => $value instanceof Carbon ? $value->copy() : Carbon::parse($value));

        return $timestamps->sortDesc()->first();
    }

    private static function formatWikiCompactRobux(float $value): string
    {
        $abs = abs($value);
        if ($abs >= 1_000_000) {
            return rtrim(rtrim(number_format($value / 1_000_000, 1, '.', ''), '0'), '.').'M';
        }
        if ($abs >= 1_000) {
            return rtrim(rtrim(number_format($value / 1_000, 1, '.', ''), '0'), '.').'k';
        }

        return number_format($value);
    }

    private static function formatWikiGameMoney(?float $value, bool $perSecond = false): string
    {
        if ($value === null || $value <= 0) {
            return '-';
        }

        $suffix = '';
        $scaled = $value;
        foreach ([1_000_000_000_000 => 'T', 1_000_000_000 => 'B', 1_000_000 => 'M', 1_000 => 'K'] as $threshold => $label) {
            if ($value >= $threshold) {
                $scaled = $value / $threshold;
                $suffix = $label;
                break;
            }
        }

        $decimals = $suffix === '' || $scaled >= 100 ? 0 : ($scaled >= 10 ? 1 : 2);
        $formatted = number_format($scaled, $decimals, '.', '');
        if ($decimals > 0) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return '$'.$formatted.$suffix.($perSecond ? '/s' : '');
    }

    private static function wikiConfirmedObtainMethod(SeoItem $item): ?string
    {
        $value = trim((string) data_get($item->attributes_json, 'manual_update.obtain_method', ''));
        if ($value === '' || preg_match('/^(n\/a|na|none|null|unknown|—|-)$/iu', $value)) {
            return null;
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private static function wikiTopicKeys(string $slug, ?string $obtainMethod): array
    {
        $haystack = mb_strtolower(trim($slug.' '.($obtainMethod ?? '')));
        $keys = [];

        if (str_contains($haystack, 'lucky-block') || str_contains($haystack, 'lucky block')) {
            $keys[] = 'lucky-blocks';
        }
        if (preg_match('/\bfusions?\b|\bfuse\b|\bcraft(ing)?\b/', $haystack)) {
            $keys[] = 'fusions';
        }
        if (str_contains($haystack, 'rebirth')) {
            $keys[] = 'rebirths';
        }
        if (str_contains($haystack, 'ritual')) {
            $keys[] = 'rituals';
        }
        // Admin Abuse is intentionally narrow. Merchant, Red Carpet, DLC,
        // shop, and generic RNG rows are not event evidence by themselves.
        if (preg_match('/admin\s*[- ]?(?:abuse|event|lucky|block)|taco\s+tuesday|limited\s+admin\s+event/', $haystack)) {
            $keys[] = 'admin-abuse';
        }

        return $keys;
    }

    /**
     * @return list<array{key: string, id: string, label: string, description: string}>
     */
    private static function wikiTopicDefinitions(): array
    {
        return [
            [
                'key' => 'lucky-blocks',
                'id' => 'lucky-blocks',
                'page' => SabWikiPageDefinitions::TOPIC_PAGE_SLUGS['lucky-blocks'],
                'label' => 'Lucky Blocks',
                'description' => 'Brainrots stored with a Lucky Block obtain path, plus Lucky Block items themselves.',
            ],
            [
                'key' => 'fusions',
                'id' => 'fusions',
                'page' => SabWikiPageDefinitions::TOPIC_PAGE_SLUGS['fusions'],
                'label' => 'Fusions',
                'description' => 'Items only appear here when a confirmed fusion, fuse, or crafting obtain method is stored.',
            ],
            [
                'key' => 'rebirths',
                'id' => 'rebirths',
                'page' => SabWikiPageDefinitions::TOPIC_PAGE_SLUGS['rebirths'],
                'label' => 'Rebirths',
                'description' => 'Rebirth is a progression reset. The Rebirth list covers all 19 cash and Brainrot requirements.',
            ],
            [
                'key' => 'rituals',
                'id' => 'rituals',
                'page' => SabWikiPageDefinitions::TOPIC_PAGE_SLUGS['rituals'],
                'label' => 'Rituals',
                'description' => 'Ritual routes are shown only when an item has a confirmed ritual obtain method.',
            ],
            [
                'key' => 'admin-abuse',
                'id' => 'admin-abuse',
                'page' => SabWikiPageDefinitions::TOPIC_PAGE_SLUGS['admin-abuse'],
                'label' => 'Admin Abuse',
                'description' => 'Admin Abuse and other limited obtain routes when those methods are stored.',
            ],
        ];
    }

    public function rebirthsData(): array
    {
        $path = resource_path('seo/sab/rebirths.json');
        if (! File::isFile($path)) {
            throw new \RuntimeException("SAB rebirths file not found: {$path}");
        }

        $data = json_decode(File::get($path), true);
        if (! is_array($data)) {
            throw new \RuntimeException('SAB rebirths file is not valid JSON.');
        }

        $verifiedAt = trim((string) ($data['verified_at'] ?? ''));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $verifiedAt)) {
            throw new \RuntimeException('SAB rebirths verified_at must use YYYY-MM-DD.');
        }
        try {
            $verifiedDate = Carbon::createFromFormat('!Y-m-d', $verifiedAt);
        } catch (\Throwable) {
            $verifiedDate = null;
        }
        if (! $verifiedDate || $verifiedDate->format('Y-m-d') !== $verifiedAt) {
            throw new \RuntimeException('SAB rebirths verified_at is not a valid date.');
        }

        $maxLevel = (int) ($data['max_level'] ?? 0);
        $levels = $data['levels'] ?? null;
        $groups = $data['groups'] ?? null;
        if ($maxLevel < 1 || ! is_array($levels) || count($levels) !== $maxLevel) {
            throw new \RuntimeException('SAB rebirths levels must match max_level.');
        }
        if (! is_array($groups) || $groups === []) {
            throw new \RuntimeException('SAB rebirths groups are required.');
        }

        foreach ($levels as $index => $level) {
            if (! is_array($level)) {
                throw new \RuntimeException('SAB rebirths levels must be objects.');
            }
            $number = (int) ($level['level'] ?? 0);
            if ($number !== $index + 1) {
                throw new \RuntimeException('SAB rebirths levels must be numbered 1 through max_level.');
            }
            foreach (['cash', 'brainrots', 'rarity', 'multiplier', 'cash_reward', 'special'] as $field) {
                if (trim((string) ($level[$field] ?? '')) === '') {
                    throw new \RuntimeException("SAB rebirths level {$number} is missing {$field}.");
                }
            }
        }

        foreach ($groups as $group) {
            if (! is_array($group) || trim((string) ($group['image'] ?? '')) === '' || trim((string) ($group['title'] ?? '')) === '') {
                throw new \RuntimeException('SAB rebirths groups require title and image.');
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function codesViewContext(string $locale = self::DEFAULT_LOCALE): array
    {
        $locale = self::normalizeLocale($locale);
        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $i18n = $this->loadI18n($site);
        $t = $this->mergeSabTranslations($locale, $i18n);

        return $this->codesViewPayload(
            self::localizedPreviewPath($locale),
            $locale,
            $baseUrl,
            $t,
            $this->codesData(),
            self::CSS_HREF_LARAVEL,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function gameViewContext(string $slug = self::PAGE_GAME, string $locale = self::DEFAULT_LOCALE): array
    {
        $slug = preg_replace('/\.html$/', '', $slug) ?: self::PAGE_GAME;
        abort_unless(isset(self::gamesCatalog()[$slug]), 404);
        $locale = self::normalizeLocale($locale);

        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $i18n = $this->loadI18n($site);
        $t = $this->mergeSabTranslations($locale, $i18n);

        return $this->gameViewPayload(
            self::localizedPreviewPath($locale),
            $locale,
            $baseUrl,
            $t,
            self::CSS_HREF_LARAVEL,
            $slug,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function gamesHubViewContext(string $locale = self::DEFAULT_LOCALE): array
    {
        $locale = self::normalizeLocale($locale);
        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $game = SeoGame::where('seo_site_id', $site->id)
            ->where('slug', self::GAME_SLUG)
            ->firstOrFail();
        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $i18n = $this->loadI18n($site);
        $t = $this->mergeSabTranslations($locale, $i18n);

        return $this->gamesHubViewPayload(
            self::localizedPreviewPath($locale),
            $locale,
            $baseUrl,
            $t,
            $this->loadItems($game),
            self::CSS_HREF_LARAVEL,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function codesData(): array
    {
        $path = resource_path('seo/sab/codes.json');
        if (! File::isFile($path)) {
            throw new \RuntimeException("SAB codes file not found: {$path}");
        }

        $data = json_decode(File::get($path), true);
        if (! is_array($data)) {
            throw new \RuntimeException('SAB codes file is not valid JSON.');
        }

        $verifiedAt = trim((string) ($data['verified_at'] ?? ''));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $verifiedAt)) {
            throw new \RuntimeException('SAB codes verified_at must use YYYY-MM-DD.');
        }
        try {
            $verifiedDate = Carbon::createFromFormat('!Y-m-d', $verifiedAt);
        } catch (\Throwable) {
            $verifiedDate = null;
        }
        if (! $verifiedDate || $verifiedDate->format('Y-m-d') !== $verifiedAt) {
            throw new \RuntimeException('SAB codes verified_at is not a valid date.');
        }

        foreach (['wiki_url', 'wiki_label'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new \RuntimeException("SAB codes {$field} is required.");
            }
        }

        $seenCodes = [];
        foreach (['active', 'expired'] as $group) {
            if (! is_array($data[$group] ?? null)) {
                throw new \RuntimeException("SAB codes {$group} must be an array.");
            }
            foreach ($data[$group] as $entry) {
                $code = trim((string) ($entry['code'] ?? ''));
                $reward = trim((string) ($entry['reward'] ?? ''));
                if ($code === '' || $reward === '') {
                    throw new \RuntimeException("SAB codes {$group} entries require code and reward.");
                }
                $key = strtolower($code);
                if (isset($seenCodes[$key])) {
                    throw new \RuntimeException("Duplicate SAB code: {$code}");
                }
                $seenCodes[$key] = true;
            }
        }

        $randomDlc = $data['random_dlc'] ?? null;
        if (! is_array($randomDlc)
            || trim((string) ($randomDlc['description'] ?? '')) === ''
            || ! is_array($randomDlc['possible_rewards'] ?? null)
            || $randomDlc['possible_rewards'] === []
        ) {
            throw new \RuntimeException('SAB random DLC code data is incomplete.');
        }
        foreach ($randomDlc['possible_rewards'] as $reward) {
            if (! is_array($reward) || trim((string) ($reward['name'] ?? '')) === '') {
                throw new \RuntimeException('SAB random DLC rewards require a name.');
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function codesCopy(string $locale): array
    {
        $translations = [];
        foreach (['codes-i18n.json', 'codes-i18n-west.json', 'codes-i18n-east.json'] as $fileName) {
            $path = resource_path("seo/sab/{$fileName}");
            if (! File::isFile($path)) {
                throw new \RuntimeException("SAB codes translations file not found: {$path}");
            }
            $bundle = json_decode(File::get($path), true);
            if (! is_array($bundle)) {
                throw new \RuntimeException("SAB codes translations file is not valid JSON: {$path}");
            }
            $translations = array_merge($translations, $bundle);
        }

        if (! is_array($translations) || ! is_array($translations[self::DEFAULT_LOCALE] ?? null)) {
            throw new \RuntimeException('SAB codes translations must include an English object.');
        }

        foreach (self::HOME_LOCALES as $supportedLocale) {
            if (! is_array($translations[$supportedLocale] ?? null)) {
                throw new \RuntimeException("SAB codes translations missing locale: {$supportedLocale}");
            }
        }

        $locale = self::normalizeLocale($locale);
        $copy = array_replace_recursive(
            $translations[self::DEFAULT_LOCALE],
            $translations[$locale] ?? []
        );
        foreach ([
            'meta_title',
            'meta_description',
            'h1',
            'hero_intro',
            'active_title',
            'dlc_title',
            'expired_title',
            'redeem_title',
            'not_working_title',
            'more_codes_title',
            'faq_title',
        ] as $key) {
            if (trim((string) ($copy[$key] ?? '')) === '') {
                throw new \RuntimeException("SAB codes translation {$locale}.{$key} is required.");
            }
        }
        if (count($copy['faq_items'] ?? []) !== 5 || count($copy['not_working_items'] ?? []) !== 5) {
            throw new \RuntimeException("SAB codes translation {$locale} requires five FAQ and five troubleshooting entries.");
        }

        return $copy;
    }

    /**
     * @return array<string, mixed>
     */
    private function calculatorCopy(string $locale): array
    {
        $translations = [];
        foreach (['calculator-i18n.json', 'calculator-i18n-west.json', 'calculator-i18n-east.json'] as $fileName) {
            $path = resource_path("seo/sab/{$fileName}");
            if (! File::isFile($path)) {
                throw new \RuntimeException("SAB calculator translations file not found: {$path}");
            }
            $bundle = json_decode(File::get($path), true);
            if (! is_array($bundle)) {
                throw new \RuntimeException("SAB calculator translations file is not valid JSON: {$path}");
            }
            $translations = array_merge($translations, $bundle);
        }

        $english = $translations[self::DEFAULT_LOCALE] ?? null;
        if (! is_array($english)) {
            throw new \RuntimeException('SAB calculator translations must include an English object.');
        }

        foreach (self::HOME_LOCALES as $supportedLocale) {
            $localized = $translations[$supportedLocale] ?? null;
            if (! is_array($localized)) {
                throw new \RuntimeException("SAB calculator translations missing locale: {$supportedLocale}");
            }
            $missing = array_keys(array_diff_key($english, $localized));
            if ($missing !== []) {
                throw new \RuntimeException(
                    "SAB calculator translation {$supportedLocale} missing keys: " . implode(', ', $missing)
                );
            }
            if (count($localized['calculator_seo_steps'] ?? []) !== 5
                || count($localized['calculator_tips'] ?? []) !== 6
                || count($localized['calculator_faq_items'] ?? []) !== 6
            ) {
                throw new \RuntimeException(
                    "SAB calculator translation {$supportedLocale} requires five steps, six tips, and six FAQ entries."
                );
            }
        }

        $locale = self::normalizeLocale($locale);

        return array_replace_recursive($english, $translations[$locale] ?? []);
    }

    private function interpolateCodesCopy(mixed $value, array $replacements): mixed
    {
        if (is_string($value)) {
            return strtr($value, $replacements);
        }
        if (! is_array($value)) {
            return $value;
        }

        return array_map(
            fn (mixed $entry): mixed => $this->interpolateCodesCopy($entry, $replacements),
            $value
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function codesViewPayload(string $urlPrefix, string $locale, string $baseUrl, array $t, array $data, string $cssHref): array
    {
        $verified = Carbon::createFromFormat('!Y-m-d', (string) $data['verified_at']);
        $monthLabel = $this->localizedMonthLabel($verified, $locale);
        $verifiedLabel = $verified->copy()->locale($this->carbonLocale($locale))->isoFormat('LL');
        $rawCopy = $this->codesCopy($locale);
        $updatedTemplate = (string) $rawCopy['updated_label'];
        $copy = $this->interpolateCodesCopy($rawCopy, [
            '{month}' => $monthLabel,
            '{verified_date}' => $verifiedLabel,
            '{active_count}' => (string) count($data['active']),
            '{expired_count}' => (string) count($data['expired']),
        ]);
        $title = (string) $copy['meta_title'];
        $description = (string) $copy['meta_description'];
        $canonical = $this->localePublicUrl($baseUrl, $locale, self::PAGE_CODES);
        $faqItems = array_map(static fn (array $faq): array => [
            'question' => (string) ($faq['question'] ?? ''),
            'answer' => (string) ($faq['answer'] ?? ''),
        ], $copy['faq_items']);
        $screenshotPath = '/uploads/images/sab/codes/steal-a-brainrot-redeem-codes.webp';
        $activeCodes = array_values($data['active']);
        foreach ($activeCodes as &$entry) {
            $entry['note'] = strtr((string) $copy['active_entry_note'], ['{code}' => (string) $entry['code']]);
        }
        unset($entry);
        $randomDlc = $data['random_dlc'];
        $randomDlc['description'] = (string) $copy['dlc_description'];

        return [
            'locale' => $locale,
            'urlPrefix' => $urlPrefix,
            'productUrlPrefix' => $this->productUrlPrefix($urlPrefix),
            'englishUrlPrefix' => $this->productUrlPrefix($urlPrefix),
            'baseUrl' => $baseUrl,
            't' => $t,
            'codesCopy' => $copy,
            'seoTitle' => $title,
            'seoDescription' => $description,
            'canonical' => $canonical,
            'hreflangLinks' => $this->hreflangLinks(self::PAGE_CODES, $baseUrl, self::MULTILINGUAL_PAGE_LOCALES),
            'languageLinks' => $this->languageLinks($urlPrefix, $locale, self::PAGE_CODES),
            'cssHref' => $cssHref,
            'websiteJsonLd' => $this->websiteJsonLd($baseUrl, $description),
            'jsonLd' => $this->codesPageJsonLd($title, $description, $canonical, $verified->format('Y-m-d'), $faqItems, $baseUrl, $locale, $copy),
            'ogImage' => $this->assetPublicUrl($baseUrl, $screenshotPath),
            'codesMonthLabel' => $monthLabel,
            'verifiedLabel' => $verifiedLabel,
            'verifiedAt' => $verified->format('Y-m-d'),
            'updatedLabelBeforeDate' => Str::before($updatedTemplate, '{verified_date}'),
            'updatedLabelAfterDate' => Str::after($updatedTemplate, '{verified_date}'),
            'activeCodes' => $activeCodes,
            'expiredCodes' => array_values($data['expired']),
            'randomDlc' => $randomDlc,
            'wikiUrl' => $data['wiki_url'],
            'wikiLabel' => $data['wiki_label'],
            'redeemScreenshot' => $screenshotPath,
            'faqItems' => $faqItems,
        ];
    }

    /**
     * @param  array<string, mixed>  $t
     * @return array<string, mixed>
     */
    private function gameViewPayload(
        string $urlPrefix,
        string $locale,
        string $baseUrl,
        array $t,
        string $cssHref,
        string $slug = self::PAGE_GAME,
    ): array {
        $catalog = self::gamesCatalog();
        $game = $catalog[$slug] ?? null;
        abort_unless(is_array($game), 404);

        $copy = $this->gamePageCopy($slug, $urlPrefix);
        $canonical = $this->localePublicUrl($baseUrl, $locale, self::gamePublicPath($slug));
        $gamesIndexUrl = $this->localePublicUrl($baseUrl, $locale, self::gamesIndexPublicPath());
        $coverSrc = (string) $game['coverSrc'];
        $featuredCharacters = $this->featuredBrainrotsForGamesHub($this->loadSabCatalogItems(), $urlPrefix);
        $recentValueChanges = $this->recentValueChangesForGames($urlPrefix);
        $codesHref = rtrim($this->productUrlPrefix($urlPrefix), '/') . '/' . self::PAGE_CODES;
        $valueChangesHref = rtrim($this->productUrlPrefix($urlPrefix), '/') . '/' . self::PAGE_VALUE_CHANGES;
        $pageSlug = self::gamePublicPath($slug);

        return [
            'locale' => $locale,
            'urlPrefix' => $urlPrefix,
            'productUrlPrefix' => $this->productUrlPrefix($urlPrefix),
            'englishUrlPrefix' => $this->productUrlPrefix($urlPrefix),
            'baseUrl' => $baseUrl,
            't' => $t,
            'hreflangLinks' => $this->hreflangLinks($pageSlug, $baseUrl, self::MULTILINGUAL_PAGE_LOCALES),
            'languageLinks' => $this->languageLinks($urlPrefix, $locale, $pageSlug),
            'canonical' => $canonical,
            'seoTitle' => $game['title'],
            'seoDescription' => $game['description'],
            'ogImage' => $this->assetPublicUrl($baseUrl, $coverSrc),
            'ogImageAlt' => (string) $game['name'] . ' cover',
            'cssHref' => $cssHref,
            'websiteJsonLd' => $this->websiteJsonLd($baseUrl, (string) $game['description']),
            'jsonLd' => $this->gamePageJsonLd(
                (string) $game['title'],
                (string) $game['description'],
                $canonical,
                $baseUrl,
                $copy['faqItems'],
                (string) $game['name'],
                $gamesIndexUrl,
                $slug,
                $coverSrc,
            ),
            'gameEmbedUrl' => $game['embedUrl'],
            'gameSlug' => $slug,
            'gameName' => $game['name'],
            'gameH1' => $game['h1'],
            'gameCoverSrc' => $coverSrc,
            'gameEyebrow' => $copy['eyebrow'],
            'gameHeroLead' => $copy['heroLead'],
            'gameCoverTitle' => $copy['coverTitle'],
            'gameCoverNote' => $copy['coverNote'],
            'gameFallback' => $copy['fallback'],
            'gameSections' => $copy['sections'],
            'relatedGames' => $copy['relatedGames'],
            'faqItems' => $copy['faqItems'],
            'featuredCharacters' => $featuredCharacters,
            'recentValueChanges' => $recentValueChanges,
            'codesHref' => $codesHref,
            'valueChangesHref' => $valueChangesHref,
        ];
    }

    /**
     * @param  array<string, mixed>  $t
     * @return array<string, mixed>
     */
    private function gamesHubViewPayload(
        string $urlPrefix,
        string $locale,
        string $baseUrl,
        array $t,
        Collection $items,
        string $cssHref,
    ): array {
        $title = 'Brainrot Games – Play Free Online | SABExistCount';
        $description = 'Play the best brainrot games online free: Steal a Brainrot and Rob Brainrot. Check exist counts, codes and rarity for every character. No download, instant play.';
        $canonical = $this->localePublicUrl($baseUrl, $locale, self::gamesIndexPublicPath());
        $featuredCharacters = $this->featuredBrainrotsForGamesHub($items, $urlPrefix);
        $faqItems = $this->gamesHubFaqItems($urlPrefix);
        $codesHref = rtrim($this->productUrlPrefix($urlPrefix), '/') . '/' . self::PAGE_CODES;
        $valueChangesHref = rtrim($this->productUrlPrefix($urlPrefix), '/') . '/' . self::PAGE_VALUE_CHANGES;
        $cards = [];
        foreach (self::gamesCatalog() as $game) {
            $cards[] = [
                'slug' => $game['slug'],
                'name' => $game['name'],
                'category' => $game['category'],
                'hot' => (bool) $game['hot'],
                'isNew' => (bool) $game['isNew'],
                'blurb' => $game['cardBlurb'],
                'coverSrc' => $game['coverSrc'],
                'href' => rtrim($urlPrefix, '/') . '/' . self::gamePublicPath((string) $game['slug']),
                'characters' => $featuredCharacters,
            ];
        }
        $roadmap = [];
        foreach (self::gamesRoadmap() as $entry) {
            $roadmap[] = $entry + [
                'href' => $entry['slug']
                    ? rtrim($urlPrefix, '/') . '/' . self::gamePublicPath((string) $entry['slug'])
                    : null,
            ];
        }

        return [
            'locale' => $locale,
            'urlPrefix' => $urlPrefix,
            'productUrlPrefix' => $this->productUrlPrefix($urlPrefix),
            'englishUrlPrefix' => $this->productUrlPrefix($urlPrefix),
            'baseUrl' => $baseUrl,
            't' => $t,
            'hreflangLinks' => $this->hreflangLinks(self::gamesIndexPublicPath(), $baseUrl, self::MULTILINGUAL_PAGE_LOCALES),
            'languageLinks' => $this->languageLinks($urlPrefix, $locale, self::gamesIndexPublicPath()),
            'canonical' => $canonical,
            'seoTitle' => $title,
            'seoDescription' => $description,
            'cssHref' => $cssHref,
            'websiteJsonLd' => $this->websiteJsonLd($baseUrl, $description),
            'jsonLd' => $this->gamesHubJsonLd($title, $description, $canonical, $baseUrl, $cards, $faqItems),
            'gameCards' => $cards,
            'hotGameCards' => array_values(array_filter($cards, static fn (array $card): bool => $card['hot'])),
            'newGameCards' => array_values(array_filter($cards, static fn (array $card): bool => $card['isNew'])),
            'gamesRoadmap' => $roadmap,
            'recentValueChanges' => $this->recentValueChangesForGames($urlPrefix),
            'codesHref' => $codesHref,
            'valueChangesHref' => $valueChangesHref,
            'faqItems' => $faqItems,
        ];
    }

    /**
     * @return array{
     *     eyebrow: string,
     *     heroLead: string,
     *     coverTitle: string,
     *     coverNote: string,
     *     fallback: string,
     *     sections: list<array{h2: string, paragraphs: list<string>, list?: list<string>, links?: list<array{href: string, label: string}>}>,
     *     relatedGames: list<array{href: string, label: string}>,
     *     faqItems: list<array{question: string, answer: string}>
     * }
     */
    private function gamePageCopy(string $slug, string $urlPrefix): array
    {
        return $slug === self::PAGE_GAME_ROB
            ? $this->robBrainrotCopy($urlPrefix)
            : $this->simulatorGameCopy($urlPrefix);
    }

    /**
     * @return array{
     *     eyebrow: string,
     *     heroLead: string,
     *     coverTitle: string,
     *     coverNote: string,
     *     fallback: string,
     *     sections: list<array{h2: string, paragraphs: list<string>, list?: list<string>, links?: list<array{href: string, label: string}>}>,
     *     relatedGames: list<array{href: string, label: string}>,
     *     faqItems: list<array{question: string, answer: string}>
     * }
     */
    private function simulatorGameCopy(string $urlPrefix): array
    {
        $pageHref = static fn (string $path): string => rtrim($urlPrefix, '/') . '/' . $path;

        return [
            'eyebrow' => 'Browser play',
            'heroLead' => 'Play Steal a Brainrot Simulator in your browser. Click Play to load the unofficial web version. This is not the Roblox client and is not affiliated with Roblox or the game developers.',
            'coverTitle' => 'Steal a Brainrot Simulator',
            'coverNote' => 'The first load downloads a large Unity WebGL package. Stay on this tab until the tutorial appears.',
            'fallback' => 'If the player stays blank, refresh once and keep this tab open until the tutorial appears. Desktop browsers usually load more reliably than mobile.',
            'sections' => [
                [
                    'h2' => 'How this browser version works',
                    'paragraphs' => [
                        'The Steal a Brainrot loop is buy, steal, and earn. After you click Play, the unofficial browser simulator loads in the frame on this page. Use it to learn the loop, then check exist counts and values on SAB Exist Count before you trade in Roblox.',
                    ],
                    'list' => [
                        'Move with WASD or arrow keys after the tutorial.',
                        'Buy brainrots from the conveyor, or steal from other bases when they are unlocked.',
                        'Use the site tools below to check supply and community values before you trade in Roblox.',
                    ],
                    'links' => [
                        ['href' => $pageHref(self::PAGE_EXIST_COUNTS_LIST), 'label' => 'Exist Count List'],
                        ['href' => $pageHref(self::PAGE_VALUE_LIST), 'label' => 'SAB Values'],
                        ['href' => $pageHref(self::PAGE_TRADING_CALCULATOR), 'label' => 'Trading Calculator'],
                        ['href' => $pageHref(self::PAGE_CODES), 'label' => 'Codes'],
                    ],
                ],
            ],
            'relatedGames' => [
                ['href' => $pageHref(self::gamesIndexPublicPath()), 'label' => 'All brainrot games'],
                ['href' => $pageHref(self::gamePublicPath(self::PAGE_GAME_ROB)), 'label' => 'Play Rob Brainrot 2'],
            ],
            'faqItems' => [
                [
                    'question' => 'What is Steal a Brainrot Simulator?',
                    'answer' => 'Steal a Brainrot Simulator is an unofficial browser version of the Roblox meme tycoon. You buy brainrots from the conveyor, steal from unlocked bases, and earn cash while you defend your own collection.',
                ],
                [
                    'question' => 'Is this the official Roblox Steal a Brainrot?',
                    'answer' => 'No. This page loads an unofficial web simulator. The original Steal a Brainrot is a Roblox game. SAB Exist Count is an independent community reference site and is not affiliated with Roblox or the game developers.',
                ],
                [
                    'question' => 'How do I play?',
                    'answer' => 'Start at your base, buy a cheap brainrot from the red conveyor, and let it walk home so it starts earning. Then sneak into unlocked bases, carry a brainrot back, and lock your base before someone steals yours.',
                ],
                [
                    'question' => 'What are the controls?',
                    'answer' => 'Use WASD or the arrow keys to move, E to buy, steal, or place a brainrot, left click to attack, Space to jump, and G to teleport home. On mobile, use the on-screen stick and tap controls.',
                ],
                [
                    'question' => 'How does the base lock work?',
                    'answer' => 'A locked base cannot be entered. The lock only lasts a short time, then other players can walk in and steal. Relock before you leave to shop or raid, and watch for bases that no longer have a shield.',
                ],
                [
                    'question' => 'What does Rebirth do?',
                    'answer' => 'Rebirth resets your cash and brainrot collection in exchange for permanent boosts such as income multipliers, a longer lock timer, and extra base slots. Requirements rise as you go. Browser-version details can differ from Roblox.',
                ],
                [
                    'question' => 'Why does the game take time to start?',
                    'answer' => 'The first load downloads a large browser game package. Keep this tab open until the tutorial appears. A desktop browser is usually faster than a phone.',
                ],
                [
                    'question' => 'Does this track exist counts?',
                    'answer' => 'No. The simulator is for play. Use the exist count list, value list, and trading calculator on this site for community supply and value references. Those are not official Roblox prices.',
                ],
                [
                    'question' => 'Does progress save?',
                    'answer' => 'Progress stays in this browser when the simulator stores it locally. Clearing cache, switching devices, or using another browser can reset it. It is not your Roblox account progress.',
                ],
                [
                    'question' => 'Can I play on mobile?',
                    'answer' => 'Yes, but keyboard and mouse are steadier for carrying brainrots and defending a raid. If the player stays blank on a phone, retry on desktop.',
                ],
                [
                    'question' => 'Where can I check exist counts and trade values?',
                    'answer' => 'Use the SAB exist count list, value list, and trading calculator on this site. Those tools are community references, not official Roblox prices.',
                ],
                [
                    'question' => 'What if the game does not load?',
                    'answer' => 'Refresh once, keep the tab visible, and try a desktop browser. Allow the page to finish loading before switching away.',
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     eyebrow: string,
     *     heroLead: string,
     *     coverTitle: string,
     *     coverNote: string,
     *     fallback: string,
     *     sections: list<array{h2: string, paragraphs: list<string>, list?: list<string>, links?: list<array{href: string, label: string}>}>,
     *     relatedGames: list<array{href: string, label: string}>,
     *     faqItems: list<array{question: string, answer: string}>
     * }
     */
    private function robBrainrotCopy(string $urlPrefix): array
    {
        $pageHref = static fn (string $path): string => rtrim($urlPrefix, '/') . '/' . $path;

        return [
            'eyebrow' => 'Browser play',
            'heroLead' => 'Play Rob Brainrot 2 online free. This page is the Rob Brainrot 2 web version. See below for how it differs from the first Rob Brainrot. Click Play to load the unofficial web version. This is not the Roblox client and is not affiliated with Roblox or the game developers.',
            'coverTitle' => 'Rob Brainrot 2',
            'coverNote' => 'The first load downloads a large browser game package. Stay on this tab until the game appears.',
            'fallback' => 'If the player stays blank, refresh once and keep this tab open until the game appears. Desktop browsers usually load more reliably than mobile.',
            'sections' => [
                [
                    'h2' => 'Rob Brainrot 2',
                    'paragraphs' => [
                        'This page plays Rob Brainrot 2 in the browser. The first Rob Brainrot is the short rob-and-earn loop. Rob Brainrot 2 keeps that loop and adds a shop, weapons, and a luck spin so a short run has more ways to spend cash.',
                        'Exact drop tables and cash amounts can change in the browser build. If you already know the first loop, treat Rob Brainrot 2 as a faster combat-and-shop variant, not a different game universe.',
                    ],
                    'list' => [
                        'Use the shop to spend a run instead of standing still.',
                        'Pick up weapons when a raid or a steal needs extra reach.',
                        'Spin luck when you want a faster roll than grinding the conveyor.',
                    ],
                ],
                [
                    'h2' => 'How to Play Rob Brainrot 2',
                    'paragraphs' => [
                        'Rob Brainrot 2 is a short rob-and-earn loop. You grab meme brainrots from the conveyor, carry them back to your base, and let them generate cash while you look for the next steal.',
                        'The risk is the same idea as Steal a Brainrot: unlocked bases can be robbed. Lock when you leave, and watch for bases that drop their shield.',
                    ],
                    'list' => [
                        'Start at your base and learn the conveyor timing.',
                        'Rob a brainrot, carry it home, and place it so it starts earning.',
                        'Spend cash on the shop, weapons, or a luck spin instead of standing still.',
                    ],
                ],
                [
                    'h2' => 'Rob Brainrot Codes',
                    'paragraphs' => [
                        'The browser version of Rob Brainrot 2 does not redeem Steal a Brainrot Roblox codes. Codes on this site are for the Roblox game, not for this web player.',
                        'If you want current working codes, use the SAB codes page, then redeem them in Roblox. Treat expired codes as closed.',
                    ],
                    'links' => [
                        ['href' => $pageHref(self::PAGE_CODES), 'label' => 'Steal a Brainrot Codes'],
                    ],
                ],
                [
                    'h2' => 'Rob Brainrot on Roblox',
                    'paragraphs' => [
                        'Rob Brainrot on Roblox is the account-based version: your inventory, rebirths, and friends stay on that account. This page is an unofficial Rob Brainrot 2 browser version for a quick session.',
                        'Progress here does not transfer to Roblox. Use this page to learn the rob loop, then check exist counts and values on SAB Exist Count before you trade in Roblox.',
                    ],
                ],
                [
                    'h2' => 'Rob Brainrot vs Steal a Brainrot',
                    'paragraphs' => [
                        'Steal a Brainrot Simulator is the longer tutorial-style loop: conveyor buys, base locks, and rebirth. Rob Brainrot 2 is the shorter rob-first version of the same meme economy, with a shop, weapons, and a luck spin.',
                        'Play Simulator if you want to learn the full Steal a Brainrot loop. Play Rob Brainrot 2 if you want a faster steal session, then come back to exist counts and values on this site.',
                    ],
                    'links' => [
                        ['href' => $pageHref(self::gamePublicPath(self::PAGE_GAME)), 'label' => 'Play Steal a Brainrot Simulator'],
                    ],
                ],
            ],
            'relatedGames' => [
                ['href' => $pageHref(self::gamesIndexPublicPath()), 'label' => 'All brainrot games'],
                ['href' => $pageHref(self::gamePublicPath(self::PAGE_GAME)), 'label' => 'Play Steal a Brainrot Simulator'],
            ],
            'faqItems' => [
                [
                    'question' => 'Is Rob Brainrot free?',
                    'answer' => 'Yes. This page loads a free unofficial browser version. You do not need to create an account on SAB Exist Count to click Play.',
                ],
                [
                    'question' => 'Do I need to download Rob Brainrot?',
                    'answer' => 'No. The first load pulls a browser game package in this tab. There is no separate installer.',
                ],
                [
                    'question' => 'Can I play Rob Brainrot on Roblox?',
                    'answer' => 'Yes. The original Rob Brainrot experience is on Roblox. This page is an unofficial web version and does not open the Roblox client.',
                ],
                [
                    'question' => 'What is Rob Brainrot 2?',
                    'answer' => 'Rob Brainrot 2 is the follow-up loop with a shop, weapons, and a luck spin. This page loads that unofficial web version. The browser build may not match every Roblox update.',
                ],
                [
                    'question' => 'Is this the official Rob Brainrot?',
                    'answer' => 'No. SAB Exist Count is an independent community reference site and is not affiliated with Roblox or the game developers.',
                ],
                [
                    'question' => 'Does this track exist counts?',
                    'answer' => 'No. Use the exist count list, value list, and trading calculator on this site for community supply and value references. Those are not official Roblox prices.',
                ],
                [
                    'question' => 'Can I play on mobile?',
                    'answer' => 'Yes, but a desktop browser is usually steadier for carrying brainrots and fighting a raid. If the player stays blank on a phone, retry on desktop.',
                ],
                [
                    'question' => 'Does progress save?',
                    'answer' => 'Progress stays in this browser when the game stores it locally. Clearing cache, switching devices, or using another browser can reset it. It is not your Roblox account progress.',
                ],
            ],
        ];
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function gamesHubFaqItems(string $urlPrefix): array
    {
        $existHref = e(rtrim($urlPrefix, '/') . '/' . self::PAGE_EXIST_COUNTS_LIST);
        $codesHref = e(rtrim($urlPrefix, '/') . '/' . self::PAGE_CODES);

        return [
            [
                'question' => 'Is Brainrot Games free to play?',
                'answer' => 'Yes. Every live game page on this list is free. You do not pay SAB Exist Count to click Play.',
            ],
            [
                'question' => 'Do I need to download anything?',
                'answer' => 'No. These are browser games. Click Play and keep the tab open while the package loads. There is no installer and no SAB account.',
            ],
            [
                'question' => 'Can I play without Roblox?',
                'answer' => 'Yes. The pages here run in your browser. They are unofficial web versions, not the Roblox client, and progress does not sync to a Roblox account.',
            ],
            [
                'question' => 'What is the rarest brainrot?',
                'answer' => 'Supply changes, so this page does not name a single rarest character. Sort the <a href="' . $existHref . '">exist count list</a> from lowest count and use the rarity filters. Treat those numbers as community references, not official Roblox data.',
            ],
            [
                'question' => 'How do I get brainrot codes?',
                'answer' => 'Browser versions do not redeem Roblox codes. Open the <a href="' . $codesHref . '">Steal a Brainrot codes</a> page on this site, then redeem working codes in Roblox.',
            ],
            [
                'question' => 'Are these games safe for kids?',
                'answer' => 'These pages are unofficial browser games for entertainment. They are not a babysitter, they do not create a SAB account, and SAB Exist Count is not affiliated with Roblox or the game developers. A parent should still decide what is appropriate.',
            ],
        ];
    }

    /**
     * @return list<array{slug: string, name: string, href: string, existCount: string, rarity: string}>
     */
    private function featuredBrainrotsForGamesHub(Collection $items, string $urlPrefix): array
    {
        $preferred = [
            'la-vacca-saturno-saturnita',
            'orcalero-orcala',
            'dragon-cannelloni',
            'tung-tung-tung-sahur',
            'ballerina-cappuccina',
        ];
        $bySlug = $items->keyBy(static fn (SeoItem $item): string => (string) $item->slug);
        $picked = [];
        $used = [];

        foreach ($preferred as $slug) {
            $item = $bySlug->get($slug);
            if (! $item instanceof SeoItem || ! self::shouldLinkProduct($item)) {
                continue;
            }
            $picked[] = $this->gamesHubCharacterLink($item, $urlPrefix);
            $used[$slug] = true;
            if (count($picked) >= 3) {
                return $picked;
            }
        }

        foreach (self::listedItems($items) as $item) {
            $slug = (string) $item->slug;
            if (isset($used[$slug]) || ! self::shouldLinkProduct($item)) {
                continue;
            }
            $picked[] = $this->gamesHubCharacterLink($item, $urlPrefix);
            $used[$slug] = true;
            if (count($picked) >= 3) {
                break;
            }
        }

        return $picked;
    }

    /**
     * @return array{slug: string, name: string, href: string, existCount: string, rarity: string}
     */
    private function gamesHubCharacterLink(SeoItem $item, string $urlPrefix): array
    {
        $display = self::resolveExistCountDisplay($item, $item->total_exists !== null ? (int) $item->total_exists : null);
        $rarityKey = self::canonicalRarityKey($item->rarity ?? null);
        $rarityLabel = $rarityKey === ''
            ? ''
            : ($rarityKey === 'og' ? 'OG' : self::canonicalRarityLabel($rarityKey));

        return [
            'slug' => (string) $item->slug,
            'name' => (string) $item->name,
            'href' => rtrim($this->productUrlPrefix($urlPrefix), '/') . '/products/' . self::productPublicSlug($item->slug),
            'existCount' => $display['primary'],
            'rarity' => $rarityLabel,
        ];
    }

    /**
     * @return Collection<int, SeoItem>
     */
    private function loadSabCatalogItems(): Collection
    {
        $site = SeoSite::where('slug', self::SITE_SLUG)->first();
        if (! $site) {
            return collect();
        }

        $game = SeoGame::where('seo_site_id', $site->id)
            ->where('slug', self::GAME_SLUG)
            ->first();
        if (! $game) {
            return collect();
        }

        return $this->loadItems($game);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentValueChangesForGames(string $urlPrefix, int $limit = 3): array
    {
        $valueChanges = app(SabValueChangesService::class);
        $gainers = $this->enrichValueChangeRows($valueChanges->topGainers(7, 2), $urlPrefix);
        $losers = $this->enrichValueChangeRows($valueChanges->topLosers(7, 1), $urlPrefix);
        $seen = [];
        $rows = [];
        foreach (array_merge($gainers, $losers) as $row) {
            $slug = (string) ($row['itemSlug'] ?? '');
            if ($slug === '' || isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;
            $rows[] = $row;
            if (count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    public function calculatorViewContext(
        string $locale = self::DEFAULT_LOCALE,
        string $siteSlug = self::SITE_SLUG,
        bool $includeTodaySummary = true,
    ): array
    {
        $locale = self::normalizeLocale($locale);
        $ctx = new SabSiteContext($siteSlug);
        $site = $ctx->resolve();
        $defaultBase = $ctx->isCalculatorOnly($site) ? 'https://sabcalculator.com' : 'https://sabexistcount.com';
        $baseUrl = rtrim($site->base_url ?: $defaultBase, '/');
        $i18n = $this->loadI18n($site);
        $t = $this->mergeSabTranslations($locale, $i18n);
        $urlPrefix = $ctx->isCalculatorOnly($site)
            ? $ctx->previewUrlPrefix()
            : self::localizedPreviewPath($locale);

        return $this->calculatorViewPayload(
            $urlPrefix,
            $locale,
            $baseUrl,
            $t,
            null,
            $ctx->cssHrefForRender(),
            $site,
            $ctx,
            $includeTodaySummary,
        );
    }

    public function tradeBuilderViewContext(string $locale = self::DEFAULT_LOCALE): array
    {
        return $this->calculatorViewContext($locale, self::SITE_SLUG, false);
    }

    /**
     * @return array<string, mixed>
     */
    public function gag2CalculatorViewContext(): array
    {
        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');

        return $this->gag2CalculatorViewPayload($baseUrl, self::CSS_HREF_LARAVEL, '/seo/sab/preview');
    }

    /**
     * @return array<string, mixed>
     */
    private function gag2CalculatorViewPayload(string $baseUrl, string $cssHref, string $urlPrefix): array
    {
        $data = app(SabGag2CalculatorService::class)->load();
        $title = 'Grow a Garden 2 Calculator - Values, Best Crops & Money Methods';
        $description = 'Free Grow a Garden 2 calculator for crop sell values, weight and size-luck odds, mutations, plant growth, best crops, pets, and gear.';
        $canonical = $this->localePublicUrl($baseUrl, self::DEFAULT_LOCALE, self::PAGE_GAG2_CALCULATOR . '.html');
        $faqItems = [
            [
                'question' => 'What does the Grow a Garden 2 Calculator do?',
                'answer' => 'It estimates crop sell value from crop, weight, amount, friend boost, and mutation choices, then shows plant growth, pets, gear, and best-crop references.',
            ],
            [
                'question' => 'Do mutations change crop value?',
                'answer' => 'Yes. Mutations apply a multiplier to the crop value estimate, so rare mutations can change the final value by a large amount.',
            ],
            [
                'question' => 'Are Grow a Garden 2 values guaranteed?',
                'answer' => 'No. The calculator is a reference tool based on collected page data and should be checked again after updates or market changes.',
            ],
        ];

        return [
            'locale' => self::DEFAULT_LOCALE,
            'urlPrefix' => $urlPrefix,
            'baseUrl' => $baseUrl,
            't' => self::I18N_EN,
            'seoTitle' => $title,
            'seoDescription' => $description,
            'canonical' => $canonical,
            'hreflangLinks' => [],
            'cssHref' => $cssHref,
            'websiteJsonLd' => $this->websiteJsonLd($baseUrl, $description),
            'jsonLd' => $this->gag2CalculatorJsonLd($title, $description, $canonical, $faqItems),
            'calculatorData' => $data,
            'faqItems' => $faqItems,
        ];
    }

    public function brainrotsListViewContext(string $siteSlug = self::SITE_SLUG_SAB_CALCULATOR, ?Collection $preloadedItems = null): array
    {
        $ctx = new SabSiteContext($siteSlug);
        $site = $ctx->resolve();
        $game = $ctx->dataGame($site);
        $defaultBase = $ctx->isCalculatorOnly($site) ? 'https://sabcalculator.com' : 'https://sabexistcount.com';
        $baseUrl = rtrim($site->base_url ?: $defaultBase, '/');
        $items = $preloadedItems ?? $this->loadItems($game);
        $urlPrefix = $ctx->previewUrlPrefix();
        $brand = $ctx->brand($site);
        $cssHref = $ctx->cssHrefForRender();

        return $this->brainrotsListViewPayload($urlPrefix, $baseUrl, $items, $cssHref, $site, $ctx);
    }

    private function brainrotsListViewPayload(string $urlPrefix, string $baseUrl, Collection $items, string $cssHref, SeoSite $site, SabSiteContext $ctx): array
    {
        $calculatorData = $this->calculatorData($items, $site);
        $priceHistory = $this->brainrotPriceHistoryForItems($items);

        $calculatorData['brainrots'] = array_map(function (array $br) use ($priceHistory): array {
            $br['priceHistory'] = $priceHistory[$br['slug']] ?? [];
            return $br;
        }, $calculatorData['brainrots']);

        $pageUrl = rtrim($baseUrl, '/') . '/' . self::PAGE_BRAINROTS_LIST;

        return [
            'locale' => self::DEFAULT_LOCALE,
            'urlPrefix' => $urlPrefix,
            'baseUrl' => $baseUrl,
            'brand' => $ctx->brand($site),
            'cssHref' => $cssHref,
            'calculatorOnly' => $ctx->isCalculatorOnly($site),
            'siteSlug' => $ctx->siteSlug(),
            'calculatorData' => $calculatorData,
            'seoTitle' => 'Steal a Brainrot Values List 2026 | All Brainrots Income & Value',
            'seoDescription' => 'Browse all 468+ Steal a Brainrot items. View income per second, Robux value, shop cost, rarity, and mutations for every brainrot.',
            'canonical' => $pageUrl,
            'hreflangLinks' => [],
            'websiteJsonLd' => $this->websiteJsonLd($baseUrl, 'Steal a Brainrot value & income list for all brainrots.'),
            'jsonLd' => null,
        ];
    }

    /**
     * Build variant-id-keyed 30-day price history from the per-slug JSON file.
     *
     * @param  Collection<int, SeoItemVariant>|list<int>  $variantsOrIds
     * @return array<int, list<array{date: string, value: float}>>
     */
    private function brainrotPriceHistoryByVariantIds(Collection|array $variantsOrIds, ?string $itemSlug = null): array
    {
        $variantIds = $variantsOrIds instanceof Collection
            ? $variantsOrIds->pluck('id')->filter()->all()
            : array_values(array_filter($variantsOrIds));

        if ($variantIds === []) {
            return [];
        }

        if ($itemSlug) {
            return $this->pickPriceHistoryForVariantIds(
                $this->priceHistoryFromSlugFile($itemSlug),
                $variantIds,
            );
        }

        return [];
    }

    /**
     * @return array<int, list<array{date: string, value: float}>>
     */
    private function priceHistoryFromSlugFile(string $slug): array
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || ! preg_match('/^[a-z0-9][a-z0-9\-]*$/', $slug)) {
            return [];
        }

        $path = storage_path('app/seo/sab-price-history/'.$slug.'.json');
        if (! File::isFile($path)) {
            return [];
        }

        $decoded = json_decode(File::get($path), true);
        $variants = is_array($decoded) ? ($decoded['variants'] ?? null) : null;
        if (! is_array($variants)) {
            return [];
        }

        $cutoff = Carbon::now()->subDays(30)->toDateString();
        $result = [];
        foreach ($variants as $variantId => $series) {
            if (! is_array($series) || $series === []) {
                continue;
            }
            $points = SabPriceHistoryWriter::filterSince($series, $cutoff);
            if ($points !== []) {
                $result[(int) $variantId] = $points;
            }
        }

        return $result;
    }

    /**
     * @param  array<int, list<array{date: string, value: float}>>  $history
     * @param  list<int|string>  $variantIds
     * @return array<int, list<array{date: string, value: float}>>
     */
    private function pickPriceHistoryForVariantIds(array $history, array $variantIds): array
    {
        $result = [];
        foreach ($variantIds as $id) {
            $key = (int) $id;
            if (isset($history[$key])) {
                $result[$key] = $history[$key];
            }
        }

        return $result;
    }

    /**
     * Build a slug-keyed map of 30-day price history from per-item JSON files.
     *
     * @return array<string, list<array{date: string, value: float}>>
     */
    private function brainrotPriceHistoryForItems(Collection $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $baseVariant = $item->variants->firstWhere('variant_key', 'base')
                ?? $item->variants->firstWhere('variant_type', 'base');
            if (! $baseVariant) {
                continue;
            }
            $history = $this->priceHistoryFromSlugFile((string) $item->slug);
            if (isset($history[$baseVariant->id])) {
                $result[$item->slug] = $history[$baseVariant->id];
            }
        }

        return $result;
    }

    private function brainrotPriceHistoryForItem(SeoItem $item): array
    {
        return $this->brainrotPriceHistoryForItems(collect([$item]))[$item->slug] ?? [];
    }

    /**
     * Per-mutation rot.rocks price payload for product detail pages.
     *
     * @return array{baseIncome: float, baseRobuxValue: float|null, mutations: list<array<string, mixed>>}|null
     */
    public function itemMutationPricePayload(SeoItem $item): ?array
    {
        $rot = data_get($item->attributes_json, 'rot_rocks', []);
        $sourceSlug = SabRotCalculatorSyncService::SOURCE_SLUG;

        $baseVariant = $item->variants->firstWhere('variant_key', 'base')
            ?? $item->variants->firstWhere('variant_type', 'base');

        $baseValue = $baseVariant
            ? $baseVariant->currentValues->first(fn ($cv) => ($cv->source?->slug) === $sourceSlug)?->value_normalized
            : null;
        $baseRobuxValue = $baseValue !== null
            ? (float) $baseValue
            : (data_get($rot, 'robux_value') !== null ? (float) data_get($rot, 'robux_value') : null);

        $mutationVariants = $this->rotRocksCatalogMutationVariants($item->variants)->sortBy('name');

        $variantsForHistory = collect();
        if ($baseVariant) {
            $variantsForHistory->push($baseVariant);
        }
        $variantsForHistory = $variantsForHistory->merge($mutationVariants);

        $historyByVariantId = $this->brainrotPriceHistoryByVariantIds($variantsForHistory, (string) $item->slug);

        $mutations = [];

        if ($baseVariant) {
            $mutations[] = [
                'id' => 'base',
                'name' => 'Default',
                'multiplier' => 1,
                'image' => null,
                'robuxValue' => $baseRobuxValue,
                'priceHistory' => $historyByVariantId[$baseVariant->id] ?? [],
            ];
        }

        foreach ($mutationVariants as $variant) {
            $value = $variant->currentValues->first(fn ($cv) => ($cv->source?->slug) === $sourceSlug);
            $mutations[] = [
                'id' => $variant->variant_key,
                'name' => $variant->mutation_name ?: $variant->variant_name,
                'multiplier' => (float) ($variant->multiplier ?? 1),
                'image' => $this->calculatorImageForVariant($variant, 'mutations'),
                'robuxValue' => $value?->value_normalized !== null ? (float) $value->value_normalized : null,
                'priceHistory' => $historyByVariantId[$variant->id] ?? [],
            ];
        }

        $mutations = collect($mutations)->unique('name')->values()->all();

        $hasSignal = collect($mutations)->contains(function (array $mut): bool {
            return ($mut['robuxValue'] ?? null) !== null || ($mut['priceHistory'] ?? []) !== [];
        });

        if (! $hasSignal || $mutations === []) {
            return null;
        }

        $baseIncome = (float) (data_get($rot, 'base_income') ?? preg_replace('/[^0-9.]/', '', (string) $item->avg_coins_raw));

        return [
            'baseIncome' => $baseIncome,
            'baseRobuxValue' => $baseRobuxValue,
            'mutations' => $mutations,
        ];
    }

    /**
     * Public JSON payload for product 30D price charts.
     *
     * @return array{found: bool, slug: string, mutations: list<array<string, mixed>>}
     */
    public function itemPriceHistoryApiPayload(string $slug): array
    {
        $slug = strtolower(trim($slug));
        $site = SeoSite::query()->where('slug', self::SITE_SLUG)->first();
        if ($site === null) {
            return ['found' => false, 'slug' => $slug, 'mutations' => []];
        }
        $game = SeoGame::query()
            ->where('seo_site_id', $site->id)
            ->where('slug', self::GAME_SLUG)
            ->first();
        if ($game === null) {
            return ['found' => false, 'slug' => $slug, 'mutations' => []];
        }

        $item = SeoItem::query()
            ->where('seo_game_id', $game->id)
            ->where('slug', $slug)
            ->with(['variants.currentValues.source'])
            ->first();
        if ($item === null) {
            return ['found' => false, 'slug' => $slug, 'mutations' => []];
        }

        $payload = $this->itemMutationPricePayload($item);

        return [
            'found' => true,
            'slug' => (string) $item->slug,
            'mutations' => is_array($payload['mutations'] ?? null) ? $payload['mutations'] : [],
        ];
    }

    public function existCountGalleryViewContext(string $locale = self::DEFAULT_LOCALE): array
    {
        $locale = self::DEFAULT_LOCALE;

        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $i18n = $this->loadI18n($site);
        $t = $this->mergeSabTranslations($locale, $i18n);
        $urlPrefix = self::publicUrlPrefix($locale);

        return $this->existCountGalleryViewPayload(
            $urlPrefix,
            $locale,
            $baseUrl,
            $t,
            $this->loadExistCountGalleryFromStorage(),
            self::CSS_HREF_LARAVEL
        );
    }

    public function itemViewContext(string $slug, string $locale = self::DEFAULT_LOCALE): array
    {
        $locale = self::DEFAULT_LOCALE;

        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $game = SeoGame::where('seo_site_id', $site->id)->where('slug', self::GAME_SLUG)->firstOrFail();

        $baseUrl   = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        // Preview runs on dev server; use root-relative prefix so all links stay local
        $urlPrefix = self::publicUrlPrefix($locale);

        $relations = ['variants.currentValues.source', 'translations'];
        if ($this->observationsTableExists()) {
            $relations[] = 'variants.observations';
        }

        $item = SeoItem::where('seo_game_id', $game->id)
            ->where('slug', $slug)
            ->with($relations)
            ->firstOrFail();

        $i18n = $this->loadI18n($site);
        $t = $this->mergeSabTranslations($locale, $i18n);

        $translation  = $item->translation($locale);
        $displayName  = $translation?->name ?: $item->name;
        $aboutFields  = $this->cleanAboutFields($item, $translation?->description);
        $seoTitle = $translation?->seo_title ?: $this->itemDefaultSeoTitle($item, $displayName);
        $seoDescription = $translation?->seo_description ?: $this->itemDefaultSeoDescription($item, $displayName);

        $currentValues = $this->groupCurrentValues($item);
        $history       = $this->itemHistory($item);
        $mutationPriceData = $this->itemMutationPricePayload($item);
        $priceHistory  = $mutationPriceData['mutations'][0]['priceHistory']
            ?? $this->brainrotPriceHistoryForItem($item);
        $calculatorBrainrot = $this->calculatorBrainrotForItem($item, $site);
        $priceHistoryUrl = rtrim($urlPrefix, '/').'/products/'.self::productPublicSlug($item->slug).'/price-history.json';
        $itemUrl = $this->localePublicUrl($baseUrl, $locale, 'products/' . self::productPublicSlug($item->slug) . '.html');
        $homeUrl = $this->localePublicUrl($baseUrl, $locale, 'index.html');
        $breadcrumbCurrent = "{$displayName} {$t['item_page_exist_count_label']}";

        return [
            'locale'         => $locale,
            'urlPrefix'      => $urlPrefix,
            'baseUrl'        => $baseUrl,
            't'              => $t,
            'hreflangLinks'  => [],
            'canonical'      => $itemUrl,
            'robots'         => self::productRobotsForSlug((string) $item->slug),
            'seoTitle'       => $seoTitle,
            'seoDescription' => $seoDescription,
            'item'           => $item,
            'displayName'    => $displayName,
            'description'    => $aboutFields['description'],
            'rotRocksDescriptionHtml' => $aboutFields['rotRocksDescriptionHtml'],
            'currentValues'  => $currentValues,
            'history'        => $history,
            'priceHistory'   => $priceHistory,
            'priceHistoryUrl' => $priceHistoryUrl,
            'mutationPriceData' => $mutationPriceData,
            'calculatorBrainrot' => $calculatorBrainrot,
            'websiteJsonLd'  => $this->websiteJsonLd($baseUrl, $seoDescription),
            'jsonLd'         => $this->itemJsonLd($seoTitle, $seoDescription, $itemUrl, $homeUrl, $breadcrumbCurrent, $t),
            'cssHref'        => self::CSS_HREF_LARAVEL,
            'playRobBrainrotHref' => $urlPrefix . '/' . self::gamePublicPath(self::PAGE_GAME_ROB),
        ];
    }

    public function itemV2ViewContext(string $slug, string $locale = self::DEFAULT_LOCALE): array
    {
        $locale = self::DEFAULT_LOCALE;

        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $game = SeoGame::where('seo_site_id', $site->id)->where('slug', self::GAME_SLUG)->firstOrFail();

        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $urlPrefix = '/seo/sab/preview2';

        $item = SeoItem::where('seo_game_id', $game->id)
            ->where('slug', $slug)
            ->with([
                'variants.currentValues.source',
                'variants.currentValues.lastObservation',
                'variants.observations.source',
                'translations',
            ])
            ->firstOrFail();

        $i18n = $this->loadI18n($site);
        $t = $this->mergeSabTranslations($locale, $i18n);

        $translation = $item->translation($locale);
        $displayName = $translation?->name ?: $item->name;
        $description = $translation?->description ?: $item->description;
        $seoTitle = $translation?->seo_title ?: $this->itemDefaultSeoTitle($item, $displayName);
        $seoDescription = $translation?->seo_description ?: $this->itemDefaultSeoDescription($item, $displayName);

        $sourcePool = $this->itemSourcePool($site, $game, $item);
        $recentObservations = $this->recentItemObservations($item);
        $dataStatus = $this->itemDataStatus($item, $sourcePool);
        $itemUrl = $this->localePublicUrl($baseUrl, $locale, 'products/' . self::productPublicSlug($item->slug) . '.html');
        $homeUrl = $this->localePublicUrl($baseUrl, $locale, 'index.html');
        $breadcrumbCurrent = "{$displayName} {$t['item_page_exist_count_label']}";

        return [
            'locale'             => $locale,
            'urlPrefix'          => $urlPrefix,
            'baseUrl'            => $baseUrl,
            't'                  => $t,
            'hreflangLinks'      => [],
            'canonical'          => $itemUrl,
            'robots'             => self::productRobotsForSlug((string) $item->slug),
            'seoTitle'           => $seoTitle,
            'seoDescription'     => $seoDescription,
            'item'               => $item,
            'displayName'        => $displayName,
            'description'        => $description,
            'currentValues'      => $this->groupCurrentValues($item),
            'history'            => $this->itemHistory($item),
            'sourcePool'         => $sourcePool,
            'recentObservations' => $recentObservations,
            'dataStatus'         => $dataStatus,
            'websiteJsonLd'      => $this->websiteJsonLd($baseUrl, $seoDescription),
            'jsonLd'             => $this->itemJsonLd($seoTitle, $seoDescription, $itemUrl, $homeUrl, $breadcrumbCurrent, $t),
            'cssHref'            => self::CSS_HREF_LARAVEL,
        ];
    }

    /**
     * Preview context for a Calculator-site product detail page.
     * Called by SabPreviewController::calculatorItem().
     */
    public function calculatorItemViewContext(string $slug, string $siteSlug = self::SITE_SLUG_SAB_CALCULATOR): array
    {
        $ctx  = new SabSiteContext($siteSlug);
        $site = $ctx->resolve();
        $game = $ctx->dataGame($site);

        $baseUrl   = rtrim($site->base_url ?: 'https://sabcalculator.com', '/');
        $urlPrefix = '/seo/sab/preview/' . $siteSlug;

        $item = SeoItem::where('seo_game_id', $game->id)
            ->where('slug', $slug)
            ->with(['variants' => fn ($q) => $q->whereIn('variant_type', ['base', 'mutation'])->with('currentValues.source'), 'translations'])
            ->firstOrFail();

        $translation  = $item->translation(self::DEFAULT_LOCALE);
        $displayName  = $translation?->name ?: $item->name;
        $aboutFields  = $this->cleanAboutFields($item, $translation?->description);

        $calcData    = $this->calculatorData(collect([$item]), $site);
        $brainrotData = $calcData['brainrots'][0] ?? null;

        $mutationPriceData = $this->itemMutationPricePayload($item);
        $priceHistory = $mutationPriceData['mutations'][0]['priceHistory']
            ?? $this->brainrotPriceHistoryForItem($item);

        $seoTitle       = $this->calculatorItemSeoTitle($displayName, $brainrotData);
        $seoDescription = $this->calculatorItemSeoDescription($displayName, $brainrotData);
        $itemUrl        = rtrim($baseUrl, '/') . '/products/' . self::productPublicSlug($item->slug);

        return [
            'locale'                  => self::DEFAULT_LOCALE,
            'urlPrefix'               => $urlPrefix,
            'baseUrl'                 => $baseUrl,
            'brand'                   => $ctx->brand($site),
            'cssHref'                 => $ctx->cssHrefForRender(),
            'item'                    => $item,
            'displayName'             => $displayName,
            'description'             => $aboutFields['description'],
            'rotRocksDescriptionHtml' => $aboutFields['rotRocksDescriptionHtml'],
            'brainrotData'            => $brainrotData,
            'mutationPriceData'       => $mutationPriceData,
            'priceHistory'            => $priceHistory,
            'seoTitle'                => $seoTitle,
            'seoDescription'          => $seoDescription,
            'canonical'               => $itemUrl,
            'robots'                  => self::productRobotsForSlug((string) $item->slug),
            'websiteJsonLd'           => $this->websiteJsonLd($baseUrl, $seoDescription),
            'jsonLd'                  => null,
            'hreflangLinks'           => [],
        ];
    }

    public function newsViewContext(string $slug, string $locale = self::DEFAULT_LOCALE): array
    {
        $locale = self::DEFAULT_LOCALE;
        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $article = SeoNewsArticle::query()
            ->where('seo_site_id', $site->id)
            ->where('type', SeoNewsArticle::TYPE_NEWS)
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->firstOrFail();

        $i18n = $this->loadI18n($site);

        return $this->newsViewPayload(
            article: $article,
            urlPrefix: self::publicUrlPrefix($locale),
            locale: $locale,
            baseUrl: $baseUrl,
            t: $this->mergeSabTranslations($locale, $i18n),
            cssHref: self::CSS_HREF_LARAVEL,
        );
    }

    public function newsListViewContext(string $locale = self::DEFAULT_LOCALE): array
    {
        $locale = self::DEFAULT_LOCALE;
        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $articles = $this->loadPublishedNews($site)->where('locale', $locale)->values();
        $urlPrefix = self::publicUrlPrefix($locale);
        $i18n = $this->loadI18n($site);
        $t = $this->mergeSabTranslations($locale, $i18n);

        return $this->newsIndexViewPayload($articles, $urlPrefix, $locale, $baseUrl, $t, self::CSS_HREF_LARAVEL);
    }

    /**
     * @return array{path: string, url: string}
     */
    public function renderNewsArticleById(int $newsId): array
    {
        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $article = SeoNewsArticle::query()
            ->where('seo_site_id', $site->id)
            ->where('id', $newsId)
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->firstOrFail();

        $outputPath = $this->filesystemOutputForRender($site, null);
        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $i18n = $this->loadI18n($site);
        if ((int) $article->type === SeoNewsArticle::TYPE_STATIC_PAGE) {
            $path = $this->renderStaticPage($outputPath, $baseUrl, $article);
            $this->syncStaticCssToWebsite($outputPath);
            $this->syncSabExistCountFavicon($outputPath);

            return [
                'path' => $path,
                'url' => $this->localePublicUrl($baseUrl, self::DEFAULT_LOCALE, "{$article->slug}.html"),
            ];
        }
        if ((string) $article->locale !== self::DEFAULT_LOCALE) {
            throw new \RuntimeException('SAB news detail pages are English-only; render the English article instead.');
        }

        $locale = self::DEFAULT_LOCALE;
        $dir = $outputPath;
        $urlPrefix = '';
        $t = $this->mergeSabTranslations($locale, $i18n);

        $this->ensureDir("{$dir}/news");
        $path = $this->renderNewsArticle($dir, $urlPrefix, $locale, $t, $article, $baseUrl);

        $listArticles = $this->loadPublishedNews($site)
            ->where('locale', $locale)
            ->values();
        $this->renderNewsIndexPage($dir, $urlPrefix, $locale, $t, $listArticles, $baseUrl);

        $this->syncStaticCssToWebsite($outputPath);
        $this->syncSabExistCountFavicon($outputPath);

        return [
            'path' => $path,
            'url' => $this->localePublicUrl($baseUrl, $locale, "news/{$article->slug}.html"),
        ];
    }

    /** Laravel preview: English-only root static pages under /seo/sab/preview/{slug}. */
    public function staticPagePreviewContext(string $slug): array
    {
        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        $baseUrl = rtrim($site->base_url ?: 'https://sabexistcount.com', '/');
        $article = $this->findPublishedStaticPage($site, $slug);

        return $this->staticPageViewPayload($article, self::publicUrlPrefix(), $baseUrl, self::CSS_HREF_LARAVEL);
    }

    /** Laravel preview: calculator-only legal pages under /seo/sabcalculator/preview/{slug}. */
    public function calculatorStaticPagePreviewContext(string $slug, string $siteSlug = self::SITE_SLUG_SAB_CALCULATOR): array
    {
        abort_unless(in_array($slug, self::STATIC_PAGE_SLUGS, true), 404);

        $ctx = new SabSiteContext($siteSlug);
        abort_unless($ctx->isCalculatorOnly(), 404);
        $site = $ctx->resolve();
        $baseUrl = rtrim($site->base_url ?: 'https://sabcalculator.com', '/');
        $article = $this->findPublishedStaticPage($site, $slug);

        return $this->calculatorStaticPageViewPayload(
            $article,
            '/seo/'.$siteSlug.'/preview',
            $baseUrl,
            $ctx->cssHrefForRender(),
            $site,
            $ctx
        );
    }

    /**
     * Admin / CLI: render one calculator-site static page to disk.
     *
     * @return array{path: string, url: string}
     */
    public function renderCalculatorStaticPageById(int $newsId): array
    {
        $ctx = new SabSiteContext(self::SITE_SLUG_SAB_CALCULATOR);
        $site = $ctx->resolve();
        $article = SeoNewsArticle::query()
            ->where('seo_site_id', $site->id)
            ->where('id', $newsId)
            ->where('type', SeoNewsArticle::TYPE_STATIC_PAGE)
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->firstOrFail();

        abort_unless(in_array((string) $article->slug, self::STATIC_PAGE_SLUGS, true), 404);

        $outputPath = $this->filesystemOutputForRender($site, null);
        $baseUrl = rtrim($site->base_url ?: 'https://sabcalculator.com', '/');
        $path = $this->renderCalculatorStaticPage($outputPath, $baseUrl, $article, $site, $ctx);
        $this->syncStaticCssToWebsite($outputPath, $ctx);
        $this->syncCalculatorBaseCss($outputPath);
        $this->syncCalculatorFavicon($outputPath);

        return [
            'path' => $path,
            'url' => $this->calculatorStaticPagePublicUrl($baseUrl, (string) $article->slug),
        ];
    }

    /**
     * Public/canonical URL for calculator-site static pages (always extensionless).
     */
    public function calculatorStaticPagePublicUrl(string $baseUrl, string $slug): string
    {
        return rtrim($baseUrl, '/').'/'.ltrim($slug, '/');
    }

    /**
     * Preview/static href for calculator-site static pages (always extensionless).
     */
    public static function calculatorStaticPageHref(string $urlPrefix, string $slug): string
    {
        $prefix = rtrim($urlPrefix, '/');

        return ($prefix === '' ? '' : $prefix).'/'.ltrim($slug, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function staticPageViewPayload(SeoNewsArticle $article, string $urlPrefix, string $baseUrl, string $cssHref): array
    {
        $desc = $article->meta_description ?: ($article->excerpt ?: '');
        $canonical = $this->localePublicUrl($baseUrl, self::DEFAULT_LOCALE, "{$article->slug}.html");

        [$pageHeading, $bodyHtml] = $this->staticPageBodyForDisplay($article);

        return [
            'locale'         => self::DEFAULT_LOCALE,
            'urlPrefix'      => $urlPrefix,
            'baseUrl'        => $baseUrl,
            't'              => self::I18N_EN,
            'article'        => $article,
            'pageHeading'    => $pageHeading,
            'legalSlug'      => (string) $article->slug,
            'seoTitle'       => $article->meta_title ?: "{$article->title} | SAB Exist Count",
            'seoDescription' => $desc,
            'canonical'      => $canonical,
            'hreflangLinks'  => [],
            'cssHref'       => $cssHref,
            'websiteJsonLd' => $this->websiteJsonLd($baseUrl, $desc),
            'bodyHtml'      => $this->normalizeNewsBodyImagePaths($bodyHtml),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculatorStaticPageViewPayload(
        SeoNewsArticle $article,
        string $urlPrefix,
        string $baseUrl,
        string $cssHref,
        SeoSite $site,
        SabSiteContext $ctx,
    ): array {
        $desc = $article->meta_description ?: ($article->excerpt ?: '');
        $slug = (string) $article->slug;
        $canonical = $this->calculatorStaticPagePublicUrl($baseUrl, $slug);
        [$pageHeading, $bodyHtml] = $this->staticPageBodyForDisplay($article);
        $brand = $ctx->brand($site);
        $siteName = (string) ($brand['site_name'] ?? 'SAB Calculator');

        return [
            'locale'         => self::DEFAULT_LOCALE,
            'urlPrefix'      => $urlPrefix,
            'baseUrl'        => $baseUrl,
            't'              => self::I18N_EN,
            'article'        => $article,
            'pageHeading'    => $pageHeading,
            'legalSlug'      => $slug,
            'seoTitle'       => $article->meta_title ?: "{$article->title} | {$siteName}",
            'seoDescription' => $desc,
            'canonical'      => $canonical,
            'hreflangLinks'  => [],
            'cssHref'        => $cssHref,
            'websiteJsonLd'  => $this->websiteJsonLd($baseUrl, $desc),
            'bodyHtml'       => $this->normalizeNewsBodyImagePaths($bodyHtml),
            'calculatorOnly' => true,
            'brand'          => $brand,
            'siteSlug'       => $ctx->siteSlug(),
        ];
    }

    private function findPublishedStaticPage(SeoSite $site, string $slug): SeoNewsArticle
    {
        return SeoNewsArticle::query()
            ->where('seo_site_id', $site->id)
            ->where('type', SeoNewsArticle::TYPE_STATIC_PAGE)
            ->where('locale', self::DEFAULT_LOCALE)
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->firstOrFail();
    }

    private function renderCalculatorStaticPage(
        string $dir,
        string $baseUrl,
        SeoNewsArticle $article,
        SeoSite $site,
        SabSiteContext $ctx,
    ): string {
        $html = view(
            'seo.sab.static-page-calculator',
            $this->calculatorStaticPageViewPayload($article, '', $baseUrl, $ctx->cssHrefForRender(), $site, $ctx)
        )->render();

        $path = "{$dir}/{$article->slug}.html";
        file_put_contents($path, $html);

        return $path;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function staticPageBodyForDisplay(SeoNewsArticle $article): array
    {
        $bodyHtml = ltrim((string) $article->body_html);
        if (preg_match('/\A([^<\r\n][^\r\n<]{2,120})(?:\r?\n|\s)*(?=<)/u', $bodyHtml, $matches) === 1) {
            $heading = trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $bodyHtml = ltrim(substr($bodyHtml, strlen($matches[0])));

            return [$heading, $bodyHtml];
        }

        return [(string) $article->title, $bodyHtml];
    }

    /**
     * @return array<string, mixed>
     */
    private function homeViewPayload(string $urlPrefix, string $locale, string $baseUrl, array $t, Collection $items, string $cssHref): array
    {
        $hreflang = $this->hreflangLinks('index.html', $baseUrl, self::HOME_LOCALES);
        $listedItems = self::listedItems($items);

        // Main `#brainrot-list` uses its own ordering; summaries / sidebar blocks stay on listed items.
        $tableItems = $this->sortItemsForHomeTable($listedItems);

        $existCounts = $listedItems->pluck('total_exists')->filter()->values();

        $stats = [
            'total'   => $listedItems->count(),
            'lowest'  => $existCounts->min(),
            'highest' => $existCounts->max(),
        ];

        $itemSummary = function (SeoItem $item): array {
            $latestDate = $item->variants
                ->flatMap(fn ($variant) => $variant->currentValues)
                ->map(fn ($cv) => $cv->changed_at ?: $cv->collected_at)
                ->filter()
                ->sortDesc()
                ->first();

            $imageSrc = self::listingImageSrc($item);
            $cvBySource = self::mergedCvBySourceForVariants($item->variants);

            return [
                'item'         => $item,
                'name'         => $item->name,
                'slug'         => self::productPublicSlug($item->slug),
                'rarity'       => $item->rarity,
                'imageSrc'     => $imageSrc,
                'existCount'   => $item->total_exists,
                'value'        => self::preferValueFromCvBySource($cvBySource),
                'latestDate'   => $latestDate,
            ];
        };

        $summaries = $listedItems
            ->filter(fn (SeoItem $item) => self::shouldLinkProduct($item))
            ->map($itemSummary);

        $topRareItems = $summaries
            ->filter(fn ($entry) => $entry['existCount'] !== null
                && self::canonicalRarityKey($entry['rarity'] ?? null) === 'og')
            ->sortBy(fn ($entry) => sprintf('%012d-%s', (int) $entry['existCount'], strtolower($entry['name'])))
            ->take(10)
            ->values();

        $recentlyChangedItems = $summaries
            ->filter(fn ($entry) => $entry['latestDate'] !== null && $entry['existCount'] !== null)
            ->sortByDesc('latestDate')
            ->take(8)
            ->values();

        $seoTitle = $t['meta_title'] ?? 'Steal a Brainrot Exist Count Tracker | SAB Values & Rarity';
        $seoDescription = $t['meta_description']
            ?? 'Check the latest Steal a Brainrot exist counts, rarity notes, known brainrot totals, mutation variants, and how SAB exist count works.';

        $homeUrl = $this->localePublicUrl($baseUrl, $locale, 'index.html');
        $jsonLd = $this->homeJsonLd($seoTitle, $seoDescription, $homeUrl, $locale, $t);

        return [
            'locale'         => $locale,
            'urlPrefix'      => $urlPrefix,
            'productUrlPrefix' => $this->productUrlPrefix($urlPrefix),
            'baseUrl'        => $baseUrl,
            't'              => $t,
            'hreflangLinks'  => $hreflang,
            'languageLinks'  => $this->languageLinks($urlPrefix, $locale, 'index'),
            'canonical'      => $homeUrl,
            'seoTitle'       => $seoTitle,
            'seoDescription' => $seoDescription,
            'items'          => $tableItems,
            'topRareItems'   => $topRareItems,
            'recentlyChangedItems' => $recentlyChangedItems,
            'stats'          => $stats,
            'wikiHref'       => rtrim($this->productUrlPrefix($urlPrefix), '/').'/'.self::PAGE_WIKI,
            'rarityTags'     => $this->rarityFilterTags($t),
            'rarityTagCounts' => $this->rarityTagCounts($tableItems),
            'statsMonthYear' => $this->formatStatsMonthYear($locale),
            'websiteJsonLd'  => $this->websiteJsonLd($baseUrl, $seoDescription),
            'jsonLd'         => $jsonLd,
            'cssHref'        => $cssHref,
        ];
    }

    private function formatStatsMonthYear(string $locale): string
    {
        try {
            $lc = match ($locale) {
                'pt', 'es', 'de', 'ru', 'fr', 'tr', 'pl' => $locale,
                default => 'en',
            };

            return Carbon::now()->locale($lc)->translatedFormat('M Y');
        } catch (\Throwable) {
            return date('M Y');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function existCountsListViewPayload(string $urlPrefix, string $locale, string $baseUrl, array $t, Collection $allItems, string $cssHref): array
    {
        $filtered = $this->sortItemsForHomeTable(self::listedItems($allItems));

        $monthLabel = $this->localizedMonthLabel(Carbon::now(), $locale);
        $withMonth = function (string $template, string $fallback) use ($monthLabel): string {
            $source = str_contains($template, '{month}') ? $template : $fallback;

            return str_replace('{month}', $monthLabel, $source);
        };

        $seoTitle = $withMonth(
            (string) $t['exist_counts_list_meta_title'],
            self::I18N_EN['exist_counts_list_meta_title'],
        );
        $seoDescription = $withMonth(
            (string) $t['exist_counts_list_meta_description'],
            self::I18N_EN['exist_counts_list_meta_description'],
        );
        $t['exist_counts_list_h1'] = $withMonth(
            (string) $t['exist_counts_list_h1'],
            self::I18N_EN['exist_counts_list_h1'],
        );
        $t['exist_counts_list_h1_cyan'] = $withMonth(
            (string) $t['exist_counts_list_h1_cyan'],
            self::I18N_EN['exist_counts_list_h1_cyan'],
        );
        $t['exist_counts_list_month_badge'] = $withMonth(
            (string) ($t['exist_counts_list_month_badge'] ?? '{month}'),
            self::I18N_EN['exist_counts_list_month_badge'],
        );

        $pageUrl = $this->localePublicUrl($baseUrl, $locale, self::PAGE_EXIST_COUNTS_LIST);
        $listRows = $this->buildExistCountsListRows($filtered);
        $faqItems = $this->buildExistCountsListFaqItems($t, $baseUrl, $locale);

        return [
            'locale'         => $locale,
            'urlPrefix'      => $urlPrefix,
            'productUrlPrefix' => $this->productUrlPrefix($urlPrefix),
            'baseUrl'        => $baseUrl,
            't'              => $t,
            'hreflangLinks'  => $this->hreflangLinks(self::PAGE_EXIST_COUNTS_LIST, $baseUrl, self::MULTILINGUAL_PAGE_LOCALES),
            'languageLinks'  => $this->languageLinks($urlPrefix, $locale, self::PAGE_EXIST_COUNTS_LIST),
            'canonical'      => $pageUrl,
            'seoTitle'       => $seoTitle,
            'seoDescription' => $seoDescription,
            'items'          => $filtered,
            'ssrItems'       => $filtered->take(self::EXIST_COUNTS_LIST_PER_PAGE)->values(),
            'listRows'       => $listRows,
            'listPerPage'    => self::EXIST_COUNTS_LIST_PER_PAGE,
            'listStatTotal'  => $filtered->count(),
            'rarityTags'     => $this->rarityFilterTags($t),
            'rarityTagCounts' => $this->rarityTagCounts($filtered),
            'existCountsListFaqItems' => $faqItems,
            'websiteJsonLd'  => $this->websiteJsonLd($baseUrl, $seoDescription),
            'jsonLd'         => $this->valueListPageJsonLd($seoTitle, $seoDescription, $pageUrl, $locale, $faqItems),
            'cssHref'        => $cssHref,
        ];
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function buildExistCountsListFaqItems(array $t, string $baseUrl, string $locale): array
    {
        $calculatorUrl = $this->localePublicUrl($baseUrl, $locale, self::PAGE_TRADING_CALCULATOR);
        $valueListUrl = $this->localePublicUrl($baseUrl, self::DEFAULT_LOCALE, self::PAGE_VALUE_LIST);
        $calculatorLabel = (string) ($t['exist_counts_list_faq_calculator_link'] ?? 'SAB Trade Calculator');
        $valueListLabel = (string) ($t['exist_counts_list_faq_value_list_link'] ?? 'Value List');
        $calculatorLink = '<a href="' . e($calculatorUrl) . '" class="text-cyan-300 hover:text-cyan-200">' . e($calculatorLabel) . '</a>';
        $valueListLink = '<a href="' . e($valueListUrl) . '" class="text-cyan-300 hover:text-cyan-200">' . e($valueListLabel) . '</a>';

        $placeholders = [
            '{calculator_link}' => $calculatorLink,
            '{value_list_link}' => $valueListLink,
        ];

        $answer = static function (string $template) use ($placeholders): string {
            return str_replace(array_keys($placeholders), array_values($placeholders), $template);
        };

        $items = [];
        for ($i = 1; $i <= 8; $i++) {
            $items[] = [
                'question' => (string) ($t["exist_counts_list_faq_q{$i}"] ?? ''),
                'answer' => $answer((string) ($t["exist_counts_list_faq_a{$i}"] ?? '')),
            ];
        }

        return $items;
    }

    /**
     * Compact row payload for exist-count-list client pagination.
     *
     * @param  Collection<int, SeoItem>  $items
     * @return list<array<string, mixed>>
     */
    private function buildExistCountsListRows(Collection $items): array
    {
        $isDisplayMutLabel = static function (string $label): bool {
            return $label !== ''
                && ! preg_match('/^(n\/a|na|none|null|—|-)$/iu', $label);
        };

        $rows = [];
        foreach ($items as $item) {
            $ec = $item->total_exists;
            $ecDisplay = self::resolveExistCountDisplay($item, $ec !== null ? (int) $ec : null);
            $ecSort = $ecDisplay['sort_value'];
            $ecSortTier = match ($ecDisplay['kind']) {
                'known' => 0,
                'estimated' => 1,
                default => 2,
            };

            $mutationLabel = trim((string) ($item->rarest_mutation_name ?? ''));
            $traitLabel = trim((string) ($item->rarest_trait_name ?? ''));
            if (! $isDisplayMutLabel($mutationLabel)) {
                $mutationLabel = '';
            }
            if (! $isDisplayMutLabel($traitLabel)) {
                $traitLabel = '';
            }

            $canOpenProduct = self::shouldLinkProduct($item);
            $productSlug = self::productPublicSlug($item->slug ?? '');
            $rarityKey = self::canonicalRarityKey($item->rarity ?? null);
            $rarityLabel = $rarityKey === ''
                ? ''
                : ($rarityKey === 'og' ? 'OG' : self::canonicalRarityLabel($rarityKey));

            $searchParts = array_filter([
                $item->name,
                $item->slug,
                $rarityKey,
                $rarityLabel,
                $ec !== null ? (string) (int) $ec : null,
                $ec !== null ? self::formatLargeNumber((float) $ec) : null,
                $ecDisplay['kind'] === 'estimated' ? $ecDisplay['primary'] : null,
                $ecDisplay['kind'] === 'estimated' ? ($ecDisplay['short'] ?? '') : null,
            ], static fn ($part) => trim((string) $part) !== '');
            $searchText = implode(' ', array_unique(array_map(
                static fn ($part) => strtolower(trim((string) $part)),
                $searchParts,
            )));

            [$sigKey] = self::raritySignal($ec !== null ? (float) $ec : null);
            $sigSymbol = match ($sigKey) {
                'very_high' => '+++',
                'medium' => '++',
                'low' => '+',
                'very_low' => '-',
                'extremely_rare' => '--',
                'near_unique' => '1',
                'lowest' => '*',
                default => '',
            };

            $ecs = match ($ecDisplay['kind']) {
                'known' => self::formatLargeNumber((float) $ec),
                'estimated' => (string) ($ecDisplay['short'] ?? $ecDisplay['primary']),
                default => '—',
            };

            $rows[] = [
                's' => $productSlug,
                'n' => (string) $item->name,
                'r' => $rarityKey,
                'rl' => $rarityLabel,
                'e' => $ecSort,
                'tier' => $ecSortTier,
                'img' => (string) (self::listingImageSrc($item) ?? ''),
                'q' => $searchText,
                'k' => $ecDisplay['kind'],
                'ecs' => $ecs,
                'mn' => $mutationLabel,
                'mc' => $item->rarest_mutation_count !== null
                    ? self::formatLargeNumber((float) $item->rarest_mutation_count)
                    : null,
                'tn' => $traitLabel,
                'tc' => $item->rarest_trait_count !== null
                    ? self::formatLargeNumber((float) $item->rarest_trait_count)
                    : null,
                'sk' => $ec !== null ? $sigKey : '',
                'ss' => $ec !== null ? $sigSymbol : '',
                'link' => $canOpenProduct ? 1 : 0,
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    private function rarityFilterTags(array $t): array
    {
        return [
            ''             => $t['rarity_filter_all'] ?? 'All',
            'og'           => $t['rarity_filter_og'] ?? 'OG',
            'legendary'    => $t['rarity_filter_legendary'] ?? 'Legendary',
            'mythic'       => $t['rarity_filter_mythic'] ?? 'Mythic',
            'rare'         => $t['rarity_filter_rare'] ?? 'Rare',
            'secret'       => $t['rarity_filter_secret'] ?? 'Secret',
            'epic'         => $t['rarity_filter_epic'] ?? 'Epic',
            'common'       => $t['rarity_filter_common'] ?? 'Common',
            'brainrot god' => $t['rarity_filter_brainrot_god'] ?? 'Brainrot God',
        ];
    }

    /**
     * @return array<string, int>
     */
    private function rarityTagCounts(Collection $items): array
    {
        $counts = ['' => $items->count()];
        foreach ($items as $item) {
            $key = self::canonicalRarityKey($item->rarity ?? null);
            if ($key !== '') {
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>
     */
    private function valueListViewPayload(string $urlPrefix, string $locale, string $baseUrl, array $t, Collection $allItems, string $cssHref): array
    {
        $filtered = $this->sortValueListItemsByRobux(
            $this->filterItemsWithRotRocks(self::listedItems($allItems))
        );

        $monthLabel = $this->localizedMonthLabel(Carbon::now(), $locale);
        $replacePairs = ['{month}' => $monthLabel];

        $seoTitle = str_replace(array_keys($replacePairs), array_values($replacePairs), (string) $t['value_list_meta_title']);
        $seoDescription = str_replace(array_keys($replacePairs), array_values($replacePairs), (string) $t['value_list_meta_description']);
        $t['value_list_h1'] = str_replace(array_keys($replacePairs), array_values($replacePairs), (string) $t['value_list_h1']);

        $pageUrl = $this->localePublicUrl($baseUrl, $locale, self::PAGE_VALUE_LIST);
        $faqItems = $this->buildValueListFaqItems($t, $urlPrefix, $baseUrl, $locale);
        $valueListRows = $this->buildValueListRows($filtered, $urlPrefix);
        $listRows = $this->buildValueListCompactRows($valueListRows);
        $lastUpdate = $this->calculatorLastUpdatePayload($locale);
        $calculatorMessages = $this->calculatorCopy($locale);
        $productUrlPrefix = $this->productUrlPrefix($urlPrefix);
        $valueChanges = app(SabValueChangesService::class);
        $todayTopGainer = $this->enrichValueChangeRows($valueChanges->topGainers(1, 1), $urlPrefix)[0] ?? null;
        $todayTopLoser = $this->enrichValueChangeRows($valueChanges->topLosers(1, 1), $urlPrefix)[0] ?? null;

        return [
            'locale'         => $locale,
            'urlPrefix'      => $urlPrefix,
            'productUrlPrefix' => $productUrlPrefix,
            'baseUrl'        => $baseUrl,
            't'              => $t,
            'hreflangLinks'  => $this->hreflangLinks(self::PAGE_VALUE_LIST, $baseUrl, self::MULTILINGUAL_PAGE_LOCALES),
            'languageLinks'  => $this->languageLinks($urlPrefix, $locale, self::PAGE_VALUE_LIST),
            'canonical'      => $pageUrl,
            'seoTitle'       => $seoTitle,
            'seoDescription' => $seoDescription,
            'items'          => $filtered,
            'valueListRows'  => $valueListRows,
            'ssrValueListRows' => array_slice($valueListRows, 0, self::VALUE_LIST_PER_PAGE),
            'listRows'       => $listRows,
            'listPerPage'    => self::VALUE_LIST_PER_PAGE,
            'listStatTotal'  => $filtered->count(),
            'valueListFaqItems' => $faqItems,
            'lastUpdateLabel' => (string) ($calculatorMessages['last_update_label'] ?? 'Last update'),
            'valueTrendsLabel' => (string) ($calculatorMessages['value_trends_label'] ?? 'View Daily Value Trends'),
            'calculatorLastUpdatedAt' => $lastUpdate['at'],
            'calculatorLastUpdatedLabel' => $lastUpdate['label'],
            'valueChangesHref' => rtrim($productUrlPrefix, '/') . '/' . self::PAGE_VALUE_CHANGES,
            'todayTopGainer' => $todayTopGainer,
            'todayTopLoser' => $todayTopLoser,
            'websiteJsonLd'  => $this->websiteJsonLd($baseUrl, $seoDescription),
            'jsonLd'         => $this->valueListPageJsonLd($seoTitle, $seoDescription, $pageUrl, $locale, $faqItems),
            'cssHref'        => $cssHref,
        ];
    }

    /**
     * Compact row payload for value-list client pagination.
     *
     * @param  list<array<string, mixed>>  $valueListRows
     * @return list<array<string, mixed>>
     */
    private function buildValueListCompactRows(array $valueListRows): array
    {
        $rows = [];
        foreach ($valueListRows as $row) {
            $item = $row['item'] ?? null;
            $slug = $item instanceof SeoItem
                ? self::productPublicSlug((string) ($item->slug ?? ''))
                : '';
            $cvn = $row['currentValue'] ?? null;
            $rows[] = [
                'n' => (string) ($row['name'] ?? ''),
                's' => $slug,
                'img' => (string) ($row['imageSrc'] ?? ''),
                'link' => ! empty($row['canOpenProduct']) ? 1 : 0,
                'cv' => (string) ($row['currentValueLabel'] ?? '—'),
                'cvn' => is_numeric($cvn) ? (float) $cvn : null,
                'pv' => (string) ($row['previousValueLabel'] ?? '—'),
                'd' => (string) ($row['direction'] ?? 'stable'),
                'dl' => (string) ($row['directionLabel'] ?? 'Stable'),
                'dp' => (string) ($row['deltaPctLabel'] ?? '—'),
                'dd' => (string) ($row['deltaLabel'] ?? '—'),
                'dm' => (string) ($row['demandLabel'] ?? '—'),
                'tr' => (string) ($row['trendLabel'] ?? '—'),
                'rk' => (string) ($row['rarityKey'] ?? ''),
                'r' => (string) ($row['rarityLabel'] ?? ''),
                'mut' => array_values(array_map('strval', $row['mutationLabels'] ?? [])),
                'q' => (string) ($row['search'] ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildValueListRows(Collection $items, string $urlPrefix): array
    {
        $changesBySlug = collect(app(SabValueChangesService::class)->changes(7, null, 'recent', 1000))
            ->keyBy(fn (array $change): string => (string) ($change['itemSlug'] ?? ''));

        return $items
            ->map(function (SeoItem $item) use ($changesBySlug, $urlPrefix): array {
                $currentValue = self::valueListValueForItem($item);
                $change = $changesBySlug->get((string) $item->slug);
                $previousValue = is_numeric($change['beforeValue'] ?? null) ? (float) $change['beforeValue'] : null;
                $delta = ($previousValue !== null && $currentValue !== null)
                    ? round($currentValue - $previousValue, 4)
                    : null;
                $deltaPct = ($previousValue !== null && $previousValue != 0.0 && $delta !== null)
                    ? round(($delta / $previousValue) * 100, 1)
                    : null;

                $direction = 'stable';
                if ($delta !== null && $delta > 0) {
                    $direction = 'up';
                } elseif ($delta !== null && $delta < 0) {
                    $direction = 'down';
                }

                $baseVariant = $item->variants->firstWhere('variant_key', 'base')
                    ?? $item->variants->firstWhere('variant_type', 'base');
                $rot = data_get($item->attributes_json, 'rot_rocks', []);
                $baseValue = $baseVariant
                    ? $baseVariant->currentValues->first(fn ($cv) => ($cv->source?->slug) === SabRotCalculatorSyncService::SOURCE_SLUG)
                    : null;
                $demand = trim((string) (data_get($rot, 'demand') ?: $baseValue?->demand ?: ($change['demandAfter'] ?? $change['demandBefore'] ?? '')));
                $trend = trim((string) data_get($rot, 'trend'));
                $canOpenProduct = self::shouldLinkProduct($item);
                $productSlug = self::productPublicSlug($item->slug ?? '');
                $rarityKey = self::canonicalRarityKey($item->rarity ?? null);
                $rarityLabel = $rarityKey === ''
                    ? ''
                    : ($rarityKey === 'og' ? 'OG' : self::canonicalRarityLabel($rarityKey));
                $mutationLabels = $item->variants
                    ->filter(fn ($variant) => in_array((string) ($variant->variant_type ?? ''), ['base', 'mutation'], true))
                    ->map(function ($variant): string {
                        $type = (string) ($variant->variant_type ?? '');
                        if ($type === 'base') {
                            return 'Default';
                        }

                        return trim((string) ($variant->mutation_name ?: $variant->variant_name ?: ''));
                    })
                    ->filter()
                    ->unique(fn (string $label): string => strtolower($label))
                    ->values()
                    ->all();
                $variantSearch = $item->variants
                    ->map(fn ($variant) => trim(($variant->variant_name ?? '') . ' ' . ($variant->mutation_name ?? '') . ' ' . ($variant->trait_name ?? '')))
                    ->filter()
                    ->implode(' ');

                $searchBlob = implode(' ', array_filter([
                    $item->name,
                    $rarityKey,
                    $rarityLabel,
                    $item->rarity,
                    $item->summary,
                    $currentValue !== null ? (string) (int) round($currentValue) : null,
                    $currentValue !== null ? number_format((float) $currentValue, 0, '', '') : null,
                    $previousValue !== null ? (string) (int) round($previousValue) : null,
                    $previousValue !== null ? number_format((float) $previousValue, 0, '', '') : null,
                    $direction,
                    $demand,
                    $trend,
                    $variantSearch,
                    implode(' ', $mutationLabels),
                ], fn ($value) => trim((string) $value) !== ''));

                return [
                    'item' => $item,
                    'name' => $item->name,
                    'imageSrc' => self::listingImageSrc($item),
                    'canOpenProduct' => $canOpenProduct,
                    'productUrl' => $canOpenProduct ? rtrim($urlPrefix, '/') . '/products/' . $productSlug : null,
                    'currentValue' => $currentValue,
                    'currentValueLabel' => $currentValue !== null ? number_format($currentValue) : '—',
                    'previousValue' => $previousValue,
                    'previousValueLabel' => $previousValue !== null ? number_format($previousValue) : '—',
                    'direction' => $direction,
                    'directionLabel' => match ($direction) {
                        'up' => 'Up',
                        'down' => 'Down',
                        default => 'Stable',
                    },
                    'deltaLabel' => $this->formatValueListDeltaLabel($delta),
                    'deltaPctLabel' => $deltaPct === null ? '—' : (($deltaPct > 0 ? '+' : '') . $deltaPct . '%'),
                    'demandLabel' => $demand !== '' ? Str::title($demand) : '—',
                    'trendLabel' => $trend !== '' ? Str::title(strtolower(str_replace('_', ' ', $trend))) : '—',
                    'rarityKey' => $rarityKey,
                    'rarityLabel' => $rarityLabel,
                    'mutationLabels' => $mutationLabels,
                    'observedAgo' => ! empty($change['observedAt'] ?? null)
                        ? Carbon::parse((string) $change['observedAt'])->diffForHumans()
                        : null,
                    'search' => strtolower($searchBlob),
                ];
            })
            ->values()
            ->all();
    }

    private function formatValueListDeltaLabel(?float $delta): string
    {
        if ($delta === null || $delta == 0.0) {
            return '—';
        }

        return ($delta > 0 ? '+' : '') . rtrim(rtrim(number_format($delta, 2, '.', ''), '0'), '.');
    }

    /**
     * @param  array<string, mixed>  $t
     * @return list<array{question: string, answer: string}>
     */
    private function buildValueListFaqItems(array $t, string $urlPrefix, string $baseUrl, string $locale): array
    {
        $existCountUrl = $this->localePublicUrl($baseUrl, $locale, self::PAGE_EXIST_COUNTS_LIST);
        $calculatorUrl = $this->localePublicUrl($baseUrl, $locale, self::PAGE_TRADING_CALCULATOR);
        $existCountLabel = (string) ($t['value_list_faq_exist_count_link'] ?? 'Exist Count List');
        $calculatorLabel = (string) ($t['value_list_faq_calculator_link'] ?? 'SAB Trading Calculator');
        $existCountLink = '<a href="' . e($existCountUrl) . '" class="text-cyan-300 hover:text-cyan-200">' . e($existCountLabel) . '</a>';
        $calculatorLink = '<a href="' . e($calculatorUrl) . '" class="text-cyan-300 hover:text-cyan-200">' . e($calculatorLabel) . '</a>';

        $topItems = $this->topValueItemsForFaq($urlPrefix, 3);
        $topItemsHtml = $this->formatOxfordList(collect($topItems)->map(function (array $item): string {
            $link = '<a href="' . e($item['productUrl']) . '" class="text-cyan-300 hover:text-cyan-200">'
                . e($item['name']) . '</a>';

            return $link . ' (' . number_format($item['value']) . ' ROBUX)';
        })->all());

        $placeholders = [
            '{exist_count_link}' => $existCountLink,
            '{calculator_link}' => $calculatorLink,
            '{top_items}' => $topItemsHtml,
        ];

        $answer = static function (string $template) use ($placeholders): string {
            return str_replace(array_keys($placeholders), array_values($placeholders), $template);
        };

        return [
            [
                'question' => (string) ($t['value_list_faq_q1'] ?? ''),
                'answer' => $answer((string) ($t['value_list_faq_a1'] ?? '')),
            ],
            [
                'question' => (string) ($t['value_list_faq_q2'] ?? ''),
                'answer' => $topItems === []
                    ? (string) ($t['value_list_faq_a2_empty'] ?? '')
                    : $answer((string) ($t['value_list_faq_a2'] ?? '')),
            ],
            [
                'question' => (string) ($t['value_list_faq_q3'] ?? ''),
                'answer' => $answer((string) ($t['value_list_faq_a3'] ?? '')),
            ],
            [
                'question' => (string) ($t['value_list_faq_q4'] ?? ''),
                'answer' => $answer((string) ($t['value_list_faq_a4'] ?? '')),
            ],
            [
                'question' => (string) ($t['value_list_faq_q5'] ?? ''),
                'answer' => $answer((string) ($t['value_list_faq_a5'] ?? '')),
            ],
            [
                'question' => (string) ($t['value_list_faq_q6'] ?? ''),
                'answer' => $answer((string) ($t['value_list_faq_a6'] ?? '')),
            ],
            [
                'question' => (string) ($t['value_list_faq_q7'] ?? ''),
                'answer' => $answer((string) ($t['value_list_faq_a7'] ?? '')),
            ],
            [
                'question' => (string) ($t['value_list_faq_q8'] ?? ''),
                'answer' => $answer((string) ($t['value_list_faq_a8'] ?? '')),
            ],
        ];
    }

    /**
     * @param  array<int, array{question: string, answer: string}>  $faqItems
     */
    private function valueListPageJsonLd(string $title, string $desc, string $url, string $locale, array $faqItems): string
    {
        $blocks = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $title,
                'url' => $url,
                'description' => $desc,
                'dateModified' => date('Y-m-d'),
                'inLanguage' => $locale,
            ],
        ];

        if ($faqItems !== []) {
            $blocks[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn (array $item): array => [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($item['answer']),
                    ],
                ], $faqItems),
            ];
        }

        return implode("\n", array_map(
            fn (array $block): string => '<script type="application/ld+json">'
                . json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                . '</script>',
            $blocks
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function valueChangesViewPayload(
        string $urlPrefix,
        string $locale,
        string $baseUrl,
        array $t,
        int $days,
        ?string $direction,
        string $sort,
        string $cssHref,
    ): array {
        $service = app(SabValueChangesService::class);
        $clientViews = $this->buildValueChangesClientViews($urlPrefix);
        $default = $clientViews['default'];
        $directionKey = ($default['direction'] ?? null) === null ? 'all' : (string) $default['direction'];
        $view = $clientViews['views'][(string) $default['days']][$directionKey][$default['sort']];

        $changes = $view['changes'];
        $topGainers = $view['topGainers'];
        $topLosers = $view['topLosers'];
        $days = (int) $default['days'];
        $direction = $default['direction'];
        $sort = (string) $default['sort'];

        $latestAt = $service->latestObservedAt($days);
        $updatedAgoLabel = $latestAt?->diffForHumans() ?? 'not yet published';
        $seoTitle = $t['value_changes_meta_title'] ?? 'Steal a Brainrot Value Changes — Daily Price Updates & Trends';
        $seoDescription = $t['value_changes_meta_description'] ?? 'Track every Steal a Brainrot value change in real time. See which brainrots are rising, which are falling, and the biggest price swings — updated daily with historical trends.';
        $pageUrl = $this->localePublicUrl($baseUrl, $locale, self::PAGE_VALUE_CHANGES);
        $faqTopGainers = $clientViews['views']['7']['all']['recent']['topGainers'] ?? $topGainers;
        $faqItems = $this->buildValueChangesFaqItems($clientViews['views']['7']['all']['recent']['changes'] ?? $changes, $faqTopGainers, $updatedAgoLabel, $urlPrefix);

        return [
            'locale' => $locale,
            'urlPrefix' => $urlPrefix,
            'productUrlPrefix' => $this->productUrlPrefix($urlPrefix),
            'baseUrl' => $baseUrl,
            't' => $t,
            'hreflangLinks' => [],
            'canonical' => $pageUrl,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'days' => $days,
            'direction' => $direction,
            'sort' => $sort,
            'changes' => $changes,
            'topLosers' => $topLosers,
            'topGainers' => $topGainers,
            'changeCount' => $view['changeCount'],
            'updatedLabel' => $latestAt?->format('M j, Y g:i A') ?? 'Not yet published',
            'updatedAgoLabel' => $updatedAgoLabel,
            'valueChangesFaqItems' => $faqItems,
            'valueChangesClientViews' => $clientViews,
            'websiteJsonLd' => $this->websiteJsonLd($baseUrl, $seoDescription),
            'jsonLd' => $this->valueChangesPageJsonLd($seoTitle, $seoDescription, $pageUrl, $locale, $faqItems),
            'cssHref' => $cssHref,
        ];
    }

    /**
     * @return array{
     *   default: array{days: int, direction: ?string, sort: string},
     *   views: array<string, array<string, array<string, array<string, mixed>>>>
     * }
     */
    private function buildValueChangesClientViews(string $urlPrefix): array
    {
        $service = app(SabValueChangesService::class);
        $views = [];
        $topMoversByDays = [];

        foreach (SabValueChangesService::ALLOWED_DAY_WINDOWS as $days) {
            $days = $service->normalizeDays($days);
            $daysKey = (string) $days;
            [$topGainers, $topLosers] = $service->topMoverSummaries($days, 10);
            $topMoversByDays[$daysKey] = [
                'topGainers' => $this->enrichValueChangeRows($topGainers, $urlPrefix),
                'topLosers' => $this->enrichValueChangeRows($topLosers, $urlPrefix),
            ];
        }

        foreach (SabValueChangesService::ALLOWED_DAY_WINDOWS as $days) {
            $days = $service->normalizeDays($days);
            $daysKey = (string) $days;
            $views[$daysKey] = [];

            foreach ([null, 'up', 'down'] as $direction) {
                $directionKey = $direction === null ? 'all' : $direction;
                $views[$daysKey][$directionKey] = [];

                foreach (['recent', 'biggest'] as $sort) {
                    $changes = $this->enrichValueChangeRows(
                        $service->changes($days, $direction, $sort),
                        $urlPrefix,
                    );

                    $views[$daysKey][$directionKey][$sort] = [
                        'changes' => $changes,
                        'topGainers' => $topMoversByDays[$daysKey]['topGainers'],
                        'topLosers' => $topMoversByDays[$daysKey]['topLosers'],
                        'changeCount' => count($changes),
                        'days' => $days,
                    ];
                }
            }
        }

        return [
            'default' => ['days' => 7, 'direction' => null, 'sort' => 'recent'],
            'views' => $views,
        ];
    }

    /**
     * @return list<array{name: string, slug: string, value: float, productUrl: string}>
     */
    private function topValueItemsForFaq(string $urlPrefix, int $limit = 3): array
    {
        $site = SeoSite::query()->where('slug', self::SITE_SLUG)->first();
        if ($site === null) {
            return [];
        }

        $game = SeoGame::query()
            ->where('seo_site_id', $site->id)
            ->where('slug', SabValueChangesService::GAME_SLUG)
            ->first();
        if ($game === null) {
            return [];
        }

        return DB::table('seo_item_current_values as cv')
            ->join('seo_item_variants as variant', 'variant.id', '=', 'cv.seo_item_variant_id')
            ->join('seo_items as item', 'item.id', '=', 'variant.seo_item_id')
            ->join('seo_value_sources as source', 'source.id', '=', 'cv.seo_value_source_id')
            ->where('item.seo_game_id', $game->id)
            ->where('item.is_listed', true)
            ->where('variant.variant_key', 'base')
            ->where('source.slug', SabRotCalculatorSyncService::SOURCE_SLUG)
            ->whereNotNull('cv.value_normalized')
            ->orderByDesc('cv.value_normalized')
            ->limit(max(1, $limit))
            ->get([
                'item.slug as item_slug',
                'item.name as item_name',
                'item.display_name as item_display_name',
                'cv.value_normalized',
            ])
            ->map(function (object $row) use ($urlPrefix): array {
                $slug = self::productPublicSlug((string) $row->item_slug);
                $name = trim((string) ($row->item_display_name ?? '')) ?: (string) $row->item_name;

                return [
                    'name' => $name,
                    'slug' => (string) $row->item_slug,
                    'value' => (float) $row->value_normalized,
                    'productUrl' => rtrim($urlPrefix, '/') . '/products/' . $slug,
                ];
            })
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $changes
     * @param  list<array<string, mixed>>  $topGainers
     * @return list<array{question: string, answer: string}>
     */
    private function buildValueChangesFaqItems(
        array $changes,
        array $topGainers,
        string $updatedAgoLabel,
        string $urlPrefix,
    ): array {
        $todayCount = collect($changes)
            ->filter(function (array $change): bool {
                if (empty($change['observedAt'])) {
                    return false;
                }

                return Carbon::parse((string) $change['observedAt'])->isToday();
            })
            ->count();

        $todayAnswer = $todayCount > 0
            ? 'Yes — <strong>' . number_format($todayCount) . '</strong> value '
                . ($todayCount === 1 ? 'change was' : 'changes were')
                . ' recorded today. Check the list above for the latest before-and-after values, demand shifts, and percentage moves.'
            : 'No confirmed Steal a Brainrot value changes have been recorded yet today. Use the 7, 14, or 30 day filters above to review recent movement from earlier this week.';

        $topValueItems = $this->topValueItemsForFaq($urlPrefix, 3);
        $topValueAnswer = $topValueItems === []
            ? 'The highest-value brainrots change frequently based on market conditions. Open the list above or check individual product pages for the latest ROBUX estimates.'
            : 'The highest-value brainrots change frequently based on market conditions. Currently, '
                . $this->formatOxfordList(collect($topValueItems)->map(function (array $item): string {
                    $link = '<a href="' . e($item['productUrl']) . '" class="text-cyan-300 hover:text-cyan-200">'
                        . e($item['name']) . '</a>';

                    return $link . ' (' . number_format($item['value']) . ' ROBUX)';
                })->all())
                . ' rank among the top tracked values. Demand and mutations can shift rankings quickly, so confirm the latest number before you trade.';

        $frequencyAnswer = 'Values can fluctuate multiple times per day as community estimates and demand signals update. '
            . 'This page refreshes from rot.rocks calculator observations — last synced <strong>' . e($updatedAgoLabel) . '</strong>. '
            . 'Use the change list above to spot fresh moves instead of relying on stale screenshots.';

        $gainerLinks = collect($topGainers)
            ->take(5)
            ->map(function (array $change): string {
                $name = trim((string) ($change['itemName'] ?? ''));
                if ($name === '') {
                    return '';
                }
                $url = (string) ($change['productUrl'] ?? '#');

                return '<a href="' . e($url) . '" class="text-cyan-300 hover:text-cyan-200">' . e($name) . '</a>';
            })
            ->filter()
            ->values()
            ->all();

        $tradeAnswer = $gainerLinks === []
            ? 'Brainrots showing upward momentum are listed in the Top Gainers section above. Sort by Biggest or Recent to see which items moved the most in the last 7 days.'
            : 'Brainrots showing upward trends in the last 7 days include '
                . $this->formatOxfordList($gainerLinks)
                . '. Rising value alone does not guarantee a win — compare both sides in the SAB trade calculator before you accept.';

        return [
            [
                'question' => 'Is there any update in Steal a Brainrot today?',
                'answer' => $todayAnswer,
            ],
            [
                'question' => 'What brainrot gives you the most money?',
                'answer' => $topValueAnswer,
            ],
            [
                'question' => 'How often do Steal a Brainrot values change?',
                'answer' => $frequencyAnswer,
            ],
            [
                'question' => 'What brainrots are worth trading right now?',
                'answer' => $tradeAnswer,
            ],
        ];
    }

    /**
     * @param  list<string>  $items
     */
    private function formatOxfordList(array $items): string
    {
        $count = count($items);
        if ($count === 0) {
            return '';
        }
        if ($count === 1) {
            return $items[0];
        }
        if ($count === 2) {
            return $items[0] . ' and ' . $items[1];
        }

        $last = array_pop($items);

        return implode(', ', $items) . ', and ' . $last;
    }

    /**
     * @param  array<int, array{question: string, answer: string}>  $faqItems
     */
    private function valueChangesPageJsonLd(string $title, string $desc, string $url, string $locale, array $faqItems): string
    {
        $blocks = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $title,
                'url' => $url,
                'description' => $desc,
                'dateModified' => date('Y-m-d'),
                'inLanguage' => $locale,
            ],
        ];

        if ($faqItems !== []) {
            $blocks[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn (array $item): array => [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($item['answer']),
                    ],
                ], $faqItems),
            ];
        }

        return implode("\n", array_map(
            fn (array $block): string => '<script type="application/ld+json">'
                . json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                . '</script>',
            $blocks
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function enrichValueChangeRows(array $rows, string $urlPrefix): array
    {
        if ($rows === []) {
            return [];
        }

        $slugs = collect($rows)
            ->pluck('itemSlug')
            ->filter()
            ->map(fn ($slug) => (string) $slug)
            ->unique()
            ->values()
            ->all();

        $itemsBySlug = SeoItem::query()
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug');

        return collect($rows)
            ->map(function (array $change) use ($urlPrefix, $itemsBySlug): array {
                $slug = self::productPublicSlug((string) ($change['itemSlug'] ?? ''));
                $item = $itemsBySlug->get((string) ($change['itemSlug'] ?? ''));
                $demand = trim((string) ($change['demandAfter'] ?? ''));
                if ($demand === '') {
                    $demand = trim((string) ($change['demandBefore'] ?? ''));
                }
                $observedAt = ! empty($change['observedAt'])
                    ? Carbon::parse((string) $change['observedAt'])
                    : null;

                return $change + [
                    'productUrl' => rtrim($urlPrefix, '/') . '/products/' . $slug,
                    'imageSrc' => $item ? self::listingImageSrc($item) : null,
                    'observedAgo' => $observedAt?->diffForHumans() ?? '—',
                    'demandLabel' => $demand !== '' ? Str::title($demand) : '—',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function calculatorViewPayload(
        string $urlPrefix,
        string $locale,
        string $baseUrl,
        array $t,
        ?Collection $allItems,
        string $cssHref,
        ?SeoSite $site = null,
        ?SabSiteContext $ctx = null,
        bool $includeTodaySummary = true,
    ): array
    {
        $ctx ??= new SabSiteContext($site?->slug ?? self::SITE_SLUG);
        $site ??= $ctx->resolve();
        $calculatorMessages = array_replace_recursive($t, $this->calculatorCopy($locale));
        $defaultCopy = self::defaultCalculatorCopy();
        $copy = $ctx->isCalculatorOnly($site)
            ? $ctx->calculatorCopy($site)
            : [
                'meta_title' => $calculatorMessages['calculator_meta_title'] ?? $defaultCopy['meta_title'],
                'meta_description' => $calculatorMessages['calculator_meta_description'] ?? $defaultCopy['meta_description'],
                'h1' => $calculatorMessages['calculator_h1'] ?? $defaultCopy['h1'],
                'intro' => $calculatorMessages['calculator_intro'] ?? $defaultCopy['intro'],
            ];
        $seoTitle = $copy['meta_title'] ?: self::defaultCalculatorCopy()['meta_title'];
        $seoDescription = $copy['meta_description'] ?: self::defaultCalculatorCopy()['meta_description'];
        $pageUrl = $ctx->isCalculatorOnly($site)
            ? rtrim($baseUrl, '/') . '/'
            : $this->localePublicUrl($baseUrl, $locale, self::PAGE_TRADING_CALCULATOR);
        $isCalculatorOnly = $ctx->isCalculatorOnly($site);
        $faqItems = $isCalculatorOnly ? [] : $this->calculatorFaqItems(false, $calculatorMessages);
        $brand = $ctx->brand($site);
        $lastUpdate = $this->calculatorLastUpdatePayload($locale);
        $productUrlPrefix = $this->productUrlPrefix($urlPrefix);
        $codesVerified = Carbon::createFromFormat('!Y-m-d', (string) $this->codesData()['verified_at']);
        $codesMonthLabel = $this->localizedMonthLabel($codesVerified, $locale);
        $faqHref = $isCalculatorOnly ? self::calculatorStaticPageHref($urlPrefix, 'faq') : null;
        $todayTopGainers = [];
        $todayTopLosers = [];
        if (! $isCalculatorOnly && $includeTodaySummary) {
            [$todayTopGainers, $todayTopLosers] = app(SabValueChangesService::class)
                ->topMoverSummaries(1, 5);
            $todayTopGainers = $this->enrichValueChangeRows($todayTopGainers, $productUrlPrefix);
            $todayTopLosers = $this->enrichValueChangeRows($todayTopLosers, $productUrlPrefix);
        }

        return [
            'locale' => $locale,
            'urlPrefix' => $urlPrefix,
            'productUrlPrefix' => $productUrlPrefix,
            'baseUrl' => $baseUrl,
            't' => $calculatorMessages,
            'hreflangLinks' => $ctx->isCalculatorOnly($site) ? [] : $this->hreflangLinks(self::PAGE_TRADING_CALCULATOR, $baseUrl, self::MULTILINGUAL_PAGE_LOCALES),
            'languageLinks' => $ctx->isCalculatorOnly($site) ? [] : $this->languageLinks($urlPrefix, $locale, self::PAGE_TRADING_CALCULATOR),
            'canonical' => $pageUrl,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'calculatorCopy' => $copy,
            'calculatorUi' => $this->calculatorUiTranslations($calculatorMessages),
            'calculatorOnly' => $isCalculatorOnly,
            'faqHref' => $faqHref,
            'valueChangesHref' => $isCalculatorOnly
                ? null
                : rtrim($productUrlPrefix, '/')
                    . '/' . self::PAGE_VALUE_CHANGES,
            'codesHref' => $isCalculatorOnly
                ? null
                : rtrim($urlPrefix, '/')
                    . '/' . self::PAGE_CODES,
            'codesNoticeLabel' => strtr(
                (string) $calculatorMessages['codes_notice_template'],
                ['{month}' => $codesMonthLabel]
            ),
            'lastUpdateLabel' => $calculatorMessages['last_update_label'],
            'valueTrendsLabel' => $calculatorMessages['value_trends_label'],
            'valuesTipLabel' => (string) ($calculatorMessages['calculator_values_tip']
                ?? 'SAB Values is live — see what moved today'),
            'todayTopGainers' => $todayTopGainers,
            'todayTopLosers' => $todayTopLosers,
            'calculatorLastUpdatedAt' => $lastUpdate['at'],
            'calculatorLastUpdatedLabel' => $lastUpdate['label'],
            'siteSlug' => $ctx->siteSlug(),
            'brand' => $brand,
            'calculatorData' => $allItems !== null ? $this->calculatorData($allItems, $site) : null,
            'calculatorCatalogManifestUrl' => SabCalculatorCatalogService::publicManifestUrl(),
            'popularTradeItems' => $allItems !== null
                ? $this->popularTradeItems($allItems)
                : ($isCalculatorOnly || ! $includeTodaySummary
                    ? collect()
                    : $this->popularTradeItemsFromDatabase($ctx, $site)),
            'calculatorFaqItems' => $faqItems,
            'websiteJsonLd' => $this->websiteJsonLd($baseUrl, $seoDescription),
            'jsonLd' => $this->calculatorPageJsonLd($seoTitle, $seoDescription, $pageUrl, $locale, $faqItems),
            'cssHref' => $cssHref,
        ];
    }

    /**
     * @return array{at: ?string, label: ?string}
     */
    private function calculatorLastUpdatePayload(string $locale): array
    {
        $path = SabCalculatorCatalogService::metaReadPath();
        if (! is_readable($path)) {
            return ['at' => null, 'label' => null];
        }

        $meta = json_decode((string) file_get_contents($path), true);
        $syncedAt = is_array($meta) ? trim((string) ($meta['synced_at'] ?? '')) : '';
        if ($syncedAt === '') {
            return ['at' => null, 'label' => null];
        }

        try {
            $date = Carbon::parse($syncedAt)->setTimezone('America/Los_Angeles');
        } catch (\Throwable) {
            return ['at' => null, 'label' => null];
        }

        return [
            'at' => $date->toIso8601String(),
            'label' => $date->locale($this->carbonLocale($locale))->isoFormat('ll LT'),
        ];
    }

    private function carbonLocale(string $locale): string
    {
        return self::normalizeLocale($locale) === 'pt' ? 'pt_BR' : self::normalizeLocale($locale);
    }

    private function localizedMonthLabel(Carbon $date, string $locale): string
    {
        $locale = self::normalizeLocale($locale);
        $monthFormat = in_array($locale, ['pt', 'es'], true) ? 'MMMM [de] YYYY' : 'MMMM YYYY';

        return $date->copy()->locale($this->carbonLocale($locale))->isoFormat($monthFormat);
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    private function calculatorUiTranslations(array $t): array
    {
        return [
            'offerTitle' => $t['calculator_offer_title'] ?? 'Your Offer',
            'receiveTitle' => $t['calculator_receive_title'] ?? 'You Receive',
            'countSingular' => $t['calculator_count_singular'] ?? 'item',
            'countPlural' => $t['calculator_count_plural'] ?? 'items',
            'swapTitle' => $t['calculator_swap_title'] ?? 'Swap sides',
            'compareEmpty' => $t['calculator_compare_empty'] ?? 'Add items to compare trades',
            'compareBothEmpty' => $t['calculator_compare_both_empty'] ?? 'Add items to both sides to check W/F/L.',
            'compareNeedReceive' => $t['calculator_compare_need_receive'] ?? 'Add items to You Receive to compare the trade.',
            'compareNeedOffer' => $t['calculator_compare_need_offer'] ?? 'Add items to Your Offer to compare the trade.',
            'clearAll' => $t['calculator_clear_all'] ?? 'Clear All',
            'helpTitle' => $t['calculator_help_title'] ?? 'How values are calculated',
            'helpSubtitle' => $t['calculator_help_subtitle'] ?? 'Formulas and trait bonuses',
            'incomeCheckTitle' => $t['calculator_income_check_title'] ?? 'Income Formula',
            'incomeFormula' => $t['calculator_income_formula'] ?? 'Base × (Mutation + Traits)',
            'incomeCheckBody' => $t['calculator_income_check_body'] ?? '',
            'valueCheckTitle' => $t['calculator_value_check_title'] ?? 'Value Formula',
            'valueFormula' => $t['calculator_value_formula'] ?? 'Mutation Value × (1 + Trait Bonuses × Streak)',
            'valueCheckBody' => $t['calculator_value_check_body'] ?? '',
            'traitValueBonuses' => $t['calculator_trait_value_bonuses'] ?? 'Trait Value Bonuses',
            'traitBonusEmpty' => $t['calculator_trait_bonus_empty'] ?? 'Trait bonus data will appear after calculator sync.',
            'selectBrainrot' => $t['calculator_select_brainrot'] ?? 'Select Brainrot',
            'change' => $t['calculator_change'] ?? 'Change',
            'searchBrainrots' => $t['calculator_search_brainrots'] ?? 'Search brainrots...',
            'mutation' => $t['calculator_mutation'] ?? 'Mutation',
            'traits' => $t['calculator_traits'] ?? 'Traits',
            'selectedLabel' => str_replace('{count} ', '', $t['calculator_traits_selected'] ?? '{count} selected'),
            'searchTraits' => $t['calculator_search_traits'] ?? 'Search traits...',
            'calculatedIncome' => $t['calculator_calculated_income'] ?? 'Calculated Income',
            'recalculate' => $t['calculator_recalculate'] ?? 'Recalculate',
            'addItem' => $t['calculator_add_item'] ?? 'Add Item',
            'updateItem' => $t['calculator_update_item'] ?? 'Update Item',
            'totalIncome' => $t['calculator_total_income'] ?? 'Total Income',
            'totalValue' => $t['calculator_total_value'] ?? 'Total Value',
            'incomeLabel' => $t['calculator_income_label'] ?? 'Income',
            'valueLabel' => $t['calculator_value_label'] ?? 'Value',
            'baseLabel' => $t['calculator_base_label'] ?? 'Base',
            'totalMultiplier' => $t['calculator_total_multiplier'] ?? 'Total Multiplier',
            'defaultMutation' => $t['calculator_default_mutation'] ?? 'Default',
            'noBrainrotsFound' => $t['calculator_no_brainrots_found'] ?? 'No matching Brainrots found.',
            'fairTrade' => $t['calculator_fair_trade'] ?? 'Fair trade',
            'winTrade' => $t['calculator_win_trade'] ?? 'Win trade',
            'loseTrade' => $t['calculator_lose_trade'] ?? 'Lose trade',
            'seoTitle' => $t['calculator_seo_title'] ?? 'How to Use the SAB Calculator',
            'seoSteps' => is_array($t['calculator_seo_steps'] ?? null) ? $t['calculator_seo_steps'] : [],
            'valuesTitle' => $t['calculator_values_title'] ?? 'What Are SAB Trading Values?',
            'valuesBody' => $t['calculator_values_body'] ?? '',
            'wflTitle' => $t['calculator_wfl_title'] ?? 'How to Check W/F/L in Steal a Brainrot',
            'wflBody' => $t['calculator_wfl_body'] ?? '',
            'mutationsTitle' => $t['calculator_mutations_title'] ?? 'How Mutations and Traits Affect SAB Trade Value',
            'mutationsBody' => $t['calculator_mutations_body'] ?? '',
            'existCountTitle' => $t['calculator_exist_count_title'] ?? 'Why Exist Count Matters in SAB Trading',
            'existCountBody' => $t['calculator_exist_count_body'] ?? '',
            'existCountLink' => $t['calculator_exist_count_link'] ?? 'View Steal a Brainrot Exist Count List',
            'valueListLink' => $t['calculator_value_list_link'] ?? 'View Steal a Brainrot Value List',
            'tipsTitle' => $t['calculator_tips_title'] ?? 'SAB Trading Tips',
            'tips' => is_array($t['calculator_tips'] ?? null) ? $t['calculator_tips'] : [],
            'popularTitle' => $t['calculator_popular_title'] ?? 'Popular Brainrots to Check Before Trading',
            'popularBody' => $t['calculator_popular_body'] ?? '',
            'faqTitle' => $t['calculator_faq_h2'] ?? 'SAB Calculator FAQ',
            'bonusStackNote' => $t['calculator_bonus_stack_note'] ?? 'Bonuses stack additively.',
            'configureItemLabel' => $t['calculator_configure_item_label'] ?? 'Configure item',
            'addOfferItem' => $t['calculator_add_offer_item'] ?? 'Add offer item',
            'addReceiveItem' => $t['calculator_add_receive_item'] ?? 'Add receive item',
            'wflCheckLabel' => $t['calculator_wfl_check_label'] ?? 'W/F/L Check',
            'traitMultiplierLabel' => $t['calculator_trait_multiplier_label'] ?? 'trait',
            'mutationMultiplierLabel' => $t['calculator_mutation_multiplier_label'] ?? 'mutation',
            'traitsSelectedCount' => $t['calculator_traits_selected_count'] ?? '{count}+ traits selected',
            'valueBonus' => $t['calculator_value_bonus'] ?? '{multiplier}x value bonus',
        ];
    }

    private function calculatorFaqItems(bool $calculatorOnly = false, array $t = []): array
    {
        if (! $calculatorOnly && is_array($t['calculator_faq_items'] ?? null)) {
            return array_values(array_filter(array_map(function ($row): ?array {
                if (! is_array($row)) {
                    return null;
                }

                $question = (string) ($row['question'] ?? $row[0] ?? '');
                $answer = (string) ($row['answer'] ?? $row[1] ?? '');

                return $question !== '' && $answer !== ''
                    ? ['question' => $question, 'answer' => $answer]
                    : null;
            }, $t['calculator_faq_items'])));
        }

        if ($calculatorOnly) {
            return [
                [
                    'question' => 'What is this trade calculator for?',
                    'answer' => 'It is a Steal a Brainrot trade checker that compares the Brainrots on both sides of a trade before you accept.',
                ],
                [
                    'question' => 'How do I check a WFL result?',
                    'answer' => 'Add your offer, add what you would receive, choose the correct mutations and traits, then compare the value and income totals.',
                ],
                [
                    'question' => 'Do mutations and traits matter?',
                    'answer' => 'Yes. Mutations and traits can change income and trade value, so the same Brainrot name can produce a different result with a different setup.',
                ],
                [
                    'question' => 'Should I compare value or income?',
                    'answer' => 'Check both. Value is useful for trade balance, while income helps you understand how much each side can earn over time.',
                ],
                [
                    'question' => 'Are these trade values official?',
                    'answer' => 'No. The calculator gives a reference for checking trades, but it is not an official price list or a guarantee of market demand.',
                ],
                [
                    'question' => 'Is the calculator free to use?',
                    'answer' => 'Yes. The calculator is free to use for checking Steal a Brainrot trades.',
                ],
            ];
        }

        return [
            [
                'question' => 'What is a SAB trading values calculator?',
                'answer' => 'It is a free Steal a Brainrot trading calculator that compares SAB trading values on both sides of a trade, including income, mutations, traits and the WFL result, before you accept.',
            ],
            [
                'question' => 'How do I check brainrot trade value before trading?',
                'answer' => 'Add the Brainrots on both sides of the trade, choose the correct mutation and traits, then compare total value, income and the WFL result.',
            ],
            [
                'question' => 'What does WFL mean in SAB trading?',
                'answer' => 'WFL means Win, Fair or Lose. It helps players understand whether the Brainrots they receive are worth more than, close to, or lower than the Brainrots they offer.',
            ],
            [
                'question' => 'Do mutations and traits change trade value?',
                'answer' => 'Yes. Mutations and traits can change income and estimated trade value, so two Brainrots with the same name may have different results.',
            ],
            [
                'question' => 'Are SAB trading values official?',
                'answer' => 'No. SAB trading values are player-to-player references, not official prices. Use them as a guide and recheck demand, exist count, mutations and traits before trading.',
            ],
            [
                'question' => 'Is this Steal a Brainrot trading calculator free?',
                'answer' => 'Yes. The SABExistCount trading calculator is free to use and helps players compare SAB trading values before accepting a trade.',
            ],
        ];
    }

    private function popularTradeItems(Collection $items): Collection
    {
        $preferred = [
            'strawberry-elephant',
            'dragon-cannelloni',
            'headless-horseman',
            'garama-and-madundung',
        ];

        return $items
            ->filter(fn (SeoItem $item) => in_array($item->slug, $preferred, true))
            ->sortBy(fn (SeoItem $item) => array_search($item->slug, $preferred, true))
            ->values();
    }

    private function popularTradeItemsFromDatabase(SabSiteContext $ctx, SeoSite $site): Collection
    {
        $preferred = [
            'strawberry-elephant',
            'dragon-cannelloni',
            'headless-horseman',
            'garama-and-madundung',
        ];
        $game = $ctx->dataGame($site);
        $items = SeoItem::query()
            ->where('seo_game_id', $game->id)
            ->whereIn('slug', $preferred)
            ->get(['id', 'slug', 'name', 'display_name', 'is_publish_html']);

        return collect($preferred)
            ->map(fn (string $slug) => $items->firstWhere('slug', $slug))
            ->filter(fn ($item): bool => $item instanceof SeoItem)
            ->filter(fn (SeoItem $item): bool => self::shouldLinkProduct($item))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * Return the calculator catalog entry for one item, or null when it is not
     * selectable on the trading calculator (baseIncome must be > 0).
     *
     * @return array<string, mixed>|null
     */
    public function calculatorBrainrotForItem(SeoItem $item, ?SeoSite $site = null): ?array
    {
        $brainrots = $this->calculatorData(collect([$item]), $site)['brainrots'] ?? [];

        return is_array($brainrots[0] ?? null) ? $brainrots[0] : null;
    }

    public function calculatorData(Collection $items, ?SeoSite $site = null): array
    {
        // Global traits and streak multipliers come from meta JSON (not per-item DB variants).
        $meta = $this->loadCalculatorMeta();
        $globalTraits = $meta['traits'] ?? [];
        $streakMultipliers = $meta['streakMultipliers']
            ?? data_get($site?->settings_json, 'sab_calculator.streak_multipliers', ['3' => 2, '6' => 3]);
        $newSince = now()->subDays(14);

        $brainrots = $this->sortItemsBySortOrderThenName($items)
            ->map(function (SeoItem $item) use ($globalTraits, $newSince): array {
                $rot = data_get($item->attributes_json, 'rot_rocks', []);
                $baseVariant = $item->variants->firstWhere('variant_key', 'base')
                    ?? $item->variants->firstWhere('variant_type', 'base');
                $baseValue = $baseVariant
                    ? $baseVariant->currentValues->first(fn ($cv) => ($cv->source?->slug) === SabRotCalculatorSyncService::SOURCE_SLUG)?->value_normalized
                    : null;

                $mutationRows = $this->rotRocksCatalogMutationVariants($item->variants)
                    ->map(function (SeoItemVariant $variant) use ($newSince): array {
                        $value = $variant->currentValues->first(fn ($cv) => ($cv->source?->slug) === SabRotCalculatorSyncService::SOURCE_SLUG);

                        return [
                            'id' => $variant->variant_key,
                            'name' => $variant->mutation_name ?: $variant->variant_name,
                            'multiplier' => (float) ($variant->multiplier ?? 1),
                            'image' => $this->calculatorImageForVariant($variant, 'mutations'),
                            'isNew' => \Carbon\Carbon::parse(
                                data_get($variant->attributes_json, 'first_seen_at') ?? $variant->created_at
                            )->gte($newSince),
                            'robuxValue' => $value?->value_normalized !== null ? (float) $value->value_normalized : null,
                            'demand' => data_get($variant->attributes_json, 'demand') ?: $value?->demand,
                            'trend' => data_get($variant->attributes_json, 'trend'),
                        ];
                    })
                    ->sortBy('name')
                    ->values();

                return [
                    'id' => (string) $item->id,
                    'slug' => $item->slug,
                    'name' => data_get($rot, 'name') ?: $item->name,
                    'image' => $this->calculatorImageForItem($item),
                    'baseIncome' => $this->calculatorBaseIncomeForItem($item),
                    'rarity' => data_get($rot, 'rarity') ?: $item->rarity,
                    'isNew' => \Carbon\Carbon::parse(
                        data_get($item->attributes_json, 'rot_rocks.first_seen_at') ?? $item->created_at
                    )->gte($newSince),
                    'robuxValue' => $baseValue !== null ? (float) $baseValue : data_get($rot, 'robux_value'),
                    'robuxCost' => data_get($rot, 'base_cost') !== null ? (float) data_get($rot, 'base_cost') : null,
                    'demand' => data_get($rot, 'demand'),
                    'trend' => data_get($rot, 'trend'),
                    'mutations' => $mutationRows->prepend([
                        'id' => 'base',
                        'name' => 'Default',
                        'multiplier' => 1,
                        'image' => null,
                        'robuxValue' => $baseValue !== null ? (float) $baseValue : data_get($rot, 'robux_value'),
                        'demand' => data_get($rot, 'demand'),
                        'trend' => data_get($rot, 'trend'),
                    ])->unique('name')->values()->all(),
                ];
            })
            ->filter(fn (array $item): bool => $item['baseIncome'] > 0)
            ->values()
            ->all();

        return [
            'brainrots' => $brainrots,
            'traits' => $globalTraits,
            'streakMultipliers' => $streakMultipliers,
        ];
    }

    private function calculatorBaseIncomeForItem(SeoItem $item): float
    {
        $rot = data_get($item->attributes_json, 'rot_rocks', []);

        return (float) (data_get($rot, 'base_income') ?? preg_replace('/[^0-9.]/', '', (string) $item->avg_coins_raw));
    }

    /**
     * Keep only rot.rocks catalog mutations (Gold, Rainbow, …).
     * Excludes trait rows mis-tagged as mutation, mutation+trait combos, and duplicate Default rows.
     *
     * @param  Collection<int, SeoItemVariant>  $variants
     * @return Collection<int, SeoItemVariant>
     */
    private function rotRocksCatalogMutationVariants(Collection $variants): Collection
    {
        return $variants
            ->where('variant_type', 'mutation')
            ->filter(function (SeoItemVariant $variant): bool {
                if (trim((string) data_get($variant->attributes_json, 'rot_id')) === '') {
                    return false;
                }

                if ((string) $variant->variant_key === 'mutation-default') {
                    return false;
                }

                if (trim((string) ($variant->trait_name ?? '')) !== '') {
                    return false;
                }

                if (str_ends_with((string) $variant->variant_key, '-1-trait')) {
                    return false;
                }

                $name = trim((string) ($variant->mutation_name ?: $variant->variant_name));
                if ($name !== '' && preg_match('/\b\d+\s+Trait$/i', $name)) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    private function calculatorImageForItem(SeoItem $item): ?string
    {
        $saved = data_get($item->attributes_json, 'rot_rocks.calculator_image_url');
        if (is_string($saved) && trim($saved) !== '') {
            return $saved;
        }

        $source = data_get($item->attributes_json, 'rot_rocks.source_image_url');
        $local = $this->calculatorImagePathFromSource((string) $source, 'brainrots', $item->slug);
        if ($local !== null) {
            return $local;
        }

        return $item->local_image_url ? '/' . ltrim((string) $item->local_image_url, '/') : ($item->image_url ?: null);
    }

    private function calculatorImageForVariant(SeoItemVariant $variant, string $type): ?string
    {
        $saved = data_get($variant->attributes_json, 'calculator_image_url');
        if (is_string($saved) && trim($saved) !== '') {
            return $saved;
        }

        return $this->calculatorImagePathFromSource(
            (string) data_get($variant->attributes_json, 'source_image_url'),
            $type,
            Str::slug($variant->variant_name ?: $variant->mutation_name ?: $variant->trait_name)
        );
    }

    private function calculatorImagePathFromSource(string $sourceUrl, string $type, string $slug): ?string
    {
        if ($sourceUrl === '' || $slug === '') {
            return null;
        }

        $path = parse_url($sourceUrl, PHP_URL_PATH);
        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['webp', 'png', 'jpg', 'jpeg', 'gif'], true)) {
            $extension = 'png';
        }

        $relative = "uploads/images/sab/calculator/{$type}/{$slug}.{$extension}";

        return is_file(public_path($relative)) ? '/' . $relative : null;
    }

    /**
     * @param array<string, mixed> $galleryManifest
     * @return array<string, mixed>
     */
    private function existCountGalleryViewPayload(string $urlPrefix, string $locale, string $baseUrl, array $t, array $galleryManifest, string $cssHref): array
    {
        $rawItems = $galleryManifest['items'] ?? [];
        $items = collect(is_array($rawItems) ? $rawItems : [])
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['name'] ?? '')) !== '')
            ->map(function (array $item) {
                $name = trim((string) ($item['name'] ?? ''));
                $rarity = trim((string) ($item['rarity'] ?? ''));
                $localImageUrl = trim((string) ($item['local_image_url'] ?? ''));
                $remoteImageUrl = trim((string) ($item['remote_image_url'] ?? ''));

                return [
                    'name'             => $name,
                    'rarity'           => $rarity,
                    'rarity_key'       => $this->galleryRarityKey($rarity),
                    'local_image_url'  => $localImageUrl,
                    'remote_image_url' => $remoteImageUrl,
                    'source_page_url'  => trim((string) ($item['source_page_url'] ?? '')),
                    'download_error'   => trim((string) ($item['download_error'] ?? '')),
                ];
            })
            ->values();

        $rarities = $items
            ->pluck('rarity')
            ->filter()
            ->unique()
            ->values()
            ->map(fn ($rarity) => [
                'label' => $rarity,
                'key'   => $this->galleryRarityKey((string) $rarity),
                'count' => $items->where('rarity', $rarity)->count(),
            ])
            ->all();

        $seoTitle = $t['exist_count_gallery_meta_title'];
        $seoDescription = $t['exist_count_gallery_meta_description'];
        $pageUrl = $this->localePublicUrl($baseUrl, $locale, self::PAGE_EXIST_COUNT_GALLERY);

        return [
            'locale'           => $locale,
            'urlPrefix'        => $urlPrefix,
            'baseUrl'          => $baseUrl,
            't'                => $t,
            'hreflangLinks'    => [],
            'canonical'        => $pageUrl,
            'seoTitle'         => $seoTitle,
            'seoDescription'   => $seoDescription,
            'galleryItems'     => $items,
            'galleryRarities'  => $rarities,
            'galleryStatTotal' => $items->count(),
            'galleryErrors'    => array_values(array_filter((array) ($galleryManifest['errors'] ?? []))),
            'websiteJsonLd'    => $this->websiteJsonLd($baseUrl, $seoDescription),
            'jsonLd'           => $this->galleryPageJsonLd($seoTitle, $seoDescription, $pageUrl, $locale, $t),
            'cssHref'          => $cssHref,
        ];
    }

    private function listPageJsonLd(string $title, string $desc, string $url, string $locale): string
    {
        $block = [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebPage',
            'name'        => $title,
            'url'         => $url,
            'description' => $desc,
            'dateModified'=> date('Y-m-d'),
            'inLanguage'  => $locale,
        ];

        return '<script type="application/ld+json">' . json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    /**
     * @param array<int, array{question: string, answer: string}> $faqItems
     */
    private function calculatorPageJsonLd(string $title, string $desc, string $url, string $locale, array $faqItems): string
    {
        $blocks = [
            [
                '@context'    => 'https://schema.org',
                '@type'       => 'WebPage',
                'name'        => $title,
                'url'         => $url,
                'description' => $desc,
                'dateModified'=> date('Y-m-d'),
                'inLanguage'  => $locale,
            ],
            [
                '@context'            => 'https://schema.org',
                '@type'               => 'WebApplication',
                'name'                => 'Steal a Brainrot SAB Calculator',
                'url'                 => $url,
                'applicationCategory' => 'GameApplication',
                'operatingSystem'     => 'Web',
                'description'         => $desc,
                'offers'              => [
                    '@type'         => 'Offer',
                    'price'         => '0',
                    'priceCurrency' => 'USD',
                ],
            ],
        ];

        if (! empty($faqItems)) {
            $blocks[] = [
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => array_map(fn (array $item) => [
                    '@type' => 'Question',
                    'name'  => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text'  => strip_tags($item['answer']),
                    ],
                ], $faqItems),
            ];
        }

        return implode("\n", array_map(
            fn ($block) => '<script type="application/ld+json">' . json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>',
            $blocks
        ));
    }

    /**
     * @param array<int, array{question: string, answer: string}> $faqItems
     */
    private function gag2CalculatorJsonLd(string $title, string $desc, string $url, array $faqItems): string
    {
        $blocks = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $title,
                'url' => $url,
                'description' => $desc,
                'dateModified' => date('Y-m-d'),
                'inLanguage' => 'en',
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => 'Grow a Garden 2 Calculator',
                'url' => $url,
                'applicationCategory' => 'GameApplication',
                'operatingSystem' => 'Web',
                'description' => $desc,
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'USD',
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn (array $item) => [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($item['answer']),
                    ],
                ], $faqItems),
            ],
        ];

        return implode("\n", array_map(
            fn ($block) => '<script type="application/ld+json">' . json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>',
            $blocks
        ));
    }

    private function galleryPageJsonLd(string $title, string $desc, string $url, string $locale, array $t): string
    {
        $blocks = [
            [
                '@context'    => 'https://schema.org',
                '@type'       => 'WebPage',
                'name'        => $title,
                'url'         => $url,
                'description' => $desc,
                'dateModified'=> date('Y-m-d'),
                'inLanguage'  => $locale,
            ],
        ];

        $faqItems = array_map(fn ($qa) => [
            '@type' => 'Question',
            'name'  => $qa[0],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => strip_tags($qa[1]),
            ],
        ], $t['exist_count_gallery_faq_items'] ?? []);

        if (! empty($faqItems)) {
            $blocks[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqItems,
            ];
        }

        return implode("\n", array_map(
            fn ($block) => '<script type="application/ld+json">' . json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>',
            $blocks
        ));
    }

    private function cvBySourceForBaseVariant(SeoItem $item): Collection
    {
        $baseVariant = $item->variants->firstWhere('variant_key', 'base') ?? $item->variants->first();

        return $baseVariant
            ? $baseVariant->currentValues->keyBy(fn ($cv) => optional($cv->source)->slug)
            : collect();
    }

    /**
     * Merge current values from ALL variants of an item, keyed by source slug.
     * Delegates to the public static helper so Blade templates can call it too.
     */
    private function mergedCvBySource(SeoItem $item): Collection
    {
        return self::mergedCvBySourceForVariants($item->variants);
    }

    /**
     * Merge current values from a variants collection, keyed by source slug.
     *
     * Non-base variants are added first (lower priority); the "base" variant
     * then overwrites any same-source entry (higher priority). This ensures
     * that moonvalues data stored on "normal"/"gold"/etc. variants is not lost
     * after eldorado creates a "base" variant that has no moonvalues entry.
     *
     * Public static so Blade templates can call it directly.
     */
    public static function mergedCvBySourceForVariants(\Illuminate\Support\Collection $variants): Collection
    {
        return app(SabCanonicalExistCountService::class)->mergedCvBySourceForVariants($variants);
    }

    /**
     * @return Collection<int, SeoItem>
     */
    private function filterItemsWithExistCount(Collection $items): Collection
    {
        return $items->filter(function (SeoItem $item) {
            return $item->total_exists !== null;
        });
    }

    /**
     * @return Collection<int, SeoItem>
     */
    private function filterItemsWithValue(Collection $items): Collection
    {
        return $items->filter(function (SeoItem $item) {
            return self::valueListValueForItem($item) !== null;
        });
    }

    /**
     * Listed items that have been synced from rot.rocks (attributes or calculator source values).
     *
     * @param  Collection<int, SeoItem>  $items
     * @return Collection<int, SeoItem>
     */
    private function filterItemsWithRotRocks(Collection $items): Collection
    {
        return $items->filter(function (SeoItem $item): bool {
            if (data_get($item->attributes_json, 'rot_rocks') !== null) {
                return true;
            }

            return $item->variants->contains(function (SeoItemVariant $variant): bool {
                return $variant->currentValues->contains(
                    fn (SeoItemCurrentValue $value): bool => optional($value->source)->slug === SabRotCalculatorSyncService::SOURCE_SLUG
                );
            });
        })->values();
    }

    /**
     * Sort value-list items by Robux descending; items without a value sink to the bottom.
     *
     * @param  Collection<int, SeoItem>  $items
     * @return Collection<int, SeoItem>
     */
    private function sortValueListItemsByRobux(Collection $items): Collection
    {
        return $items->sort(function (SeoItem $a, SeoItem $b): int {
            $valueA = self::valueListValueForItem($a);
            $valueB = self::valueListValueForItem($b);
            $hasA = $valueA !== null;
            $hasB = $valueB !== null;

            if ($hasA !== $hasB) {
                return $hasA ? -1 : 1;
            }

            if ($hasA && $hasB) {
                $metricComparison = ((float) $valueB) <=> ((float) $valueA);
                if ($metricComparison !== 0) {
                    return $metricComparison;
                }
            }

            return strcasecmp((string) $a->name, (string) $b->name);
        })->values();
    }

    /**
     * @return array{ok: bool, dest: string, src?: string, bytes?: int, message: string}
     */
    public function syncStaticCssToWebsite(string $outputPath, ?SabSiteContext $ctx = null): array
    {
        $ctx ??= new SabSiteContext(self::SITE_SLUG);
        $filename = $ctx->cssSourceFilename();
        $cssDir = "{$outputPath}/static/css";
        $this->ensureDir($cssDir);
        $dest = "{$cssDir}/{$filename}";
        $sources = [
            public_path("static/css/{$filename}"),
            resource_path("seo/sab/{$filename}"),
        ];
        if ($filename === 'sabexistcount.css') {
            $sources[] = resource_path('seo/sab/sabexistcount.css');
        }

        foreach ($sources as $src) {
            if (! is_readable($src)) {
                continue;
            }
            if (@copy($src, $dest) === false) {
                Log::error('seo:sab-render: CSS copy failed', ['src' => $src, 'dest' => $dest]);
                $result = ['ok' => false, 'dest' => $dest, 'message' => 'Copy failed', 'src' => $src];

                return self::$lastCssSyncResult = $result;
            }
            $bytes = (int) @filesize($dest);
            $result = ['ok' => true, 'dest' => $dest, 'src' => $src, 'bytes' => $bytes, 'message' => 'OK'];

            return self::$lastCssSyncResult = $result;
        }

        Log::warning('seo:sab-render: no readable CSS source', ['tried' => $sources]);
        $result = [
            'ok'      => false,
            'dest'    => $dest,
            'message' => 'No readable CSS (try public/static/css or resources/seo/sab/sabexistcount.css)',
        ];

        return self::$lastCssSyncResult = $result;
    }

    /**
     * @return array{ok: bool, dest: string, src?: string, bytes?: int, message: string}|null
     */
    public static function lastCssSyncResult(): ?array
    {
        return self::$lastCssSyncResult;
    }

    public static function productPublicSlug(?string $slug): string
    {
        $slug = trim((string) $slug);

        return self::PRODUCT_CANONICAL_SLUG_MAP[$slug] ?? $slug;
    }

    public static function shouldLinkProduct(?SeoItem $item): bool
    {
        if (! $item || ! (bool) ($item->is_publish_html ?? true)) {
            return false;
        }

        return ! self::isSuppressedProductSlug((string) $item->slug);
    }

    private static function isSuppressedProductSlug(string $slug): bool
    {
        return in_array($slug, self::NON_INDEXABLE_PRODUCT_SLUGS, true);
    }

    private static function isCanonicalAliasProductSlug(string $slug): bool
    {
        return array_key_exists($slug, self::PRODUCT_CANONICAL_SLUG_MAP);
    }

    private static function shouldRenderProductHtml(SeoItem $item): bool
    {
        $slug = (string) $item->slug;

        return ((bool) $item->is_publish_html || in_array($slug, self::TEMPORARY_NOINDEX_PRODUCT_SLUGS, true))
            && ! self::isSuppressedProductSlug($slug)
            && ! self::isCanonicalAliasProductSlug($slug);
    }

    private static function shouldIndexProductSlug(string $slug): bool
    {
        return ! self::isSuppressedProductSlug($slug)
            && ! in_array($slug, self::TEMPORARY_NOINDEX_PRODUCT_SLUGS, true);
    }

    private static function productRobotsForSlug(string $slug): string
    {
        return self::shouldIndexProductSlug($slug)
            ? 'index,follow,max-image-preview:large'
            : 'noindex,follow,max-image-preview:large';
    }

    private function renderLocale(string $locale, string $outputPath, string $baseUrl, Collection $items, Collection $news, array $i18n, string $module = self::RENDER_MODULE_ALL): void
    {
        $module = self::normalizeRenderModuleOption($module);

        $dir = $locale === self::DEFAULT_LOCALE ? $outputPath : "{$outputPath}/{$locale}";
        $this->ensureDir($dir);

        $urlPrefix = $locale === self::DEFAULT_LOCALE ? '' : "/{$locale}";
        $tMerged = $this->mergeSabTranslations($locale, $i18n);

        if ($module === self::RENDER_MODULE_CALCULATOR) {
            if ($locale === self::DEFAULT_LOCALE) {
                $this->renderCalculatorPage($dir, $urlPrefix, $locale, $tMerged, $items, $baseUrl);
            }

            return;
        }

        if ($module !== self::RENDER_MODULE_NEWS) {
            $this->ensureDir("{$dir}/products");
            $this->renderHome($dir, $urlPrefix, $locale, $tMerged, $items, $baseUrl);
            $this->renderExistCountsListPage($dir, $urlPrefix, $locale, $tMerged, $items, $baseUrl);
            $this->renderExistCountGalleryPage($dir, $urlPrefix, $locale, $tMerged, $baseUrl);
            $this->renderValueListPage($dir, $urlPrefix, $locale, $tMerged, $items, $baseUrl);
            if ($locale === self::DEFAULT_LOCALE) {
                $this->renderValueChangesPage($dir, $urlPrefix, $locale, $tMerged, $baseUrl);
            }
            $this->renderCodesPage($dir, $urlPrefix, $locale, $tMerged, $baseUrl);
            $this->renderGamePage($dir, $urlPrefix, $locale, $tMerged, $items, $baseUrl);
            if ($locale === self::DEFAULT_LOCALE) {
                $this->renderCalculatorPage($dir, $urlPrefix, $locale, $tMerged, $items, $baseUrl);
                $this->renderGag2CalculatorPage($dir, $baseUrl);
            }
            $this->cleanupSuppressedProductHtml($dir);
            foreach ($items->filter(fn (SeoItem $item) => self::shouldRenderProductHtml($item)) as $item) {
                $this->renderItem($dir, $urlPrefix, $locale, $tMerged, $item, $baseUrl);
            }
        }

        if ($module !== self::RENDER_MODULE_PRODUCTS) {
            $this->ensureDir("{$dir}/news");
            $this->renderNewsIndexPage(
                $dir,
                $urlPrefix,
                $locale,
                $tMerged,
                $news->where('locale', $locale)->values(),
                $baseUrl
            );
            foreach ($news->where('locale', $locale) as $article) {
                $this->renderNewsArticle($dir, $urlPrefix, $locale, $tMerged, $article, $baseUrl);
            }
        }
    }

    private function renderLocaleMarketingPages(string $locale, string $outputPath, string $baseUrl, Collection $items, array $i18n): void
    {
        $dir = "{$outputPath}/{$locale}";
        $this->ensureDir($dir);

        $urlPrefix = "/{$locale}";
        $tMerged = $this->mergeSabTranslations($locale, $i18n);

        $this->renderHome($dir, $urlPrefix, $locale, $tMerged, $items, $baseUrl);
        $this->renderExistCountsListPage($dir, $urlPrefix, $locale, $tMerged, $items, $baseUrl);
        $this->renderValueListPage($dir, $urlPrefix, $locale, $tMerged, $items, $baseUrl);
        $this->renderCalculatorPage($dir, $urlPrefix, $locale, $tMerged, $items, $baseUrl);
        $this->renderCodesPage($dir, $urlPrefix, $locale, $tMerged, $baseUrl);
        $this->renderGamePage($dir, $urlPrefix, $locale, $tMerged, $items, $baseUrl);
        $this->removeUnsupportedLocaleArtifacts($dir);
    }

    private function renderStaticPages(string $dir, string $baseUrl): void
    {
        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();
        foreach ($this->loadPublishedStaticPages($site) as $article) {
            $this->renderStaticPage($dir, $baseUrl, $article);
        }
    }

    private function renderStaticPage(string $dir, string $baseUrl, SeoNewsArticle $article): string
    {
        $html = view(
            'seo.sab.static-page',
            $this->staticPageViewPayload($article, '', $baseUrl, self::CSS_HREF_STATIC_HOME)
        )->render();

        $path = "{$dir}/{$article->slug}.html";
        file_put_contents($path, $html);

        return $path;
    }

    // -----------------------------------------------------------------------
    // Page renderers
    // -----------------------------------------------------------------------

    private function renderHome(string $dir, string $urlPrefix, string $locale, array $t, Collection $items, string $baseUrl): void
    {
        $html = view(
            'seo.sab.home',
            $this->homeViewPayload($urlPrefix, $locale, $baseUrl, $t, $items, self::CSS_HREF_STATIC_HOME)
        )->render();

        file_put_contents("{$dir}/index.html", $html);
    }

    private function renderExistCountsListPage(string $dir, string $urlPrefix, string $locale, array $t, Collection $items, string $baseUrl): void
    {
        $html = view(
            'seo.sab.exist-counts-list',
            $this->existCountsListViewPayload($urlPrefix, $locale, $baseUrl, $t, $items, self::CSS_HREF_STATIC_HOME)
        )->render();

        file_put_contents("{$dir}/" . self::PAGE_EXIST_COUNTS_LIST . '.html', $html);
    }

    private function renderExistCountGalleryPage(string $dir, string $urlPrefix, string $locale, array $t, string $baseUrl): void
    {
        $html = view(
            'seo.sab.exist-count-gallery',
            $this->existCountGalleryViewPayload(
                $urlPrefix,
                $locale,
                $baseUrl,
                $t,
                $this->loadExistCountGalleryFromStorage(),
                self::CSS_HREF_STATIC_HOME
            )
        )->render();

        file_put_contents("{$dir}/" . self::PAGE_EXIST_COUNT_GALLERY . '.html', $html);
    }

    private function renderValueListPage(string $dir, string $urlPrefix, string $locale, array $t, Collection $items, string $baseUrl): void
    {
        $html = view(
            'seo.sab.value-list',
            $this->valueListViewPayload($urlPrefix, $locale, $baseUrl, $t, $items, self::CSS_HREF_STATIC_HOME)
        )->render();

        file_put_contents("{$dir}/" . self::PAGE_VALUE_LIST . '.html', $html);
    }

    private function renderValueChangesPage(string $dir, string $urlPrefix, string $locale, array $t, string $baseUrl): void
    {
        $html = view(
            'seo.sab.value-changes',
            $this->valueChangesViewPayload($urlPrefix, $locale, $baseUrl, $t, 7, null, 'recent', self::CSS_HREF_STATIC_HOME)
        )->render();

        file_put_contents("{$dir}/" . self::PAGE_VALUE_CHANGES . '.html', $html);
    }

    private function renderCodesPage(string $dir, string $urlPrefix, string $locale, array $t, string $baseUrl): void
    {
        $html = view(
            'seo.sab.codes',
            $this->codesViewPayload($urlPrefix, $locale, $baseUrl, $t, $this->codesData(), self::CSS_HREF_STATIC_HOME)
        )->render();

        file_put_contents("{$dir}/" . self::PAGE_CODES . '.html', $html);
    }

    /**
     * @param  array<string, mixed>  $t
     */
    private function renderGamePage(string $dir, string $urlPrefix, string $locale, array $t, Collection $items, string $baseUrl): void
    {
        $this->ensureDir("{$dir}/" . self::PAGE_GAMES_DIR);
        $hubHtml = view(
            'seo.sab.games',
            $this->gamesHubViewPayload($urlPrefix, $locale, $baseUrl, $t, $items, self::CSS_HREF_STATIC_HOME)
        )->render();
        file_put_contents("{$dir}/" . self::PAGE_GAMES_DIR . '/index.html', $hubHtml);

        foreach (array_keys(self::gamesCatalog()) as $slug) {
            $html = view(
                'seo.sab.game',
                $this->gameViewPayload($urlPrefix, $locale, $baseUrl, $t, self::CSS_HREF_STATIC_HOME, $slug)
            )->render();
            file_put_contents("{$dir}/" . self::gamePublicPath($slug) . '.html', $html);
        }
    }

    private function renderCalculatorPage(string $dir, string $urlPrefix, string $locale, array $t, Collection $items, string $baseUrl): void
    {
        $site = SeoSite::where('slug', self::SITE_SLUG)->firstOrFail();

        $html = view(
            'seo.sab.calculator',
            $this->calculatorViewPayload($urlPrefix, $locale, $baseUrl, $t, $items, self::CSS_HREF_STATIC_HOME, $site)
        )->render();

        file_put_contents("{$dir}/" . self::PAGE_TRADING_CALCULATOR . '.html', $html);
    }

    private function renderGag2CalculatorPage(string $dir, string $baseUrl): void
    {
        $html = view(
            'seo.sab.gag2-calculator',
            $this->gag2CalculatorViewPayload($baseUrl, self::CSS_HREF_STATIC_HOME, '')
        )->render();

        file_put_contents("{$dir}/" . self::PAGE_GAG2_CALCULATOR . '.html', $html);
    }

    private function renderItem(string $dir, string $urlPrefix, string $locale, array $t, SeoItem $item, string $baseUrl): void
    {
        $translation = $item->translation($locale);
        $displayName = $translation?->name ?: $item->name;
        $aboutFields = $this->cleanAboutFields($item, $translation?->description);
        $seoTitle = $translation?->seo_title ?: $this->itemDefaultSeoTitle($item, $displayName);
        $seoDescription = $translation?->seo_description ?: $this->itemDefaultSeoDescription($item, $displayName);

        $currentValues = $this->groupCurrentValues($item);
        $history       = $this->itemHistory($item);
        $mutationPriceData = $this->itemMutationPricePayload($item);
        $priceHistory  = $mutationPriceData['mutations'][0]['priceHistory']
            ?? $this->brainrotPriceHistoryForItem($item);
        $site = SeoSite::where('slug', self::SITE_SLUG)->first();
        $calculatorBrainrot = $this->calculatorBrainrotForItem($item, $site);
        $itemUrl = $this->localePublicUrl($baseUrl, $locale, 'products/' . self::productPublicSlug($item->slug) . '.html');
        $homeUrl = $this->localePublicUrl($baseUrl, $locale, 'index.html');
        $breadcrumbCurrent = "{$displayName} {$t['item_page_exist_count_label']}";

        $html = view('seo.sab.item', [
            'locale'         => $locale,
            'urlPrefix'      => $urlPrefix,
            'baseUrl'        => $baseUrl,
            't'              => $t,
            'hreflangLinks'  => [],
            'canonical'      => $itemUrl,
            'robots'         => self::productRobotsForSlug((string) $item->slug),
            'seoTitle'       => $seoTitle,
            'seoDescription' => $seoDescription,
            'item'           => $item,
            'displayName'    => $displayName,
            'description'    => $aboutFields['description'],
            'rotRocksDescriptionHtml' => $aboutFields['rotRocksDescriptionHtml'],
            'currentValues'  => $currentValues,
            'history'        => $history,
            'priceHistory'   => $priceHistory,
            'mutationPriceData' => $mutationPriceData,
            'calculatorBrainrot' => $calculatorBrainrot,
            'websiteJsonLd'  => $this->websiteJsonLd($baseUrl, $seoDescription),
            'jsonLd'         => $this->itemJsonLd($seoTitle, $seoDescription, $itemUrl, $homeUrl, $breadcrumbCurrent, $t),
            'cssHref'        => self::CSS_HREF_STATIC_PRODUCT,
            'playRobBrainrotHref' => rtrim($urlPrefix, '/') . '/' . self::gamePublicPath(self::PAGE_GAME_ROB),
        ])->render();

        file_put_contents("{$dir}/products/{$item->slug}.html", $html);
    }

    private function renderCalculatorItem(
        string $outputPath,
        SeoItem $item,
        array $brainrotData,
        SeoSite $site,
        SabSiteContext $ctx,
        string $baseUrl
    ): void {
        $translation = $item->translation(self::DEFAULT_LOCALE);
        $displayName = $translation?->name ?: $item->name;
        $aboutFields = $this->cleanAboutFields($item, $translation?->description);

        $mutationPriceData = $this->itemMutationPricePayload($item);
        $priceHistory = $mutationPriceData['mutations'][0]['priceHistory']
            ?? $this->brainrotPriceHistoryForItem($item);

        $seoTitle       = $this->calculatorItemSeoTitle($displayName, $brainrotData);
        $seoDescription = $this->calculatorItemSeoDescription($displayName, $brainrotData);
        $itemUrl        = rtrim($baseUrl, '/') . '/products/' . self::productPublicSlug($item->slug);

        $html = view('seo.sab.item-calculator', [
            'locale'                  => self::DEFAULT_LOCALE,
            'urlPrefix'               => '',
            'baseUrl'                 => $baseUrl,
            'brand'                   => $ctx->brand($site),
            'cssHref'                 => $ctx->cssHrefForRender(),
            'item'                    => $item,
            'displayName'             => $displayName,
            'description'             => $aboutFields['description'],
            'rotRocksDescriptionHtml' => $aboutFields['rotRocksDescriptionHtml'],
            'brainrotData'            => $brainrotData,
            'mutationPriceData'       => $mutationPriceData,
            'priceHistory'            => $priceHistory,
            'seoTitle'                => $seoTitle,
            'seoDescription'          => $seoDescription,
            'canonical'               => $itemUrl,
            'robots'                  => self::productRobotsForSlug((string) $item->slug),
            'websiteJsonLd'           => $this->websiteJsonLd($baseUrl, $seoDescription),
            'jsonLd'                  => null,
            'hreflangLinks'           => [],
        ])->render();

        file_put_contents("{$outputPath}/products/{$item->slug}.html", $html);
    }

    private function cleanupSuppressedProductHtml(string $dir): void
    {
        foreach ($this->nonRenderableProductSlugs() as $slug) {
            $path = "{$dir}/products/{$slug}.html";
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /** @return list<string> */
    private function nonRenderableProductSlugs(): array
    {
        return array_values(array_unique(array_merge(
            self::NON_INDEXABLE_PRODUCT_SLUGS,
            array_keys(self::PRODUCT_CANONICAL_SLUG_MAP),
        )));
    }

    private function renderNewsIndexPage(string $dir, string $urlPrefix, string $locale, array $t, Collection $articles, string $baseUrl): void
    {
        $html = view(
            'seo.sab.news-index',
            $this->newsIndexViewPayload($articles, $urlPrefix, $locale, $baseUrl, $t, self::CSS_HREF_STATIC_HOME)
        )->render();

        file_put_contents("{$dir}/news/index.html", $html);
    }

    private function renderNewsArticle(string $dir, string $urlPrefix, string $locale, array $t, SeoNewsArticle $article, string $baseUrl): string
    {
        $html = view('seo.sab.news', $this->newsViewPayload(
            article: $article,
            urlPrefix: $urlPrefix,
            locale: $locale,
            baseUrl: $baseUrl,
            t: $t,
            cssHref: self::CSS_HREF_STATIC_PRODUCT,
        ))->render();

        $path = "{$dir}/news/{$article->slug}.html";
        file_put_contents($path, $html);

        return $path;
    }

    // -----------------------------------------------------------------------
    // Sitemap & data files
    // -----------------------------------------------------------------------

    private function renderSitemap(string $outputPath, string $baseUrl, Collection $items, Collection $news, Collection $staticPages, array $generatedLocales, ?array $detailLocales = null): void
    {
        $builder = app(SabSitemapBuilder::class);
        foreach ($builder->staticFiles() as $file) {
            $full = $outputPath.'/'.$file['path'];
            $dir = dirname($full);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($full, $file['body']);
        }
    }

    private function renderRobotsTxt(string $outputPath, string $baseUrl): void
    {
        $baseUrl = rtrim($baseUrl, '/');
        $templatePath = resource_path('seo/sab/robots.txt.stub');
        if (! is_readable($templatePath)) {
            throw new \RuntimeException("SAB robots.txt template is missing: {$templatePath}");
        }

        $template = trim((string) file_get_contents($templatePath));
        if ($template === '') {
            throw new \RuntimeException("SAB robots.txt template is empty: {$templatePath}");
        }

        $localeDisallows = [];
        foreach ($this->nonDefaultHomeLocales() as $locale) {
            $localeDisallows[] = "Disallow: /{$locale}/products/";
            $localeDisallows[] = "Disallow: /{$locale}/news/";
        }

        $robots = str_replace('{{ base_url }}', $baseUrl, $template);
        $robots = preg_replace(
            "/(Allow: \\/\\n)/",
            "$1" . implode("\n", $localeDisallows) . "\n",
            $robots,
            1
        ) ?? $robots;

        file_put_contents("{$outputPath}/robots.txt", $robots."\n");
    }

    private function renderHomeSitemap(string $outputPath, string $baseUrl, array $generatedLocales): void
    {
        $locales = array_values(array_intersect(self::HOME_LOCALES, $generatedLocales));
        $codesLastmod = (string) $this->codesData()['verified_at'];
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($locales as $locale) {
            foreach ([
                ['path' => 'index.html', 'priority' => '1.0'],
                ['path' => self::PAGE_EXIST_COUNTS_LIST, 'priority' => '0.85'],
                ['path' => self::PAGE_TRADING_CALCULATOR, 'priority' => '0.85'],
                ['path' => self::PAGE_CODES, 'priority' => '0.9', 'lastmod' => $codesLastmod],
            ] as $row) {
                $xml .= "  <url>\n    <loc>"
                    . htmlspecialchars($this->localePublicUrl($baseUrl, $locale, $row['path']))
                    . "</loc>\n";
                if (! empty($row['lastmod'])) {
                    $xml .= "    <lastmod>{$row['lastmod']}</lastmod>\n";
                }
                $xml .= "    <priority>{$row['priority']}</priority>\n  </url>\n";
            }
        }
        $xml .= '</urlset>';
        file_put_contents("{$outputPath}/sitemap.xml", $xml);
    }

    private function nonDefaultHomeLocales(): array
    {
        return array_values(array_filter(
            self::HOME_LOCALES,
            fn (string $locale): bool => $locale !== self::DEFAULT_LOCALE
        ));
    }

    private function renderDataFiles(string $outputPath, Collection $items): void
    {
        $this->ensureDir("{$outputPath}/data/history");

        $latest = $items->map(function (SeoItem $item) {
            return [
                'slug'          => $item->slug,
                'name'          => $item->name,
                'rarity'        => $item->rarity,
                'summary'       => $item->summary,
                'is_publish_html' => (bool) $item->is_publish_html,
                'total_exists'  => $item->total_exists,
                'avg_rebirth'   => $item->avg_rebirth,
                'avg_coins_raw' => $item->avg_coins_raw,
                'image_url'     => $item->image_url,
                'current_values' => $this->groupCurrentValues($item),
            ];
        });
        file_put_contents("{$outputPath}/data/latest.json", json_encode($latest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $site = SeoSite::where('slug', self::SITE_SLUG)->first();
        file_put_contents(
            "{$outputPath}/data/calculator.json",
            json_encode($this->calculatorData($items, $site), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        foreach ($items as $item) {
            $history = $this->itemHistory($item);
            file_put_contents(
                "{$outputPath}/data/history/{$item->slug}.json",
                json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }
    }

    // -----------------------------------------------------------------------
    // Static helpers (callable from Blade)
    // -----------------------------------------------------------------------

    /**
     * Pick best known exist count from base-variant current values (keyed by source slug).
     * Order: Eldorado → Fandom Exist Counts page sync → Gallery OCR → legacy Fandom category sync → MoonValues.
     */
    public static function preferExistCountFromCvBySource(Collection $cvBySource): ?int
    {
        return app(SabCanonicalExistCountService::class)->selectFromCurrentValues($cvBySource);
    }

    /** MoonValues RAP / value for base variant (matches item variants table). */
    public static function preferValueFromCvBySource(Collection $cvBySource): ?float
    {
        return $cvBySource->get('moonvalues')?->value_normalized;
    }

    /**
     * Value list uses the same base-variant signal shown in product page snapshots.
     */
    public static function valueListValueForItem(SeoItem $item): ?float
    {
        $baseVariant = $item->variants->firstWhere('variant_key', 'base')
            ?? $item->variants->firstWhere('variant_type', 'base');

        if (! $baseVariant) {
            return null;
        }

        $preferred = $baseVariant->currentValues
            ->first(fn ($cv) => optional($cv->source)->slug === SabRotCalculatorSyncService::SOURCE_SLUG
                && $cv->value_normalized !== null);

        $value = $preferred ?? $baseVariant->currentValues
            ->filter(fn ($cv) => $cv->value_normalized !== null)
            ->sortByDesc(fn ($cv) => ($cv->changed_at ?? $cv->collected_at)?->timestamp ?? 0)
            ->first();

        return $value?->value_normalized !== null ? (float) $value->value_normalized : null;
    }

    public static function raritySignal(?float $ec): array
    {
        if ($ec === null) {
            return ['unknown', 'bg-slate-700/30 text-slate-500'];
        }
        if ($ec >= 1_000_000) return ['very_high',      'bg-green-400/10 text-green-300'];
        if ($ec >= 100_000)   return ['medium',         'bg-yellow-400/10 text-yellow-300'];
        if ($ec >= 10_000)    return ['low',            'bg-orange-400/10 text-orange-300'];
        if ($ec >= 1_000)     return ['very_low',       'bg-red-400/10 text-red-300'];
        if ($ec > 10)         return ['extremely_rare', 'bg-purple-400/10 text-purple-300'];
        if ($ec > 1)          return ['near_unique',    'bg-cyan-400/10 text-cyan-300'];
        return                        ['lowest',        'bg-cyan-400/10 text-cyan-300'];
    }

    /**
     * Reduce noisy imported `rarity` strings (template placeholders, prose) to a
     * short tier slug matching `homeViewPayload` rarity filter keys. Returns '' if none.
     */
    public static function canonicalRarityKey(?string $raw): string
    {
        if ($raw === null) {
            return '';
        }

        $s = trim($raw);
        if ($s === '') {
            return '';
        }

        $prev = null;
        while ($prev !== $s) {
            $prev = $s;
            $next = preg_replace('/\{\{[^{}]*\|([^|}]+)\}\}/u', '$1', $s);
            if ($next !== $s) {
                $s = $next;

                continue;
            }
            $next = preg_replace('/\{\{[^{}]+\}\}/u', ' ', $s);
            if ($next !== $s) {
                $s = $next;

                continue;
            }
        }
        $s = preg_replace('/\{\{[^}]*$/u', ' ', $s) ?? $s;
        $s = preg_replace('/\{[^}]*\}/u', ' ', $s) ?? $s;
        $s = trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
        if ($s === '') {
            return '';
        }

        $lower = mb_strtolower($s);

        $patterns = [
            'brainrot god' => '/\bbrainrot\s+god\b/u',
            'legendary'    => '/\blegendary\b/u',
            'mythic'       => '/\bmythic\b/u',
            'uncommon'     => '/\buncommon\b/u',
            'secret'       => '/\bsecret\b/u',
            'epic'         => '/\bepic\b/u',
            'rare'         => '/\brare\b/u',
            'common'       => '/\bcommon\b/u',
            'og'           => '/\bog\b/u',
        ];

        foreach ($patterns as $tier => $pattern) {
            if (preg_match($pattern, $lower) === 1) {
                return $tier;
            }
        }

        if (strlen($s) <= 36
            && strpbrk($s, '{}') === false
            && preg_match('/^[a-zA-Z][a-zA-Z\s\-]{0,34}$/', $s) === 1) {
            return mb_strtolower($s);
        }

        return '';
    }

    /** Title-case label for UI (works with `canonicalRarityKey` output). */
    public static function canonicalRarityLabel(string $key): string
    {
        if ($key === '') {
            return '';
        }

        return mb_convert_case($key, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Canonical rarity filter keys in preview/home chip order (excludes "all").
     *
     * @return list<string>
     */
    public static function rarityFilterTagKeys(): array
    {
        return [
            'og',
            'legendary',
            'mythic',
            'rare',
            'secret',
            'epic',
            'common',
            'brainrot god',
        ];
    }

    /**
     * @return list<array{key: string, label: string, count: int}>
     */
    public static function buildAdminRarityTagStats(iterable $items): array
    {
        $counts = ['' => 0];
        foreach (self::rarityFilterTagKeys() as $tagKey) {
            $counts[$tagKey] = 0;
        }

        foreach ($items as $item) {
            $rawRarity = is_object($item) ? ($item->rarity ?? null) : ($item['rarity'] ?? null);
            $counts['']++;
            $canonicalKey = self::canonicalRarityKey(is_string($rawRarity) ? $rawRarity : null);
            if ($canonicalKey !== '' && array_key_exists($canonicalKey, $counts)) {
                $counts[$canonicalKey]++;
            }
        }

        $tags = [];
        foreach (['', ...self::rarityFilterTagKeys()] as $tagKey) {
            $tags[] = [
                'key' => $tagKey,
                'label' => $tagKey === '' ? '全部' : self::canonicalRarityLabel($tagKey),
                'count' => $counts[$tagKey] ?? 0,
            ];
        }

        return $tags;
    }

    public static function applyCanonicalRarityFilter(Builder $query, string $key): void
    {
        $key = mb_strtolower(trim($key));
        if ($key === '') {
            return;
        }

        if (!in_array($key, self::rarityFilterTagKeys(), true)) {
            $query->whereRaw('0 = 1');

            return;
        }

        $matchingIds = self::pluckIdsMatchingCanonicalRarity($query, $key);
        $query->whereIn('seo_items.id', $matchingIds === [] ? [-1] : $matchingIds);
    }

    /**
     * @return list<int>
     */
    public static function pluckIdsMatchingCanonicalRarity(Builder $query, string $key): array
    {
        $cloned = clone $query;
        $cloned->getQuery()->orders = null;
        $cloned->getQuery()->limit = null;
        $cloned->getQuery()->offset = null;

        return $cloned->select('seo_items.id', 'seo_items.rarity')
            ->get()
            ->unique('id')
            ->filter(fn ($row) => self::canonicalRarityKey($row->rarity ?? null) === $key)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, SeoItem>
     */
    public static function listedItems(Collection $items): Collection
    {
        return $items->filter(fn (SeoItem $item) => (bool) $item->is_listed)->values();
    }

    /**
     * Listing/thumbnail image for homepage, list pages, and product detail hero.
     * Prefers uploads/images/sab/thumbs/{slug}-64.webp (seo:sab-images convention).
     */
    public static function listingImageSrc(SeoItem $item): ?string
    {
        $thumbPath = 'uploads/images/sab/thumbs/' . $item->slug . '-64.webp';
        if (file_exists(public_path($thumbPath))) {
            return '/' . $thumbPath;
        }

        $local = ltrim((string) $item->local_image_url, '/');
        if ($local !== '' && file_exists(public_path($local))) {
            return '/' . $local;
        }

        return $item->image_url ?: null;
    }

    public static function formatLargeNumber(float $n): string
    {
        if ($n >= 1_000_000_000) return round($n / 1_000_000_000, 1) . 'B+';
        if ($n >= 1_000_000)     return round($n / 1_000_000, 1) . 'M+';
        if ($n >= 1_000)         return round($n / 1_000, 1) . 'K+';
        return number_format((int) $n);
    }

    public static function formatEstimateRangeShort(?int $low, ?int $high): string
    {
        if ($low === null || $high === null) {
            return '—';
        }

        $format = static function (int $value): string {
            if ($value >= 1_000_000_000) {
                return rtrim(rtrim(number_format($value / 1_000_000_000, 1), '0'), '.') . 'B';
            }
            if ($value >= 1_000_000) {
                return rtrim(rtrim(number_format($value / 1_000_000, 1), '0'), '.') . 'M';
            }
            if ($value >= 1_000) {
                return rtrim(rtrim(number_format($value / 1_000, 1), '0'), '.') . 'K';
            }

            return number_format($value);
        };

        return $format($low) . '-' . $format($high);
    }

    public static function getI18nEn(): array
    {
        return self::I18N_EN;
    }

    public static function i18nStoragePath(): string
    {
        return storage_path('app/seo/sab-i18n.json');
    }

    public static function legacyI18nStoragePath(): string
    {
        return storage_path('app/private/seo/sab-i18n.json');
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function loadItems(SeoGame $game): Collection
    {
        // Only load base and mutation variants; trait variants are global and
        // live in storage/app/calc/sab/meta.json (not duplicated per item in DB).
        $items = SeoItem::query()
            ->where('seo_game_id', $game->id)
            ->with([
                'variants' => fn ($q) => $q->whereIn('variant_type', ['base', 'mutation'])
                    ->with('currentValues.source'),
                'translations',
            ])
            ->get();

        return $this->sortItemsBySortOrderThenName($items);
    }

    /**
     * Read global traits/mutations meta written by SabRotCalculatorSyncService::saveCalculatorMeta().
     *
     * @return array{traits: array, mutations: array, streakMultipliers: array}
     */
    private function loadCalculatorMeta(): array
    {
        $path = SabCalculatorCatalogService::metaReadPath();
        if (! is_file($path)) {
            return ['traits' => [], 'mutations' => [], 'streakMultipliers' => ['3' => 2, '6' => 3]];
        }

        $data = json_decode(file_get_contents($path), true);

        return is_array($data) ? $data : ['traits' => [], 'mutations' => [], 'streakMultipliers' => ['3' => 2, '6' => 3]];
    }

    /**
     * @return Collection<int, SeoItem>
     */
    private function sortItemsBySortOrderThenName(Collection $items): Collection
    {
        return $items->sort(fn (SeoItem $a, SeoItem $b) => $this->compareItemsBySortOrderThenName($a, $b))->values();
    }

    /**
     * @param callable(SeoItem): float|int|null $metric
     * @return Collection<int, SeoItem>
     */
    private function sortItemsBySortOrderThenMetric(Collection $items, callable $metric): Collection
    {
        return $items->sort(function (SeoItem $a, SeoItem $b) use ($metric): int {
            $sortOrderComparison = $this->sortOrder($b) <=> $this->sortOrder($a);
            if ($sortOrderComparison !== 0) {
                return $sortOrderComparison;
            }

            $metricComparison = ((float) ($metric($b) ?? -1)) <=> ((float) ($metric($a) ?? -1));
            if ($metricComparison !== 0) {
                return $metricComparison;
            }

            return strcasecmp($a->name, $b->name);
        })->values();
    }

    private function compareItemsBySortOrderThenName(SeoItem $a, SeoItem $b): int
    {
        $sortOrderComparison = $this->sortOrder($b) <=> $this->sortOrder($a);
        if ($sortOrderComparison !== 0) {
            return $sortOrderComparison;
        }

        return strcasecmp($a->name, $b->name);
    }

    /**
     * Exist count sort proxy within the same data-quality tier.
     * Known counts use total_exists; estimated rows use estimate midpoint; unknown returns null.
     */
    private static function effectiveHomeExistCountForSort(SeoItem $item): ?int
    {
        if ($item->total_exists !== null) {
            return (int) $item->total_exists;
        }

        if ($item->exist_estimate_low !== null && $item->exist_estimate_high !== null) {
            return (int) (((int) $item->exist_estimate_low + (int) $item->exist_estimate_high) / 2);
        }

        return null;
    }

    /** 0 = known count, 1 = guess estimate, 2 = no count data. */
    private function homeExistCountSortTier(SeoItem $item): int
    {
        if ($item->total_exists !== null) {
            return 0;
        }

        if ($item->exist_estimate_low !== null && $item->exist_estimate_high !== null) {
            return 1;
        }

        return 2;
    }

    /**
     * Unified exist count display resolver for Blade templates.
     *
     * Priority: explicit known count > total_exists > estimate columns > none.
     *
     * Returns an array:
     *   kind         => 'known' | 'estimated' | 'none'
     *   primary      => formatted string shown as main number / range ('55,127' | '19,294-90,960' | '—')
     *   label        => badge text ('Guess' for estimated, null for known/none)
     *   sort_value   => integer for data-sort-exist (midpoint for estimated)
     *   reason       => human-readable estimate reason or null
     *   confidence   => 'low' | 'medium' | 'high' | null
     *   is_estimated => bool
     */
    public static function resolveExistCountDisplay(SeoItem $item, ?int $knownCount = null): array
    {
        $known = $knownCount
            ?? ($item->total_exists !== null ? (int) $item->total_exists : null);

        if ($known !== null) {
            return [
                'kind'         => 'known',
                'primary'      => number_format($known),
                'label'        => null,
                'short'        => self::formatLargeNumber((float) $known),
                'sort_value'   => $known,
                'reason'       => null,
                'confidence'   => null,
                'is_estimated' => false,
            ];
        }

        $low  = $item->exist_estimate_low !== null ? (int) $item->exist_estimate_low : null;
        $high = $item->exist_estimate_high !== null ? (int) $item->exist_estimate_high : null;

        if ($low !== null && $high !== null) {
            return [
                'kind'         => 'estimated',
                'primary'      => number_format($low) . '-' . number_format($high),
                'label'        => 'Guess',
                'short'        => self::formatEstimateRangeShort($low, $high),
                'sort_value'   => (int) (($low + $high) / 2),
                'reason'       => trim((string) ($item->exist_estimate_reason ?? '')),
                'confidence'   => trim((string) ($item->exist_estimate_confidence ?? '')),
                'is_estimated' => true,
            ];
        }

        return [
            'kind'         => 'none',
            'primary'      => '—',
            'label'        => null,
            'short'        => '—',
            'sort_value'   => null,
            'reason'       => null,
            'confidence'   => null,
            'is_estimated' => false,
        ];
    }

    /**
     * @return Collection<int, SeoItem>
     */
    private function sortItemsForHomeTable(Collection $items): Collection
    {
        return $items->sort(fn (SeoItem $a, SeoItem $b) => $this->compareItemsForHomeTable($a, $b))->values();
    }

    /** Main home table ordering: known counts first, then estimates, unknown counts last. */
    private function compareItemsForHomeTable(SeoItem $a, SeoItem $b): int
    {
        $tierCmp = $this->homeExistCountSortTier($a) <=> $this->homeExistCountSortTier($b);
        if ($tierCmp !== 0) {
            return $tierCmp;
        }

        $sortOrderComparison = $this->sortOrder($b) <=> $this->sortOrder($a);
        if ($sortOrderComparison !== 0) {
            return $sortOrderComparison;
        }

        $newCmp = (int) self::isNewHomeItem($b) <=> (int) self::isNewHomeItem($a);
        if ($newCmp !== 0) {
            return $newCmp;
        }

        $ea = self::effectiveHomeExistCountForSort($a);
        $eb = self::effectiveHomeExistCountForSort($b);

        // Desc larger first; unknown (null) at end
        if ($ea !== null || $eb !== null) {
            if ($ea === null) {
                return 1;
            }
            if ($eb === null) {
                return -1;
            }
            $ecCmp = $eb <=> $ea;
            if ($ecCmp !== 0) {
                return $ecCmp;
            }
        }

        $maAdj = $a->rarest_mutation_count ?? PHP_INT_MAX;
        $mbAdj = $b->rarest_mutation_count ?? PHP_INT_MAX;
        $mutCmp = $maAdj <=> $mbAdj;
        if ($mutCmp !== 0) {
            return $mutCmp;
        }

        $taAdj = $a->rarest_trait_count ?? PHP_INT_MAX;
        $tbAdj = $b->rarest_trait_count ?? PHP_INT_MAX;
        $traitCmp = $taAdj <=> $tbAdj;
        if ($traitCmp !== 0) {
            return $traitCmp;
        }

        return strcasecmp($a->name, $b->name);
    }

    public static function isNewHomeItem(SeoItem $item): bool
    {
        $date = self::homeNewReferenceDate($item);

        return $date !== null && $date->gte(now()->subDays(14));
    }

    private static function homeNewReferenceDate(SeoItem $item): ?Carbon
    {
        foreach ([
            data_get($item->attributes_json, 'rot_rocks.first_seen_at'),
            data_get($item->attributes_json, 'manual_update.checked_at'),
            $item->created_at,
        ] as $candidate) {
            if ($candidate instanceof Carbon) {
                return $candidate;
            }

            if ($candidate === null || trim((string) $candidate) === '') {
                continue;
            }

            try {
                return Carbon::parse($candidate);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function sortOrder(SeoItem $item): int
    {
        return (int) ($item->sort_order ?? 10);
    }

    private function loadPublishedNews(SeoSite $site): Collection
    {
        return SeoNewsArticle::query()
            ->where('seo_site_id', $site->id)
            ->where('type', SeoNewsArticle::TYPE_NEWS)
            ->where('status', 'published')
            ->whereIn('locale', self::HOME_LOCALES)
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderByDesc('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();
    }

    private function loadPublishedStaticPages(SeoSite $site): Collection
    {
        return SeoNewsArticle::query()
            ->where('seo_site_id', $site->id)
            ->where('type', SeoNewsArticle::TYPE_STATIC_PAGE)
            ->where('status', 'published')
            ->where('locale', self::DEFAULT_LOCALE)
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderByDesc('sort_order')
            ->orderBy('slug')
            ->get();
    }

    private function loadI18n(?SeoSite $site = null): array
    {
        $base = [self::DEFAULT_LOCALE => self::I18N_EN];
        $path = self::i18nStoragePath();
        if (! file_exists($path) && file_exists(self::legacyI18nStoragePath())) {
            $path = self::legacyI18nStoragePath();
        }
        if (file_exists($path)) {
            $stored = json_decode(file_get_contents($path), true);
            if (is_array($stored)) {
                $merged = array_merge($base, $stored);
                $merged[self::DEFAULT_LOCALE] = $this->applySiteCopyOverrides(
                    array_merge(self::I18N_EN, $merged[self::DEFAULT_LOCALE] ?? []),
                    $site
                );
                foreach ($merged as $locale => $messages) {
                    if ($locale === self::DEFAULT_LOCALE || ! is_array($messages)) {
                        continue;
                    }
                    $merged[$locale] = $this->sanitizeI18nTree(
                        array_merge(self::I18N_EN, $messages),
                        $merged[self::DEFAULT_LOCALE]
                    );
                }

                return $merged;
            }
        }
        $base[self::DEFAULT_LOCALE] = $this->applySiteCopyOverrides($base[self::DEFAULT_LOCALE], $site);

        return $base;
    }

    private function sanitizeI18nTree(mixed $value, mixed $fallback): mixed
    {
        if (is_string($value)) {
            return $this->containsI18nProtectionToken($value) || $this->hasPlaceholderMismatch($value, $fallback)
                ? (is_string($fallback) ? $fallback : '')
                : $value;
        }

        if (is_array($value)) {
            $clean = [];
            foreach ($value as $key => $child) {
                $clean[$key] = $this->sanitizeI18nTree(
                    $child,
                    is_array($fallback) && array_key_exists($key, $fallback) ? $fallback[$key] : null
                );
            }

            return $clean;
        }

        return $value;
    }

    private function containsI18nProtectionToken(string $text): bool
    {
        return (bool) preg_match('/(?:__SAB\d+__|SAB\s*(?:TERM|PH)\s*\d*\s*KEEP|TERM\d+\s*SABTERM)/iu', $text);
    }

    private function hasPlaceholderMismatch(string $value, mixed $fallback): bool
    {
        if (! is_string($fallback)) {
            return false;
        }

        $expected = $this->extractPlaceholders($fallback);
        $actual = $this->extractPlaceholders($value);
        if ($expected === []) {
            return $actual !== [];
        }

        sort($expected);
        sort($actual);

        return $expected !== $actual;
    }

    /**
     * @return list<string>
     */
    private function extractPlaceholders(string $text): array
    {
        preg_match_all('/\{[A-Za-z0-9_]+\}/', $text, $matches);

        return array_values(array_unique($matches[0] ?? []));
    }

    /**
     * @param  array<string, mixed>  $t
     * @return array<string, mixed>
     */
    private function applySiteCopyOverrides(array $t, ?SeoSite $site): array
    {
        $settings = $site?->settings_json;
        $copy = is_array($settings) && is_array($settings['sab_copy'] ?? null)
            ? $settings['sab_copy']
            : [];

        $overrides = [
            'home.meta_title' => 'meta_title',
            'home.meta_description' => 'meta_description',
            'home.hero_body' => 'hero_body',
            'exist_counts_list.meta_title' => 'exist_counts_list_meta_title',
            'exist_counts_list.meta_description' => 'exist_counts_list_meta_description',
            'exist_counts_list.h1' => 'exist_counts_list_h1',
            'exist_counts_list.intro' => 'exist_counts_list_intro',
            'exist_counts_list.table_h2' => 'exist_counts_list_table_h2',
            'exist_counts_list.table_caption' => 'exist_counts_list_table_caption',
            'exist_counts_list.updated' => 'exist_counts_list_updated',
            'value_list.meta_title' => 'value_list_meta_title',
            'value_list.meta_description' => 'value_list_meta_description',
            'value_list.h1' => 'value_list_h1',
            'value_list.intro' => 'value_list_intro',
            'value_list.table_h2' => 'value_list_table_h2',
            'value_list.table_caption' => 'value_list_table_caption',
            'value_list.updated' => 'value_list_updated',
            'calculator.meta_title' => 'calculator_meta_title',
            'calculator.meta_description' => 'calculator_meta_description',
            'calculator.h1' => 'calculator_h1',
            'calculator.intro' => 'calculator_intro',
        ];

        foreach ($overrides as $path => $key) {
            $value = data_get($copy, $path);
            if (is_string($value) && trim($value) !== '') {
                $t[$key] = trim($value);
            }
        }

        return $t;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadExistCountGalleryFromStorage(): array
    {
        $path = storage_path('app/seo/sab-exist-count-gallery.json');
        if (! file_exists($path)) {
            return [
                'items' => [],
                'errors' => ['Run php artisan seo:sab-fandom-gallery before rendering the gallery.'],
            ];
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::warning('seo:sab-render: invalid gallery manifest', ['path' => $path, 'error' => $e->getMessage()]);

            return [
                'items' => [],
                'errors' => ['Gallery manifest is invalid. Run php artisan seo:sab-fandom-gallery again.'],
            ];
        }

        if (! is_array($data)) {
            return [
                'items' => [],
                'errors' => ['Gallery manifest is invalid. Run php artisan seo:sab-fandom-gallery again.'],
            ];
        }

        $data['items'] = is_array($data['items'] ?? null) ? $data['items'] : [];
        $data['errors'] = is_array($data['errors'] ?? null) ? $data['errors'] : [];

        return $data;
    }

    private function galleryRarityKey(string $rarity): string
    {
        $key = strtolower(trim($rarity));
        $key = preg_replace('/[^a-z0-9]+/', '-', $key) ?: '';

        return trim($key, '-');
    }

    private function groupCurrentValues(SeoItem $item): array
    {
        $result = [];
        foreach ($item->variants as $variant) {
            foreach ($variant->currentValues as $cv) {
                $result[$variant->variant_key][$cv->source->slug ?? 'unknown'] = [
                    'exist_count_raw'        => $cv->exist_count_raw,
                    'exist_count_normalized' => $cv->exist_count_normalized,
                    'value_raw'              => $cv->value_raw,
                    'value_normalized'       => $cv->value_normalized,
                    'demand'                 => $cv->demand,
                    'currency'               => $cv->currency,
                    'collected_at'           => optional($cv->collected_at)->toIso8601String(),
                    'changed_at'             => optional($cv->changed_at)->toIso8601String(),
                ];
            }
        }
        return $result;
    }

    private function itemSourcePool(SeoSite $site, SeoGame $game, SeoItem $item): Collection
    {
        return SeoItemSourcePool::query()
            ->where('seo_site_id', $site->id)
            ->where('seo_game_id', $game->id)
            ->where('item_slug', $item->slug)
            ->orderBy('source_slug')
            ->orderByDesc('last_collected_at')
            ->get();
    }

    private function recentItemObservations(SeoItem $item, int $limit = 10): Collection
    {
        if (! $this->observationsTableExists()) {
            return collect();
        }

        $variantIds = $item->variants->pluck('id')->all();
        if (empty($variantIds)) {
            return collect();
        }

        return SeoItemObservation::query()
            ->whereIn('seo_item_variant_id', $variantIds)
            ->with('source', 'variant')
            ->orderByDesc('observed_at')
            ->limit($limit)
            ->get();
    }

    private function itemDataStatus(SeoItem $item, Collection $sourcePool): array
    {
        $currentValues = $item->variants->flatMap(fn ($variant) => $variant->currentValues);
        $hasCount = $currentValues->contains(fn ($cv) => $cv->exist_count_normalized !== null);
        $hasValue = $currentValues->contains(fn ($cv) => $cv->value_normalized !== null);
        $hasItemSource = trim((string) $item->source_page_url) !== ''
            || trim((string) $item->wiki_page_url) !== ''
            || $sourcePool->isNotEmpty();

        $label = $hasCount ? 'Count confirmed' : ($hasItemSource ? 'Item source found' : 'Source pending');
        $detail = $hasCount
            ? 'This page has a tracked exist count from the current source dataset.'
            : ($hasItemSource
                ? 'This item has a source page, but no reliable public exist count is available in the current dataset.'
                : 'No source evidence is available for this item in the current dataset.');

        return [
            'label' => $label,
            'detail' => $detail,
            'has_count' => $hasCount,
            'has_value' => $hasValue,
            'has_item_source' => $hasItemSource,
        ];
    }

    /** @return array{description:string,rotRocksDescriptionHtml:string} */
    private function cleanAboutFields(SeoItem $item, ?string $translationDescription): array
    {
        $cleaner = app(SabWikiMarkupCleaner::class);

        return [
            'description' => $cleaner->clean((string) ($translationDescription ?: $item->description)),
            'rotRocksDescriptionHtml' => $cleaner->clean((string) ($item->rot_rocks_description_html ?? '')),
        ];
    }

    private function itemHistory(SeoItem $item): array
    {
        if (! $this->observationsTableExists()) {
            return [];
        }

        $variantIds = $item->variants->pluck('id')->all();
        if (empty($variantIds)) {
            return [];
        }
        return SeoItemObservation::query()
            ->whereIn('seo_item_variant_id', $variantIds)
            ->with('source', 'variant')
            ->orderBy('observed_at')
            ->get()
            ->map(fn ($obs) => [
                'variant_key'            => $obs->variant->variant_key ?? '',
                'source'                 => $obs->source->slug ?? '',
                'observed_at'            => $obs->observed_at->toIso8601String(),
                'exist_count_normalized' => $obs->exist_count_normalized,
                'value_normalized'       => $obs->value_normalized,
                'demand'                 => $obs->demand,
            ])
            ->all();
    }

    private function hreflangLinks(string $file, string $baseUrl, array $locales): array
    {
        $links = [];
        foreach ($locales as $locale) {
            $links[self::localeHreflang($locale)] = $this->localePublicUrl($baseUrl, $locale, $file);
        }
        $links['x-default'] = $this->localePublicUrl($baseUrl, self::DEFAULT_LOCALE, $file);

        return $links;
    }

    /**
     * @return list<array{locale: string, label: string, href: string, active: bool}>
     */
    private function languageLinks(string $urlPrefix, string $currentLocale, string $pageSlug): array
    {
        $isPreview = str_starts_with($urlPrefix, '/seo/sab/preview');

        return array_map(function (string $locale) use ($currentLocale, $pageSlug, $isPreview): array {
            if ($isPreview) {
                $href = self::localizedPreviewPath($locale, $pageSlug);
            } else {
                $prefix = $locale === self::DEFAULT_LOCALE ? '' : "/{$locale}";
                $href = $pageSlug === 'index' ? ($prefix === '' ? '/' : $prefix) : "{$prefix}/{$pageSlug}";
            }

            return [
                'locale' => $locale,
                'label' => self::localeLabel($locale),
                'href' => $href,
                'active' => $locale === $currentLocale,
            ];
        }, self::MULTILINGUAL_PAGE_LOCALES);
    }

    private function productUrlPrefix(string $urlPrefix): string
    {
        if (str_starts_with($urlPrefix, '/seo/sab/preview')) {
            return '/seo/sab/preview';
        }

        return '';
    }

    private function itemDefaultSeoTitle(SeoItem $item, string $displayName): string
    {
        $hasValue = $this->itemHasValueSignal($item);
        $hasRarity = self::canonicalRarityKey($item->rarity ?? null) !== '';

        if ($hasValue && $hasRarity) {
            return "{$displayName} Exist Count, Value & Rarity 2026 | Steal a Brainrot";
        }

        if ($hasRarity) {
            return "{$displayName} Exist Count & Rarity 2026 | Steal a Brainrot";
        }

        if ($hasValue) {
            return "{$displayName} Exist Count & Value 2026 | Steal a Brainrot";
        }

        return "{$displayName} Exist Count 2026 | Steal a Brainrot";
    }

    private function itemDefaultSeoDescription(SeoItem $item, string $displayName): string
    {
        $details = [];

        if ($item->total_exists !== null || $this->itemHasEstimatedExistCount($item)) {
            $details[] = 'latest exist count';
        }

        if (self::canonicalRarityKey($item->rarity ?? null) !== '') {
            $details[] = 'rarity';
        }

        if ($this->itemHasValueSignal($item)) {
            $details[] = 'value signal';
        }

        if (trim((string) ($item->local_image_url ?? $item->image_url ?? '')) !== '') {
            $details[] = 'image';
        }

        if ($this->itemHasVariantType($item, 'mutation')) {
            $details[] = 'mutations';
        }

        if ($this->itemHasVariantType($item, 'trait')) {
            $details[] = 'traits';
        }

        $details = array_values(array_unique($details));
        if ($details === []) {
            return "Check {$displayName} in Steal a Brainrot. Track available item details, update history, and future SAB exist count data on this page.";
        }

        return "Check {$displayName} in Steal a Brainrot: " . implode(', ', $details) . ', and whether it is worth trading or collecting.';
    }

    private function calculatorItemSeoTitle(string $displayName, ?array $brainrotData): string
    {
        $parts = [];
        if (($brainrotData['baseIncome'] ?? 0) > 0) {
            $parts[] = 'Income';
        }
        if (($brainrotData['robuxValue'] ?? null) !== null) {
            $parts[] = 'Value';
        }
        if ($parts === []) {
            return "{$displayName} 2026 | SAB Calculator";
        }
        return "{$displayName} " . implode(' & ', $parts) . " 2026 | SAB Calculator";
    }

    private function calculatorItemSeoDescription(string $displayName, ?array $brainrotData): string
    {
        $parts = [];
        if (($brainrotData['baseIncome'] ?? 0) > 0) {
            $parts[] = 'income per second';
        }
        if (($brainrotData['robuxValue'] ?? null) !== null) {
            $parts[] = 'ROBUX value';
        }
        if (($brainrotData['demand'] ?? '') !== '') {
            $parts[] = 'demand';
        }
        $mutCount = count(array_filter($brainrotData['mutations'] ?? [], fn ($m) => ($m['id'] ?? '') !== 'base'));
        if ($mutCount > 0) {
            $parts[] = 'mutation values';
        }
        if ($parts === []) {
            return "Check {$displayName} stats in Steal a Brainrot on SAB Calculator.";
        }
        return "Check {$displayName} in Steal a Brainrot: " . implode(', ', $parts) . ', 30D price history, and trade signals. Use the calculator to compare brainrot values.';
    }

    /**
     * Snapshot + FAQ share this resolver: ROBUX first, then coins/income, else calculator.
     *
     * @return array{kind: 'robux'|'coins'|'calculator', amount: float|null, unit: string|null}
     */
    public static function itemValueDisplay(SeoItem $item, mixed $baseValueCv = null, mixed $extraRobux = null): array
    {
        $normalized = is_object($baseValueCv) ? $baseValueCv->value_normalized : null;
        if ($normalized !== null && is_numeric($normalized) && (float) $normalized > 0) {
            $unit = trim((string) ($baseValueCv->currency ?? '')) ?: 'ROBUX';

            return [
                'kind' => 'robux',
                'amount' => (float) $normalized,
                'unit' => $unit,
            ];
        }

        if ($extraRobux !== null && $extraRobux !== '' && is_numeric($extraRobux) && (float) $extraRobux > 0) {
            return [
                'kind' => 'robux',
                'amount' => (float) $extraRobux,
                'unit' => 'ROBUX',
            ];
        }

        $rotRobux = data_get($item->attributes_json, 'rot_rocks.robux_value');
        if ($rotRobux !== null && $rotRobux !== '' && is_numeric($rotRobux) && (float) $rotRobux > 0) {
            return [
                'kind' => 'robux',
                'amount' => (float) $rotRobux,
                'unit' => 'ROBUX',
            ];
        }

        $coins = self::parseItemCoinsValue($item);
        if ($coins !== null) {
            return [
                'kind' => 'coins',
                'amount' => (float) $coins,
                'unit' => 'coins',
            ];
        }

        return [
            'kind' => 'calculator',
            'amount' => null,
            'unit' => null,
        ];
    }

    public static function parseItemCoinsValue(SeoItem $item): ?int
    {
        $raw = trim((string) ($item->avg_coins_raw ?? ''));
        $clean = str_replace([',', ' '], '', $raw);
        if ($clean !== '' && ctype_digit($clean) && (int) $clean > 0) {
            return (int) $clean;
        }

        $income = data_get($item->attributes_json, 'rot_rocks.base_income');
        if (is_numeric($income) && (float) $income > 0) {
            return (int) $income;
        }

        return null;
    }

    public static function itemDemandDisplay(SeoItem $item, mixed $baseValueCv = null): string
    {
        $demand = is_object($baseValueCv) ? trim((string) ($baseValueCv->demand ?? '')) : '';
        if ($demand !== '') {
            return $demand;
        }

        return trim((string) data_get($item->attributes_json, 'rot_rocks.demand', ''));
    }

    private function itemHasValueSignal(SeoItem $item): bool
    {
        foreach ($item->variants as $variant) {
            foreach ($variant->currentValues as $currentValue) {
                if ($currentValue->value_normalized !== null || trim((string) $currentValue->demand) !== '') {
                    return true;
                }
            }
        }

        return self::itemValueDisplay($item)['kind'] !== 'calculator';
    }

    private function itemHasVariantType(SeoItem $item, string $type): bool
    {
        return $item->variants->contains(fn ($variant) => ($variant->variant_type ?? '') === $type);
    }

    private function itemHasEstimatedExistCount(SeoItem $item): bool
    {
        $display = self::resolveExistCountDisplay($item, $item->total_exists !== null ? (int) $item->total_exists : null);

        return ($display['kind'] ?? '') === 'estimated';
    }

    private function wikiPageJsonLd(
        string $title,
        string $description,
        string $url,
        array $rows,
        array $faqItems,
        ?Carbon $updatedAt,
        array $crumbs = [],
        string $itemListName = 'All Steal a Brainrot Brainrots',
        bool $hubList = false,
        ?array $article = null,
        array $eventSchemas = [],
        ?string $pageSchemaType = null,
    ): string {
        $siteBase = preg_replace('#/(wiki(?:/.*)?|all-[a-z0-9-]+)$#', '', $url) ?: $url;
        $listRows = array_values($rows);
        $crumbItems = $crumbs === []
            ? [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $siteBase.'/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Steal a Brainrot Wiki', 'item' => $url],
            ]
            : array_map(fn (array $crumb, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['href'] === '/' ? $siteBase.'/' : (str_starts_with((string) $crumb['href'], 'http')
                    ? $crumb['href']
                    : $siteBase.((string) $crumb['href'] === '' ? '/' : $crumb['href'])),
            ], $crumbs, array_keys($crumbs));
        $graph = [
            [
                '@type' => $pageSchemaType ?: 'CollectionPage',
                '@id' => $url.'#page',
                'url' => $url,
                'name' => $title,
                'description' => $description,
                'inLanguage' => 'en',
                'dateModified' => $updatedAt?->toIso8601String(),
                'mainEntity' => ['@id' => $url.'#brainrots'],
            ],
            [
                '@type' => 'BreadcrumbList',
                '@id' => $url.'#breadcrumbs',
                'itemListElement' => $crumbItems,
            ],
            [
                '@type' => 'ItemList',
                '@id' => $url.'#brainrots',
                'name' => $itemListName,
                'numberOfItems' => count($rows),
                'itemListElement' => array_map(
                    fn (array $row, int $index): array => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $row['name'],
                        'url' => $row['url'] ?? ($hubList
                            ? rtrim($siteBase, '/').'/'.$row['slug']
                            : rtrim($siteBase, '/').'/products/'.$row['slug']),
                    ],
                    $listRows,
                    array_keys($listRows),
                ),
            ],
        ];

        if ($faqItems !== []) {
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => $url.'#faq-schema',
                'mainEntity' => array_map(fn (array $faq): array => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq['answer'],
                    ],
                ], $faqItems),
            ];
        }

        if ($article !== null) {
            $graph[] = [
                '@type' => 'Article',
                '@id' => $url.'#article',
                'headline' => $article['headline'],
                'description' => $article['description'],
                'author' => ['@type' => 'Organization', 'name' => 'SABExistCount'],
                'publisher' => ['@type' => 'Organization', 'name' => 'SABExistCount'],
                'mainEntityOfPage' => $url,
                'dateModified' => $article['dateModified'] ?? $updatedAt?->toDateString(),
                'keywords' => $article['keywords'] ?? '',
            ];
        }

        foreach ($eventSchemas as $eventSchema) {
            if (is_array($eventSchema) && ($eventSchema['@type'] ?? null) === 'Event') {
                $graph[] = $eventSchema;
            }
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];

        return '<script type="application/ld+json">'.json_encode(
            $schema,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ).'</script>';
    }


    private function websiteJsonLd(string $baseUrl, string $description): string
    {
        $schema = ['@context' => 'https://schema.org', '@type' => 'WebSite',
                   'name' => 'SAB Exist Count', 'url' => rtrim($baseUrl, '/'), 'description' => $description];
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    private function homeJsonLd(string $title, string $desc, string $url, string $locale, array $t): string
    {
        $faqItems = array_map(fn ($qa) => [
            '@type' => 'Question',
            'name'  => $qa[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($qa[1])],
        ], $t['faq_items'] ?? []);

        $blocks = [
            ['@context' => 'https://schema.org', '@type' => 'WebPage',
             'name' => $title, 'url' => $url, 'description' => $desc,
             'dateModified' => date('Y-m-d'), 'inLanguage' => $locale],
        ];
        if (!empty($faqItems)) {
            $blocks[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage',
                         'mainEntity' => $faqItems];
        }
        return implode("\n", array_map(
            fn ($b) => '<script type="application/ld+json">' . json_encode($b, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>',
            $blocks
        ));
    }

    /**
     * @param  list<array{question: string, answer: string}>  $faqItems
     */
    private function gamePageJsonLd(
        string $title,
        string $description,
        string $url,
        string $baseUrl,
        array $faqItems,
        string $gameName,
        string $gamesIndexUrl,
        string $slug,
        string $coverSrc,
    ): string {
        $blocks = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $title,
                'description' => $description,
                'url' => $url,
                'inLanguage' => 'en',
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Home',
                        'item' => $this->localePublicUrl($baseUrl, self::DEFAULT_LOCALE, 'index.html'),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'Games',
                        'item' => $gamesIndexUrl,
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => $gameName,
                        'item' => $url,
                    ],
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(static fn (array $faq): array => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq['answer'],
                    ],
                ], $faqItems),
            ],
        ];

        if ($slug === self::PAGE_GAME_ROB) {
            array_splice($blocks, 1, 0, [[
                '@context' => 'https://schema.org',
                '@type' => 'VideoGame',
                'name' => $gameName,
                'url' => $url,
                'description' => $description,
                'image' => rtrim($baseUrl, '/') . $coverSrc,
                'gamePlatform' => 'Web browser',
                'applicationCategory' => 'Game',
                'operatingSystem' => 'Any',
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'USD',
                    'availability' => 'https://schema.org/InStock',
                ],
            ]]);
        }

        return implode("\n", array_map(
            static fn (array $block): string => '<script type="application/ld+json">'
                . json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                . '</script>',
            $blocks
        ));
    }

    /**
     * @param  list<array{name: string, href: string}>  $cards
     * @param  list<array{question: string, answer: string}>  $faqItems
     */
    private function gamesHubJsonLd(
        string $title,
        string $description,
        string $url,
        string $baseUrl,
        array $cards,
        array $faqItems,
    ): string {
        $blocks = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $title,
                'description' => $description,
                'url' => $url,
                'inLanguage' => 'en',
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Home',
                        'item' => $this->localePublicUrl($baseUrl, self::DEFAULT_LOCALE, 'index.html'),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'Games',
                        'item' => $url,
                    ],
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => 'Brainrot Games',
                'itemListElement' => array_values(array_map(function (array $card, int $index) use ($baseUrl): array {
                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $card['name'],
                        'url' => $this->localePublicUrl($baseUrl, self::DEFAULT_LOCALE, self::gamePublicPath((string) $card['slug'])),
                    ];
                }, $cards, array_keys($cards))),
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(static fn (array $faq): array => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => trim(strip_tags($faq['answer'])),
                    ],
                ], $faqItems),
            ],
        ];

        return implode("\n", array_map(
            static fn (array $block): string => '<script type="application/ld+json">'
                . json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                . '</script>',
            $blocks
        ));
    }

    /**
     * @param  list<array{question: string, answer: string}>  $faqItems
     */
    private function codesPageJsonLd(
        string $title,
        string $description,
        string $url,
        string $dateModified,
        array $faqItems,
        string $baseUrl,
        string $locale,
        array $copy,
    ): string {
        $blocks = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $title,
                'description' => $description,
                'url' => $url,
                'dateModified' => $dateModified,
                'inLanguage' => self::localeHreflang($locale),
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => $copy['breadcrumb_home'],
                        'item' => $this->localePublicUrl($baseUrl, $locale, 'index.html'),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $copy['breadcrumb_codes'],
                        'item' => $url,
                    ],
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn (array $faq): array => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq['answer'],
                    ],
                ], $faqItems),
            ],
        ];

        return implode("\n", array_map(
            fn (array $block): string => '<script type="application/ld+json">'
                . json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                . '</script>',
            $blocks,
        ));
    }

    private function itemJsonLd(string $title, string $desc, string $pageUrl, string $homePageUrl, string $breadcrumbCurrentName, array $t): string
    {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebPage',
            'name'        => $title,
            'description' => $desc,
            'url'         => $pageUrl,
            'breadcrumb'  => [
                '@type'           => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => $t['breadcrumb_home'], 'item' => $homePageUrl],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $breadcrumbCurrentName, 'item' => $pageUrl],
                ],
            ],
        ];

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    private function articleJsonLd(SeoNewsArticle $article, string $url, string $locale, string $description, ?string $imageUrl = null): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $description,
            'author' => [
                '@type' => 'Organization',
                'name' => 'SABExistCount.com',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'SABExistCount.com',
                'url' => 'https://sabexistcount.com/',
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $url,
            ],
            'url' => $url,
            'datePublished' => optional($article->published_at ?? $article->created_at)->toIso8601String(),
            'dateModified' => optional($article->updated_at)->toIso8601String(),
            'inLanguage' => $locale,
        ];
        if ($imageUrl) {
            $schema['image'] = [$imageUrl];
        }

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    private function newsIndexWebPageJsonLd(string $title, string $description, string $url, string $locale): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'url' => $url,
            'description' => $description,
            'inLanguage' => $locale,
        ];

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    /**
     * @param  Collection<int, SeoNewsArticle>  $articles
     */
    private function newsIndexViewPayload(Collection $articles, string $urlPrefix, string $locale, string $baseUrl, array $t, string $cssHref): array
    {
        $listUrl = $this->localePublicUrl($baseUrl, $locale, 'news/index.html');
        $coreTitle = trim((string) ($t['news_list_meta_title'] ?? 'News'));
        $siteBrand = trim((string) ($t['site_name'] ?? 'SAB Exist Count'));
        $seoTitle = $coreTitle !== '' ? "{$coreTitle} | {$siteBrand}" : $siteBrand;
        $seoDescription = $t['news_list_meta_description'] ?? '';

        return [
            'locale' => $locale,
            'urlPrefix' => $urlPrefix,
            'baseUrl' => $baseUrl,
            't' => $t,
            'articles' => $articles,
            'canonical' => $listUrl,
            'hreflangLinks' => [],
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'websiteJsonLd' => $this->websiteJsonLd($baseUrl, $seoDescription),
            'jsonLd' => $this->newsIndexWebPageJsonLd($seoTitle, $seoDescription, $listUrl, $locale),
            'cssHref' => $cssHref,
            'htmlNewsLinks' => ! str_starts_with($urlPrefix, '/seo/sab/preview'),
        ];
    }

    private function newsViewPayload(SeoNewsArticle $article, string $urlPrefix, string $locale, string $baseUrl, array $t, string $cssHref): array
    {
        $articleUrl = $this->localePublicUrl($baseUrl, $locale, "news/{$article->slug}.html");
        $seoTitle = $article->meta_title ?: "{$article->title} | SABExistCount.com";
        $seoDescription = $article->meta_description ?: ($article->excerpt ?: 'SAB Exist Count news and updates.');
        $coverImageUrl = $this->assetPublicUrl($baseUrl, (string) $article->cover_image_url);
        $coverImageSrc = $this->normalizeNewsImagePath((string) $article->cover_image_url);
        $defaultOgImage = 'https://sabexistcount.com/uploads/images/sab/og-image.png';
        $htmlNewsLinks = ! str_starts_with($urlPrefix, '/seo/sab/preview');
        $bodyHtml = $this->normalizeNewsBodyLinks(
            $this->normalizeNewsBodyImagePaths((string) $article->body_html),
            $urlPrefix,
        );
        $decorated = $this->decorateNewsBodyWithHeadingIds($bodyHtml);

        return [
            'locale' => $locale,
            'urlPrefix' => $urlPrefix,
            'baseUrl' => $baseUrl,
            't' => $t,
            'article' => $article,
            'canonical' => $articleUrl,
            'hreflangLinks' => [],
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'coverImageUrl' => $coverImageUrl,
            'coverImageSrc' => $coverImageSrc,
            'bodyHtml' => $decorated['html'],
            'toc' => $decorated['toc'],
            'ogImage' => $coverImageUrl ?: $defaultOgImage,
            'websiteJsonLd' => $this->websiteJsonLd($baseUrl, $seoDescription),
            'jsonLd' => $this->articleJsonLd($article, $articleUrl, $locale, $seoDescription, $coverImageUrl ?: null),
            'cssHref' => $cssHref,
            'htmlNewsLinks' => $htmlNewsLinks,
        ];
    }

    /**
     * @return array{html: string, toc: list<array{id: string, text: string, level: int}>}
     */
    private function decorateNewsBodyWithHeadingIds(string $html): array
    {
        if (trim($html) === '') {
            return ['html' => $html, 'toc' => []];
        }

        $usedIds = [];
        if (preg_match_all('/\bid=["\']([^"\']+)["\']/i', $html, $idMatches)) {
            foreach ($idMatches[1] as $existing) {
                $usedIds[$existing] = true;
            }
        }

        $toc = [];
        $decorated = preg_replace_callback(
            '/<(h2|h3)\b([^>]*)>(.*?)<\/\1>/is',
            function (array $matches) use (&$usedIds, &$toc): string {
                $tag = strtolower($matches[1]);
                $attrs = $matches[2];
                $inner = $matches[3];
                $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
                if ($text === '') {
                    return $matches[0];
                }

                $id = '';
                if (preg_match('/\bid=["\']([^"\']+)["\']/i', $attrs, $idMatch) === 1) {
                    $id = $idMatch[1];
                }
                if ($id === '') {
                    $base = Str::slug($text);
                    if ($base === '') {
                        $base = 'section';
                    }
                    $id = $base;
                    $suffix = 2;
                    while (isset($usedIds[$id])) {
                        $id = $base.'-'.$suffix;
                        $suffix++;
                    }
                    $attrs .= ' id="'.e($id).'"';
                }
                $usedIds[$id] = true;
                $toc[] = [
                    'id' => $id,
                    'text' => $text,
                    'level' => $tag === 'h3' ? 3 : 2,
                ];

                return '<'.$tag.$attrs.'>'.$inner.'</'.$tag.'>';
            },
            $html
        ) ?? $html;

        return ['html' => $decorated, 'toc' => $toc];
    }

    private function normalizeNewsBodyLinks(string $html, string $urlPrefix): string
    {
        if ($html === '') {
            return '';
        }

        $prefix = rtrim($urlPrefix, '/');
        $html = preg_replace_callback(
            '/(<a\b[^>]*\bhref=["\'])\/(news|products)\/([A-Za-z0-9._-]+)(?:\.html)?(["\'][^>]*>)/i',
            function (array $matches) use ($prefix): string {
                $section = $matches[2];
                $slug = $matches[3];
                $href = "{$prefix}/{$section}/{$slug}";

                return $matches[1] . $href . $matches[4];
            },
            $html
        ) ?? $html;

        $gameSlugPattern = self::gameSlugPattern();

        return preg_replace_callback(
            '/(<a\b[^>]*\bhref=["\'])\/games(?:\/(' . $gameSlugPattern . '))?(?:\.html)?(["\'][^>]*>)/i',
            function (array $matches) use ($prefix): string {
                $slug = $matches[2] ?? '';
                $href = "{$prefix}/games" . ($slug !== '' ? "/{$slug}" : '');

                return $matches[1] . $href . $matches[3];
            },
            $html
        ) ?? $html;
    }

    private function normalizeNewsBodyImagePaths(string $html): string
    {
        if ($html === '') {
            return '';
        }

        return preg_replace_callback(
            '/(<img\b[^>]*\bsrc=["\'])([^"\']+)(["\'][^>]*>)/i',
            fn (array $matches) => $matches[1] . $this->normalizeNewsImagePath($matches[2]) . $matches[3],
            $html
        ) ?? $html;
    }

    private function normalizeNewsImagePath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || preg_match('/^(?:https?:)?\/\//i', $path) || str_starts_with($path, 'data:')) {
            return $path;
        }
        if (str_starts_with($path, '/')) {
            return $path;
        }
        if (str_starts_with($path, 'uploads/images/sab/news/')) {
            return "/{$path}";
        }

        return $path;
    }

    private function assetPublicUrl(string $baseUrl, string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Absolute URL on the live site for canonical/hreflang/sitemap (no `.html`; home is `{base}` or `{base}/{locale}`, no trailing slash).
     */
    private function localePublicUrl(string $baseUrl, string $locale, string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/');
        if ($relativePath === 'index.html') {
            $relativePath = '';
        } elseif ($relativePath !== '' && str_ends_with($relativePath, '.html')) {
            $relativePath = substr($relativePath, 0, -strlen('.html'));
        }

        // e.g. news/index → news (preferred public URL segment)
        if ($relativePath !== '' && str_ends_with($relativePath, '/index')) {
            $relativePath = substr($relativePath, 0, -strlen('/index'));
        }

        $seg = $locale === self::DEFAULT_LOCALE ? '' : "/{$locale}";
        $base = rtrim($baseUrl, '/');

        if ($relativePath === '') {
            return "{$base}{$seg}";
        }

        return "{$base}{$seg}/{$relativePath}";
    }

    /**
     * Filesystem directory used for `seo:sab-render` output. CLI override wins over DB `output_path`.
     */
    public function filesystemOutputForRender(SeoSite $site, ?string $cliPathOverride): string
    {
        $o = $cliPathOverride !== null ? trim($cliPathOverride) : '';
        if ($o !== '') {
            return str_starts_with($o, '/') ? $o : base_path(ltrim($o, '/'));
        }

        return $this->resolveOutputPath($site);
    }

    private function resolveOutputPath(SeoSite $site): string
    {
        $path = SabSiteContext::expandHomePath(trim((string) $site->output_path));
        if ($path === '') {
            $fallback = $site->slug === self::SITE_SLUG_SAB_CALCULATOR
                ? SabSiteContext::expandHomePath('~/project-seo/seo-sabcalculator')
                : base_path('website/' . self::SITE_SLUG);

            return $fallback;
        }

        return str_starts_with($path, '/') ? $path : base_path(ltrim($path, '/'));
    }

    private function ensureDir(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    private function removeUnsupportedLocaleArtifacts(string $localeDir): void
    {
        foreach (['products', 'news'] as $dirName) {
            $this->removePath("{$localeDir}/{$dirName}");
        }

        foreach ([
            self::PAGE_EXIST_COUNT_GALLERY . '.html',
            'about-us.html',
            'privacy-policy.html',
            'terms-of-service.html',
        ] as $fileName) {
            $this->removePath("{$localeDir}/{$fileName}");
        }
    }

    private function removePath(string $path): void
    {
        if (! file_exists($path) && ! is_link($path)) {
            return;
        }

        if (is_file($path) || is_link($path)) {
            unlink($path);
            return;
        }

        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $name) {
            $this->removePath("{$path}/{$name}");
        }

        rmdir($path);
    }
}
