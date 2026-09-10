<?php

use App\Http\Controllers\Seo\SabCalculatorCatalogController;
use App\Http\Controllers\Seo\SabPublicController;
use App\Services\Seo\SabRenderService;
use App\Services\Seo\SabWikiPageDefinitions;
use Illuminate\Support\Facades\Route;

$localePattern = implode('|', array_filter(
    SabRenderService::supportedLocales(),
    fn (string $locale): bool => $locale !== SabRenderService::defaultLocale()
));

$consolePath = trim((string) config('sab.console_path'), '/');
if ($consolePath !== '') {
    Route::any('/'.$consolePath.'/{any?}', fn () => abort(404))->where('any', '.*');
}

Route::get('/products/{slug}/price-history.json', [SabPublicController::class, 'itemPriceHistory']);
Route::get('/data/calc/sab/manifest.json', [SabCalculatorCatalogController::class, 'manifest']);
Route::get('/data/calc/sab/releases/{version}/bootstrap.json', [SabCalculatorCatalogController::class, 'bootstrap'])
    ->where('version', '[a-z0-9][a-z0-9-]*');
Route::get('/data/calc/sab/releases/{version}/chunks/{chunk}.json', [SabCalculatorCatalogController::class, 'chunk'])
    ->where([
        'version' => '[a-z0-9][a-z0-9-]*',
        'chunk' => '[0-9]{2}',
    ]);

Route::middleware('cache.public.seo')->group(function () use ($localePattern): void {
    Route::get('/robots.txt', [SabPublicController::class, 'robots']);
    Route::get('/sitemap.xml', [SabPublicController::class, 'sitemap']);
    Route::get('/sitemaps/{name}.xml', [SabPublicController::class, 'sitemapShard'])
        ->where('name', 'main|trading-[1-9][0-9]*|profiles-[1-9][0-9]*');

    Route::get('/', [SabPublicController::class, 'home']);
    Route::get('/sab-exist-count-list', [SabPublicController::class, 'existCountsList']);
    Route::get('/exist-count-gallery', [SabPublicController::class, 'existCountGallery']);
    Route::get('/sab-value-list', [SabPublicController::class, 'valueList']);
    Route::get('/value-changes', [SabPublicController::class, 'valueChanges']);
    Route::get('/wiki/{slug}', [SabPublicController::class, 'wikiNestedTopic'])
        ->where('slug', implode('|', array_map(
            fn (string $slug): string => $slug.'(?:\\.html)?',
            SabWikiPageDefinitions::nestedPreviewSlugs()
        )));
    Route::get('/wiki', [SabPublicController::class, 'wiki']);
    Route::get('/steal-a-brainrot-codes', [SabPublicController::class, 'codes']);
    Route::get('/steal-a-brainrot-trading-calculator', [SabPublicController::class, 'calculator']);
    Route::get('/about-us', [SabPublicController::class, 'staticPage'])->defaults('legalSlug', 'about-us');
    Route::get('/privacy-policy', [SabPublicController::class, 'staticPage'])->defaults('legalSlug', 'privacy-policy');
    Route::get('/terms-of-service', [SabPublicController::class, 'staticPage'])->defaults('legalSlug', 'terms-of-service');
    Route::get('/games', [SabPublicController::class, 'games']);
    Route::get('/games/{slug}', [SabPublicController::class, 'game'])->where('slug', SabRenderService::gameSlugPattern());
    Route::get('/news', [SabPublicController::class, 'newsIndex']);
    Route::get('/news/{slug}', [SabPublicController::class, 'news']);
    Route::get('/products/{slug}', [SabPublicController::class, 'item']);

    Route::get('/{locale}', [SabPublicController::class, 'home'])->where('locale', $localePattern);
    Route::get('/{locale}/sab-exist-count-list', [SabPublicController::class, 'existCountsList'])->where('locale', $localePattern);
    Route::get('/{locale}/sab-value-list', [SabPublicController::class, 'valueList'])->where('locale', $localePattern);
    Route::get('/{locale}/steal-a-brainrot-trading-calculator', [SabPublicController::class, 'calculator'])->where('locale', $localePattern);
    Route::get('/{locale}/steal-a-brainrot-codes', [SabPublicController::class, 'codes'])->where('locale', $localePattern);
    Route::get('/{locale}/games', [SabPublicController::class, 'games'])->where('locale', $localePattern);
    Route::get('/{locale}/games/{slug}', [SabPublicController::class, 'game'])
        ->where(['locale' => $localePattern, 'slug' => SabRenderService::gameSlugPattern()]);
});
