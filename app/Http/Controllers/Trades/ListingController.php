<?php

namespace App\Http\Controllers\Trades;

use App\Exceptions\TradeException;
use App\Http\Controllers\Controller;
use App\Services\Trades\BrainrotCatalogService;
use App\Services\Trades\TradeListingService;
use App\Support\TradeCanonical;
use App\Support\TradePaths;
use App\Support\TradeQueryRules;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingController extends Controller
{
    use TradePageSupport;

    public function __invoke(Request $request, TradeListingService $listings, BrainrotCatalogService $catalog): View
    {
        $filters = $this->validatedQuery($request, TradeQueryRules::recentListings());
        $wantBrainrot = $this->selectedBrainrot($catalog, $filters['want_brainrot_id'] ?? null);
        $haveBrainrot = $this->selectedBrainrot($catalog, $filters['have_brainrot_id'] ?? null);
        if ($wantBrainrot === null) {
            unset($filters['want_brainrot_id']);
        }
        if ($haveBrainrot === null) {
            unset($filters['have_brainrot_id']);
        }

        $page = $this->page([
            'seoTitle' => 'SABExistCount - Steal a Brainrot Trades, Trade Calculator & Values',
            'seoDescription' => 'Find live Steal a Brainrot trades, post or join offers, compare SAB values, calculate W/F/L, and check mutations, traits and exist counts before trading.',
            'seoKeywords' => 'Steal a Brainrot, trade calculator, value list, brainrot values, mutations, traits',
            'listings' => collect(),
            'wantBrainrot' => $wantBrainrot,
            'haveBrainrot' => $haveBrainrot,
        ]);
        if (! $page['schemaMissing']) {
            $listingsPage = $listings->recent($filters);
            $this->assertListPage($request, $listingsPage);
            $page['listings'] = $listingsPage;
        }
        $seo = TradeCanonical::forList(TradePaths::marketplace(), $request);
        $page['canonical'] = $seo['canonical'];
        $page['robots'] = $seo['robots'];

        return view('trades.listings', $page);
    }

    /**
     * @return array{id: int, name: string, image: ?string}|null
     */
    private function selectedBrainrot(BrainrotCatalogService $catalog, mixed $id): ?array
    {
        $id = (int) $id;
        if ($id < 1) {
            return null;
        }

        try {
            $row = $catalog->getBrainrot($id);
        } catch (TradeException) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'image' => isset($row['image']) ? (string) $row['image'] : null,
        ];
    }
}
