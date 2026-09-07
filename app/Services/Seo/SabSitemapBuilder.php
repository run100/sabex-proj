<?php

namespace App\Services\Seo;

use App\Models\SeoGame;
use App\Models\SeoItem;
use App\Models\SeoSite;
use App\Models\TradeListing;
use App\Models\TradeUser;
use App\Support\TradeIndexEligibility;
use App\Support\TradePaths;
use App\Support\TradeProfileAccess;
use App\Support\TradeSchema;
use Illuminate\Support\Collection;

class SabSitemapBuilder
{
    public const SHARD_SIZE = 50000;

    public function __construct(
        private readonly SabRenderService $render,
    ) {}

    public function indexXml(): string
    {
        $origin = rtrim($this->render->publicWwwOrigin(), '/');
        $locs = [$origin.'/sitemaps/main.xml'];
        $tradingShards = $this->tradingShardCount();
        for ($i = 1; $i <= $tradingShards; $i++) {
            $locs[] = $origin.'/sitemaps/trading-'.$i.'.xml';
        }
        $profileShards = $this->profileShardCount();
        for ($i = 1; $i <= $profileShards; $i++) {
            $locs[] = $origin.'/sitemaps/profiles-'.$i.'.xml';
        }

        return $this->indexDocument($locs);
    }

    public function shardXml(string $name): string
    {
        if ($name === 'main') {
            return $this->urlset($this->mainUrls());
        }
        if (preg_match('/^trading-([1-9][0-9]*)$/', $name, $m)) {
            return $this->urlset($this->tradingUrls((int) $m[1]));
        }
        if (preg_match('/^profiles-([1-9][0-9]*)$/', $name, $m)) {
            return $this->urlset($this->profileUrls((int) $m[1]));
        }

        abort(404);
    }

    /**
     * @return list<array{path: string, body: string}>
     */
    public function staticFiles(): array
    {
        $files = [
            ['path' => 'sitemap.xml', 'body' => $this->indexXml()],
            ['path' => 'sitemaps/main.xml', 'body' => $this->urlset($this->mainUrls())],
        ];
        $tradingShards = $this->tradingShardCount();
        for ($i = 1; $i <= $tradingShards; $i++) {
            $files[] = ['path' => 'sitemaps/trading-'.$i.'.xml', 'body' => $this->urlset($this->tradingUrls($i))];
        }
        $profileShards = $this->profileShardCount();
        for ($i = 1; $i <= $profileShards; $i++) {
            $files[] = ['path' => 'sitemaps/profiles-'.$i.'.xml', 'body' => $this->urlset($this->profileUrls($i))];
        }

        return $files;
    }

    /**
     * @return list<array{loc: string, lastmod?: string, priority?: string}>
     */
    public function mainUrls(): array
    {
        $baseUrl = rtrim($this->render->publicWwwOrigin(), '/');
        $urls = [];
        try {
            $site = SeoSite::query()->where('slug', SabRenderService::SITE_SLUG)->first();
            $game = $site
                ? SeoGame::query()->where('seo_site_id', $site->id)->where('slug', SabRenderService::sitemapGameSlug())->first()
                : null;
            $items = $game ? $this->render->loadItemsForSitemap($game) : collect();
            $news = $site ? $this->render->loadPublishedNewsForSitemap($site) : collect();
            $staticPages = $site ? $this->render->loadPublishedStaticPagesForSitemap($site) : collect();
            $codesLastmod = null;
            try {
                $codesLastmod = (string) $this->render->codesDataForSitemap()['verified_at'];
            } catch (\Throwable) {
                $codesLastmod = null;
            }

            foreach (SabRenderService::HOME_LOCALES as $locale) {
                $urls[] = ['loc' => $this->render->localePublicUrlForSitemap($baseUrl, $locale, 'index.html'), 'priority' => '1.0'];
                $urls[] = ['loc' => $this->render->localePublicUrlForSitemap($baseUrl, $locale, SabRenderService::PAGE_EXIST_COUNTS_LIST), 'priority' => '0.85'];
                $urls[] = ['loc' => $this->render->localePublicUrlForSitemap($baseUrl, $locale, SabRenderService::PAGE_VALUE_LIST), 'priority' => '0.85'];
                $urls[] = ['loc' => $this->render->localePublicUrlForSitemap($baseUrl, $locale, SabRenderService::PAGE_TRADING_CALCULATOR), 'priority' => '0.85'];
                $row = [
                    'loc' => $this->render->localePublicUrlForSitemap($baseUrl, $locale, SabRenderService::PAGE_CODES),
                    'priority' => '0.9',
                ];
                if ($codesLastmod) {
                    $row['lastmod'] = $codesLastmod;
                }
                $urls[] = $row;
                $urls[] = ['loc' => $this->render->localePublicUrlForSitemap($baseUrl, $locale, SabRenderService::gamesIndexPublicPath()), 'priority' => '0.75'];
                foreach (array_keys(SabRenderService::gamesCatalog()) as $gameSlug) {
                    $urls[] = ['loc' => $this->render->localePublicUrlForSitemap($baseUrl, $locale, SabRenderService::gamePublicPath($gameSlug)), 'priority' => '0.7'];
                }
            }

            $en = SabRenderService::sitemapDefaultLocale();
            $urls[] = ['loc' => $this->render->localePublicUrlForSitemap($baseUrl, $en, SabRenderService::PAGE_EXIST_COUNT_GALLERY), 'priority' => '0.8'];
            $urls[] = ['loc' => $this->render->localePublicUrlForSitemap($baseUrl, $en, SabRenderService::PAGE_VALUE_CHANGES), 'priority' => '0.85'];
            $urls[] = ['loc' => $this->render->localePublicUrlForSitemap($baseUrl, $en, SabRenderService::PAGE_WIKI), 'priority' => '0.85'];
            foreach (SabWikiPageDefinitions::shippingPageSlugs() as $slug) {
                $urls[] = ['loc' => $this->render->localePublicUrlForSitemap($baseUrl, $en, $slug), 'priority' => '0.8'];
            }
            $newsLastmod = optional($news->where('locale', $en)->sortByDesc('updated_at')->first())->updated_at?->toDateString();
            $newsRow = ['loc' => $this->render->localePublicUrlForSitemap($baseUrl, $en, 'news'), 'priority' => '0.75'];
            if ($newsLastmod) {
                $newsRow['lastmod'] = $newsLastmod;
            }
            $urls[] = $newsRow;

            foreach ($items->filter(fn (SeoItem $item) => SabRenderService::sitemapShouldRenderProduct($item) && SabRenderService::sitemapShouldIndexProductSlug((string) $item->slug)) as $item) {
                $row = [
                    'loc' => $this->render->localePublicUrlForSitemap($baseUrl, $en, 'products/'.SabRenderService::productPublicSlug($item->slug)),
                    'priority' => '0.8',
                ];
                if ($item->updated_at) {
                    $row['lastmod'] = $item->updated_at->toDateString();
                }
                $urls[] = $row;
            }

            foreach ($news->where('locale', $en) as $article) {
                $row = [
                    'loc' => $this->render->localePublicUrlForSitemap($baseUrl, $en, 'news/'.$article->slug),
                    'priority' => '0.7',
                ];
                if ($article->updated_at) {
                    $row['lastmod'] = $article->updated_at->toDateString();
                }
                $urls[] = $row;
            }

            $legalSlugs = ['about-us', 'privacy-policy', 'terms-of-service'];
            $staticBySlug = $staticPages->keyBy('slug');
            foreach ($legalSlugs as $legalSlug) {
                $article = $staticBySlug->get($legalSlug);
                $row = [
                    'loc' => $this->render->localePublicUrlForSitemap($baseUrl, $en, $legalSlug),
                    'priority' => '0.5',
                ];
                if ($article?->updated_at) {
                    $row['lastmod'] = $article->updated_at->toDateString();
                }
                $urls[] = $row;
            }
        } catch (\Throwable) {
            $urls[] = ['loc' => $baseUrl, 'priority' => '1.0'];
        }

        foreach ([
            TradePaths::marketplace(),
            TradePaths::create(),
            TradePaths::pending(),
            TradePaths::completed(),
        ] as $path) {
            $urls[] = ['loc' => $baseUrl.$path, 'priority' => '0.8'];
        }

        return $urls;
    }

    /**
     * @return list<array{loc: string, lastmod?: string, priority?: string}>
     */
    public function tradingUrls(int $shard): array
    {
        if (! TradeSchema::ready() || $shard < 1) {
            return [];
        }
        $origin = rtrim($this->render->publicWwwOrigin(), '/');
        $offset = ($shard - 1) * self::SHARD_SIZE;
        $rows = $this->indexableListingsQuery()
            ->orderBy('id')
            ->offset($offset)
            ->limit(self::SHARD_SIZE)
            ->get(['id', 'public_id', 'status', 'created_at', 'pending_at', 'completed_at']);

        return $rows->map(function (TradeListing $listing) use ($origin): array {
            $lastmod = match ($listing->status) {
                TradeListing::STATUS_PENDING, TradeListing::STATUS_PENDING_CONFIRMATION => optional($listing->pending_at)->toDateString()
                    ?: optional($listing->created_at)->toDateString(),
                TradeListing::STATUS_COMPLETED => optional($listing->completed_at)->toDateString()
                    ?: optional($listing->created_at)->toDateString(),
                default => optional($listing->created_at)->toDateString(),
            };
            $row = [
                'loc' => $origin.TradePaths::show($listing->public_id),
                'priority' => '0.6',
            ];
            if ($lastmod) {
                $row['lastmod'] = $lastmod;
            }

            return $row;
        })->all();
    }

    /**
     * @return list<array{loc: string, lastmod?: string, priority?: string}>
     */
    public function profileUrls(int $shard): array
    {
        if (! TradeSchema::ready() || $shard < 1) {
            return [];
        }
        $origin = rtrim($this->render->publicWwwOrigin(), '/');
        $offset = ($shard - 1) * self::SHARD_SIZE;
        $users = TradeUser::query()
            ->where('account_status', TradeUser::STATUS_ACTIVE)
            ->where('profile_visibility', TradeUser::VISIBILITY_PUBLIC)
            ->where('moderation_status', '!=', TradeUser::MODERATION_RESTRICTED)
            ->orderBy('id')
            ->offset($offset)
            ->limit(self::SHARD_SIZE)
            ->get();

        return $users
            ->filter(fn (TradeUser $user) => TradeIndexEligibility::isEligible($user))
            ->map(fn (TradeUser $user): array => [
                'loc' => $origin.$user->profilePath(),
                'priority' => '0.5',
            ])
            ->values()
            ->all();
    }

    private function tradingShardCount(): int
    {
        if (! TradeSchema::ready()) {
            return 0;
        }
        $count = $this->indexableListingsQuery()->count();

        return $count > 0 ? (int) ceil($count / self::SHARD_SIZE) : 0;
    }

    private function profileShardCount(): int
    {
        if (! TradeSchema::ready()) {
            return 0;
        }
        $count = TradeUser::query()
            ->where('account_status', TradeUser::STATUS_ACTIVE)
            ->where('profile_visibility', TradeUser::VISIBILITY_PUBLIC)
            ->where('moderation_status', '!=', TradeUser::MODERATION_RESTRICTED)
            ->count();

        return $count > 0 ? (int) ceil($count / self::SHARD_SIZE) : 0;
    }

    private function indexableListingsQuery()
    {
        return TradeListing::query()
            ->whereIn('status', [
                TradeListing::STATUS_OPEN,
                TradeListing::STATUS_PENDING,
                TradeListing::STATUS_PENDING_CONFIRMATION,
                TradeListing::STATUS_COMPLETED,
            ])
            ->whereHas('owner', fn ($q) => TradeProfileAccess::constrainPublicIdentity($q))
            ->where(function ($q): void {
                $q->whereNull('counterparty_user_id')
                    ->orWhereHas('counterparty', fn ($inner) => TradeProfileAccess::constrainPublicIdentity($inner));
            });
    }

    /**
     * @param  list<string>  $locs
     */
    private function indexDocument(array $locs): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($locs as $loc) {
            $xml .= '  <sitemap><loc>'.htmlspecialchars($loc, ENT_XML1).'</loc></sitemap>'."\n";
        }
        $xml .= '</sitemapindex>';

        return $xml;
    }

    /**
     * @param  list<array{loc: string, lastmod?: string, priority?: string}>  $urls
     */
    private function urlset(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n    <loc>".htmlspecialchars((string) $u['loc'], ENT_XML1)."</loc>\n";
            if (! empty($u['lastmod'])) {
                $xml .= '    <lastmod>'.$u['lastmod']."</lastmod>\n";
            }
            if (! empty($u['priority'])) {
                $xml .= '    <priority>'.$u['priority']."</priority>\n";
            }
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        return $xml;
    }
}
