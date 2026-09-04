<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoNewsArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(): JsonResponse
    {
        $articles = SeoNewsArticle::query()
            ->where('type', SeoNewsArticle::TYPE_NEWS)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'slug', 'title', 'status', 'locale', 'published_at', 'meta_title', 'meta_description', 'excerpt', 'body_html']);

        return response()->json(['articles' => $articles]);
    }

    public function update(Request $request, SeoNewsArticle $article): JsonResponse
    {
        abort_unless((int) $article->type === SeoNewsArticle::TYPE_NEWS, 404);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'excerpt' => ['sometimes', 'nullable', 'string'],
            'body_html' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:published,draft'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string'],
        ]);
        $article->fill($data);
        $article->save();

        return response()->json(['article' => $article]);
    }
}
