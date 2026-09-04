<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Services\Seo\SabRenderService;
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

    public function robots(): Response
    {
        $template = trim((string) file_get_contents(resource_path('seo/sab/robots.txt.stub')));
        $body = str_replace('{{ base_url }}', rtrim((string) config('app.url'), '/'), $template);

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(SabRenderService $sabRender): Response
    {
        $payload = $sabRender->homeViewContext();
        $baseUrl = rtrim((string) ($payload['baseUrl'] ?? config('app.url')), '/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n';
        $xml .= '  <url><loc>'.htmlspecialchars($baseUrl.'/').'</loc></url>\n</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function locale(Request $request, ?string $routeLocale = null): string
    {
        return SabRenderService::normalizeLocale($routeLocale ?: (string) $request->query('locale', SabRenderService::defaultLocale()));
    }
}
