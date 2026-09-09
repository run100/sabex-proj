<?php

namespace App\Http\Controllers\Trades\Api;

use App\Exceptions\TradeException;
use App\Http\Controllers\Controller;
use App\Services\Trades\TradeActivityService;
use App\Services\Trades\TradeConfirmationService;
use App\Services\Trades\TradeJoinService;
use App\Services\Trades\TradeListingService;
use App\Services\Trades\TradeModerationService;
use App\Services\Trades\TradeNotificationService;
use App\Support\AccessLogService;
use App\Support\TradeApi;
use App\Support\TradePaths;
use App\Support\TradePresenter;
use App\Support\TradeSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class TradeController extends Controller
{
    public function index(Request $request, TradeListingService $listings): JsonResponse
    {
        $this->requireSchema();
        $page = $listings->recent($request->only([
            'page', 'limit', 'want_brainrot_id', 'have_brainrot_id', 'min_value', 'max_value', 'sort',
        ]));

        return TradeApi::ok([
            'items' => collect($page->items())->map(fn ($row) => TradePresenter::listing($row))->all(),
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ]);
    }

    public function completed(Request $request, TradeListingService $listings): JsonResponse
    {
        $this->requireSchema();
        $page = $listings->completed($request->only([
            'page', 'limit', 'username', 'roblox_sub', 'brainrot_id', 'sort',
        ]));

        return TradeApi::ok([
            'items' => collect($page->items())->map(fn ($row) => TradePresenter::listing($row))->all(),
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ]);
    }

    public function store(Request $request, TradeListingService $listings): JsonResponse
    {
        $this->requireSchema();
        try {
            return $this->idempotent($request, 'trades.create', function () use ($request, $listings) {
                $offering = $request->input('offering', $request->input('offer', []));
                $looking = $request->input('looking_for', $request->input('receive', []));
                if (! is_array($offering) || ! is_array($looking)) {
                    throw TradeException::invalid('EMPTY_OFFERING', 'Both sides are required.');
                }
                $listing = $listings->create(
                    auth('trades')->user(),
                    $offering,
                    $looking,
                    null,
                    AccessLogService::ip($request)
                );

                return TradeApi::ok([
                    'public_id' => $listing->public_id,
                    'redirect' => TradePaths::show($listing->public_id),
                    'trade' => TradePresenter::listing($listing),
                ], 201);
            });
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        } catch (ValidationException $e) {
            return TradeApi::error('EMPTY_OFFERING', collect($e->errors())->flatten()->first() ?: 'Invalid trade.', 422);
        }
    }

    public function pending(Request $request, TradeListingService $listings): JsonResponse
    {
        $this->requireSchema();
        $page = $listings->pending($request->only(['page', 'limit', 'sort']));

        return TradeApi::ok([
            'items' => collect($page->items())->map(fn ($row) => TradePresenter::listing($row))->all(),
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ]);
    }

    public function show(string $ulid, TradeListingService $listings): JsonResponse
    {
        $this->requireSchema();
        try {
            return TradeApi::ok(['trade' => TradePresenter::listing($listings->findPublic($ulid), true)]);
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }

    public function cancel(string $ulid, TradeListingService $listings): JsonResponse
    {
        $this->requireSchema();
        try {
            $listing = $listings->cancel(auth('trades')->user(), $listings->findPublic($ulid));

            return TradeApi::ok(['trade' => TradePresenter::listing($listing)]);
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }

    public function join(Request $request, string $ulid, TradeListingService $listings, TradeJoinService $joins): JsonResponse
    {
        $this->requireSchema();
        try {
            return $this->idempotent($request, 'trades.join.'.$ulid, function () use ($request, $ulid, $listings, $joins) {
                $row = $joins->join(auth('trades')->user(), $listings->findPublic($ulid), $request->input('note'));

                return TradeApi::ok(['join_request' => TradePresenter::join($row)], 201);
            });
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }

    public function messages(string $ulid, TradeListingService $listings, TradeNotificationService $notifications): JsonResponse
    {
        $this->requireSchema();
        try {
            $user = auth('trades')->user();
            $listing = $listings->findPublic($ulid);
            $items = $notifications->thread($user, $listing)
                ->map(fn ($row) => TradePresenter::threadMessage($row, $user))
                ->values()
                ->all();
            $peer = $notifications->peer($user, $listing);

            return TradeApi::ok([
                'items' => $items,
                'message_quota' => $peer ? $notifications->messageQuota($user, $peer) : null,
                'contact_quota' => $peer ? $notifications->contactQuota($user, $peer) : null,
            ]);
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }

    public function sendMessage(Request $request, string $ulid, TradeListingService $listings, TradeNotificationService $notifications): JsonResponse
    {
        $this->requireSchema();
        try {
            $user = auth('trades')->user();
            $row = $notifications->send($user, $listings->findPublic($ulid), $request->input('message'));

            return TradeApi::ok(['message' => TradePresenter::threadMessage($row->loadMissing('actor'), $user)], 201);
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }

    public function joinRequests(string $ulid, TradeListingService $listings, TradeJoinService $joins): JsonResponse
    {
        $this->requireSchema();
        try {
            $rows = $joins->forListing(auth('trades')->user(), $listings->findPublic($ulid));

            return TradeApi::ok(['items' => array_map(fn ($row) => TradePresenter::join($row), $rows)]);
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }

    public function hide(string $ulid, TradeListingService $listings, TradeModerationService $moderation): JsonResponse
    {
        $this->requireSchema();
        $user = auth('trades')->user();
        if (! $moderation->isModerator($user)) {
            return TradeApi::fromException(TradeException::forbidden('AUTH_REQUIRED', 'Moderator only.'));
        }
        try {
            $listing = $listings->hide($user, $listings->findPublic($ulid));

            return TradeApi::ok(['trade' => TradePresenter::listing($listing)]);
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }

    public function confirm(Request $request, string $ulid, TradeListingService $listings, TradeConfirmationService $confirmations): JsonResponse
    {
        $this->requireSchema();
        try {
            return $this->idempotent($request, 'trades.confirm.'.$ulid, function () use ($request, $ulid, $listings, $confirmations) {
                $listing = $confirmations->confirm(
                    auth('trades')->user(),
                    $listings->findPublic($ulid),
                    (string) $request->input('confirmation'),
                    $request->input('note')
                );

                return TradeApi::ok(['trade' => TradePresenter::listing($listing)]);
            });
        } catch (TradeException $e) {
            return TradeApi::fromException($e);
        }
    }

    public function activity(Request $request, TradeActivityService $activity): JsonResponse
    {
        $this->requireSchema();
        $page = $activity->forUser(
            auth('trades')->user(),
            (string) $request->query('status', 'all'),
            (int) $request->query('page', 1),
            (int) $request->query('limit', 20),
        );

        return TradeApi::ok([
            'items' => collect($page->items())->map(fn ($row) => TradePresenter::listing($row))->all(),
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ]);
    }

    private function requireSchema(): void
    {
        abort_unless(TradeSchema::ready(), 404);
    }

    /**
     * @param  callable(): JsonResponse  $callback
     */
    private function idempotent(Request $request, string $scope, callable $callback): JsonResponse
    {
        $key = trim((string) $request->header('Idempotency-Key', ''));
        if ($key === '') {
            return $callback();
        }
        $cacheKey = 'trade:idem:'.$scope.':'.auth('trades')->id().':'.$key;
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return response()->json($cached['body'], $cached['status']);
        }
        $response = $callback();
        Cache::put($cacheKey, ['body' => $response->getData(true), 'status' => $response->getStatusCode()], 3600);

        return $response;
    }
}
