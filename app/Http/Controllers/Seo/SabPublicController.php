<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Services\Seo\SabRenderService;
use App\Services\Seo\SabWikiPageDefinitions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SabPublicController extends Controller
{
    public function home(Request $request, SabRenderService $sabRender, ?string $locale = null): View
    {
        return view('seo.sab.home', $sabRender->homeViewContext($this->locale($request, $locale)));
    }

    public function existCountsList(Request $request, SabRenderService $sabRender, ?string $locale = null): View
    {
        return view('seo.sab.exist-counts-list', $sabRender->existCountsListViewContext($this->locale($request, $locale)));
    }

    public function existCountGallery(Request $request, SabRenderService $sabRender): View
    {
        return view('seo.sab.exist-count-gallery', $sabRender->existCountGalleryViewContext($this->locale($request)));
    }

    public function valueList(Request $request, SabRenderService $sabRender, ?string $locale = null): View
    {
        return view('seo.sab.value-list', $sabRender->valueListViewContext($this->locale($request, $locale)));
    }

    public function valueChanges(SabRenderService $sabRender): View
    {
        return view('seo.sab.value-changes', $sabRender->valueChangesViewContext());
    }

    public function wiki(SabRenderService $sabRender): View
    {
        return view('seo.sab.wiki', $sabRender->wikiViewContext());
    }

    public function wikiNestedTopic(string $slug, SabRenderService $sabRender): View
    {
        $slug = preg_replace('/\.html$/', '', $slug) ?: '';
        $page = 'wiki/'.$slug;
        if (in_array($page, SabWikiPageDefinitions::catalogPageSlugs(), true)) {
            return view('seo.sab.wiki-catalog', $sabRender->wikiCatalogViewContext($page));
        }
        abort_unless(in_array($page, SabWikiPageDefinitions::topicPageSlugs(), true), 404);

        return view('seo.sab.wiki-topic', $sabRender->wikiTopicViewContext($page));
    }

    public function games(Request $request, SabRenderService $sabRender, ?string $locale = null): View
    {
        return view('seo.sab.games', $sabRender->gamesHubViewContext($this->locale($request, $locale)));
    }

    public function game(Request $request, SabRenderService $sabRender, ?string $locale = null): View
    {
        $slug = preg_replace('/\.html$/', '', (string) $request->route('slug')) ?: '';

        return view('seo.sab.game', $sabRender->gameViewContext($slug, $this->locale($request, $locale)));
    }

    public function codes(Request $request, SabRenderService $sabRender, ?string $locale = null): View
    {
        return view('seo.sab.codes', $sabRender->codesViewContext($this->locale($request, $locale)));
    }

    public function calculator(Request $request, SabRenderService $sabRender, ?string $locale = null): View
    {
        return view('seo.sab.calculator', $sabRender->calculatorViewContext($this->locale($request, $locale)));
    }

    public function item(Request $request, string $slug, SabRenderService $sabRender): View
    {
        $slug = preg_replace('/\.html$/', '', $slug);

        return view('seo.sab.item', $sabRender->itemViewContext($slug, $this->locale($request)));
    }

    public function newsIndex(Request $request, SabRenderService $sabRender): View
    {
        return view('seo.sab.news-index', $sabRender->newsListViewContext($this->locale($request)));
    }

    public function news(Request $request, string $slug, SabRenderService $sabRender): View
    {
        $slug = preg_replace('/\.html$/', '', $slug);

        return view('seo.sab.news', $sabRender->newsViewContext($slug, $this->locale($request)));
    }

    public function staticPage(string $legalSlug, SabRenderService $sabRender): View
    {
        abort_unless(in_array($legalSlug, ['about-us', 'privacy-policy', 'terms-of-service'], true), 404);

        return view('seo.sab.static-page', $sabRender->staticPagePreviewContext($legalSlug));
    }

    public function robots(SabRenderService $sabRender): Response
    {
        $template = trim((string) file_get_contents(resource_path('seo/sab/robots.txt.stub')));
        $body = str_replace('{{ base_url }}', $sabRender->publicWwwOrigin(), $template);

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(SabRenderService $sabRender): Response
    {
        return response($sabRender->buildLiveSitemapXml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    public function sitemapShard(string $name, SabRenderService $sabRender): Response
    {
        return response($sabRender->buildLiveSitemapShardXml($name), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function locale(Request $request, ?string $routeLocale = null): string
    {
        return SabRenderService::normalizeLocale($routeLocale ?: (string) $request->query('locale', SabRenderService::defaultLocale()));
    }
}
