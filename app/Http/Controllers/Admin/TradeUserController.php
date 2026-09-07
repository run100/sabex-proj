<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TradeUser;
use App\Support\TradeSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TradeUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);

        $q = trim((string) $request->query('q', ''));

        $users = TradeUser::query()
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('username', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%')
                        ->orWhere('display_name', 'like', '%'.$q.'%')
                        ->orWhere('profile_id', 'like', '%'.$q.'%')
                        ->orWhere('public_id', 'like', '%'.$q.'%')
                        ->orWhere('roblox_sub', 'like', '%'.$q.'%')
                        ->orWhere('roblox_user_id', $q);
                });
            })
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return response()->json([
            'users' => $users->map(fn (TradeUser $user): array => $this->serialize($user))->all(),
        ]);
    }

    public function show(TradeUser $tradeUser): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);

        return response()->json([
            'user' => $this->serialize($tradeUser),
        ]);
    }

    public function update(Request $request, TradeUser $tradeUser): JsonResponse
    {
        abort_unless(TradeSchema::ready(), 404);

        $data = $request->validate([
            'posting_approved' => ['sometimes', 'boolean'],
            'account_status' => ['sometimes', Rule::in([
                TradeUser::STATUS_ACTIVE,
                TradeUser::STATUS_SUSPENDED,
                TradeUser::STATUS_BANNED,
            ])],
            'profile_visibility' => ['sometimes', Rule::in([
                TradeUser::VISIBILITY_PUBLIC,
                TradeUser::VISIBILITY_UNLISTED,
                TradeUser::VISIBILITY_PRIVATE,
            ])],
            'moderation_status' => ['sometimes', Rule::in([
                TradeUser::MODERATION_CLEAR,
                TradeUser::MODERATION_REVIEW,
                TradeUser::MODERATION_RESTRICTED,
            ])],
            'profile_index_eligible' => ['sometimes', 'boolean'],
        ]);

        if ($data === []) {
            throw ValidationException::withMessages([
                'posting_approved' => 'At least one field is required.',
            ]);
        }

        if (array_key_exists('posting_approved', $data)) {
            if ((bool) $data['posting_approved']) {
                if (! $tradeUser->posting_approved || $tradeUser->posting_approved_at === null) {
                    $tradeUser->posting_approved_at = now();
                }
                $tradeUser->posting_approved = true;
            } else {
                $tradeUser->posting_approved = false;
                $tradeUser->posting_approved_at = null;
            }
        }

        if (array_key_exists('account_status', $data)) {
            $tradeUser->account_status = $data['account_status'];
        }
        if (array_key_exists('profile_visibility', $data)) {
            $tradeUser->profile_visibility = $data['profile_visibility'];
        }
        if (array_key_exists('moderation_status', $data)) {
            $tradeUser->moderation_status = $data['moderation_status'];
        }
        if (array_key_exists('profile_index_eligible', $data)) {
            $tradeUser->profile_index_eligible = (bool) $data['profile_index_eligible'];
        }

        $tradeUser->save();

        return response()->json([
            'user' => $this->serialize($tradeUser),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(TradeUser $user): array
    {
        return [
            'id' => $user->id,
            'public_id' => $user->public_id,
            'profile_id' => $user->profile_id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'avatar_url' => $user->avatar_url,
            'profile_url' => $user->profile_url,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'roblox_sub' => $user->roblox_sub,
            'roblox_user_id' => $user->roblox_user_id,
            'account_status' => $user->account_status,
            'profile_visibility' => $user->profile_visibility,
            'moderation_status' => $user->moderation_status,
            'profile_index_eligible' => (bool) $user->profile_index_eligible,
            'deleted_at' => $user->deleted_at,
            'posting_approved' => $user->posting_approved,
            'posting_approved_at' => $user->posting_approved_at,
            'registered_ip' => $user->registered_ip,
            'last_login_at' => $user->last_login_at,
            'last_login_ip' => $user->last_login_ip,
            'created_at' => $user->created_at,
            'providers' => $user->connectedProviders(),
        ];
    }
}
