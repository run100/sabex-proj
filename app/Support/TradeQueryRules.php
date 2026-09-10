<?php

namespace App\Support;

use Illuminate\Validation\Rule;

final class TradeQueryRules
{
    /**
     * @var list<string>
     */
    public const SORTS = ['newest', 'value_desc', 'value_asc'];

    /**
     * @var list<string>
     */
    public const ACTIVITY_STATUSES = [
        'ads',
        'received',
        'sent',
        'expired',
        'open',
        'pending',
        'completed',
        'failed',
        'disputed',
        'all',
    ];

    /**
     * @return array<string, list<mixed>>
     */
    public static function pagination(): array
    {
        return [
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function recentListings(): array
    {
        return array_merge(self::pagination(), [
            'want_brainrot_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'have_brainrot_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'min_value' => ['sometimes', 'nullable', 'numeric'],
            'max_value' => ['sometimes', 'nullable', 'numeric'],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in(self::SORTS)],
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function completedListings(): array
    {
        return array_merge(self::pagination(), [
            'username' => ['sometimes', 'nullable', 'string', 'max:100'],
            'roblox_sub' => ['sometimes', 'nullable', 'string', 'max:64'],
            'brainrot_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in(self::SORTS)],
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function pendingListings(): array
    {
        return array_merge(self::pagination(), [
            'sort' => ['sometimes', 'nullable', 'string', Rule::in(self::SORTS)],
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function activity(): array
    {
        return array_merge(self::pagination(), [
            'status' => ['sometimes', 'nullable', 'string', Rule::in(self::ACTIVITY_STATUSES)],
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function webActivity(): array
    {
        return [
            'status' => ['sometimes', 'nullable', 'string', Rule::in([
                'ads',
                'received',
                'sent',
                'pending',
                'completed',
                'expired',
            ])],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function notifications(): array
    {
        return [
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function brainrotSearch(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function confirmation(): array
    {
        return [
            'confirmation' => ['required', 'string', Rule::in(['completed', 'failed'])],
            'note' => ['sometimes', 'nullable', 'string', 'max:280'],
        ];
    }
}
