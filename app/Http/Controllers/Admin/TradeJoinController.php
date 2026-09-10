<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TradeJoinRequest;
use App\Services\Trades\TradeListingService;
use App\Support\TradeSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class TradeJoinController extends Controller
{
    public function __construct(private readonly TradeListingService $listings) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless(TradeSchema::ready() && Schema::hasTable('seo_trade_join_requests'), 404);

        $data = $request->validate([
            'status' => ['sometimes', 'nullable', 'string', Rule::in([
                TradeJoinRequest::STATUS_REQUESTED,
                TradeJoinRequest::STATUS_ACCEPTED,
                TradeJoinRequest::STATUS_REJECTED,
                TradeJoinRequest::STATUS_AUTO_REJECTED,
                TradeJoinRequest::STATUS_CANCELLED,
                TradeJoinRequest::STATUS_EXPIRED,
            ])],
        ]);
        $status = trim((string) ($data['status'] ?? ''));

        $joins = TradeJoinRequest::query()
            ->with([
                'listing:id,public_id',
                'requester:id,username,email,profile_id',
                'owner:id,username,email,profile_id',
            ])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return response()->json([
            'joins' => $joins->map(fn (TradeJoinRequest $join): array => [
                'id' => $join->id,
                'public_id' => $join->public_id,
                'listing_public_id' => $join->listing?->public_id,
                'requester_username' => $join->requester?->username,
                'requester_email' => $join->requester?->email,
                'requester_profile_id' => $join->requester?->profile_id,
                'owner_username' => $join->owner?->username,
                'owner_profile_id' => $join->owner?->profile_id,
                'status' => $join->status,
                'note' => $join->note,
                'created_at' => $join->created_at,
                'accepted_at' => $join->accepted_at,
                'rejected_at' => $join->rejected_at,
                'expires_at' => $join->expires_at,
            ])->all(),
        ]);
    }

    public function destroy(int $join): JsonResponse
    {
        abort_unless(TradeSchema::ready() && Schema::hasTable('seo_trade_join_requests'), 404);

        $row = TradeJoinRequest::query()->findOrFail($join);
        if ($row->status !== TradeJoinRequest::STATUS_REQUESTED) {
            return response()->json([
                'message' => 'Only requested send offers can be deleted.',
            ], 422);
        }

        $listing = $row->listing;
        $publicId = $row->public_id;
        $row->delete();

        if ($listing !== null) {
            $this->listings->event($listing, 'join_deleted', null, [
                'admin' => true,
                'join_public_id' => $publicId,
            ]);
        }

        return response()->json([
            'ok' => true,
        ]);
    }
}
