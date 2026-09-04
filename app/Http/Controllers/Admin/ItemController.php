<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $items = SeoItem::query()
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q): void {
                $inner->where('slug', 'like', '%'.$q.'%')
                    ->orWhere('name', 'like', '%'.$q.'%');
            }))
            ->orderByDesc('is_listed')
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'slug', 'name', 'rarity', 'is_listed', 'is_publish_html', 'total_exists']);

        return response()->json(['items' => $items]);
    }

    public function update(Request $request, SeoItem $item): JsonResponse
    {
        $data = $request->validate([
            'is_listed' => ['sometimes', 'boolean'],
            'is_publish_html' => ['sometimes', 'boolean'],
        ]);
        $item->fill($data);
        $item->save();

        return response()->json(['item' => $item->only(['id', 'slug', 'name', 'rarity', 'is_listed', 'is_publish_html', 'total_exists'])]);
    }
}
