<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoNewsArticle;
use App\Services\Seo\HtmlSanitizerService;
use App\Services\Seo\SabRenderService;
use App\Support\SabHost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NewsController extends Controller
{
    /**
     * @var list<string>
     */
    private const LEGAL_SLUGS = ['about-us', 'privacy-policy', 'terms-of-service'];

    public function __construct(private readonly HtmlSanitizerService $htmlSanitizer)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $title = trim((string) $request->query('title', $request->query('q', '')));
        $perPage = min(200, max(1, (int) $request->query('limit', 100)));

        $query = SeoNewsArticle::query()
            ->with('site:id,slug,name')
            ->when($request->filled('seo_site_id'), fn ($inner) => $inner->where('seo_site_id', (int) $request->query('seo_site_id')))
            ->when($request->filled('locale'), fn ($inner) => $inner->where('locale', (string) $request->query('locale')))
            ->when($request->filled('status'), fn ($inner) => $inner->where('status', (string) $request->query('status')))
            ->when($request->filled('type'), fn ($inner) => $inner->where('type', (int) $request->query('type')))
            ->when($title !== '', function ($inner) use ($title): void {
                $inner->where(function ($search) use ($title): void {
                    $search->where('title', 'like', '%'.$title.'%')
                        ->orWhere('slug', 'like', '%'.$title.'%');
                });
            })
            ->orderByDesc('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        $page = $query->paginate($perPage);

        return response()->json([
            'articles' => collect($page->items())->map(fn (SeoNewsArticle $article): array => $this->serialize($article))->all(),
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
            'locales' => SabRenderService::HOME_LOCALES,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $article = SeoNewsArticle::query()->create($this->payload($request));

        return response()->json([
            'article' => $this->serialize($article->load('site:id,slug,name')),
        ], 201);
    }

    public function update(Request $request, SeoNewsArticle $article): JsonResponse
    {
        $article->fill($this->payload($request, $article->id));
        $article->save();

        return response()->json([
            'article' => $this->serialize($article->refresh()->load('site:id,slug,name')),
        ]);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'seo_site_id' => ['nullable', 'integer', 'exists:seo_sites,id'],
        ]);

        $file = $payload['file'];
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $relativeDir = 'uploads/images/sab/news';
        $targetDir = public_path($relativeDir);

        if (! is_dir($targetDir) && ! mkdir($targetDir, 0755, true) && ! is_dir($targetDir)) {
            return response()->json(['message' => '图片上传目录不可写'], 500);
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $file->move($targetDir, $filename);

        return response()->json([
            'url' => '/'.$relativeDir.'/'.$filename,
            'filename' => $filename,
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, ?int $newsId = null): array
    {
        $payload = $request->validate([
            'seo_site_id' => ['required', 'integer', 'exists:seo_sites,id'],
            'type' => ['nullable', 'integer', Rule::in([SeoNewsArticle::TYPE_STATIC_PAGE, SeoNewsArticle::TYPE_NEWS])],
            'slug' => ['nullable', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:8'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'cover_image_url' => ['nullable', 'string', 'max:1000'],
            'body_html' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_system_log' => ['nullable', 'boolean'],
        ]);

        $payload['type'] ??= SeoNewsArticle::TYPE_NEWS;
        if ((int) $payload['type'] === SeoNewsArticle::TYPE_STATIC_PAGE && $payload['locale'] !== 'en') {
            abort(response()->json(['message' => '单页只支持 en 根目录版本'], 422));
        }

        $slug = Str::slug((string) ($payload['slug'] ?? ''));
        if ($slug === '' && $newsId) {
            $slug = (string) SeoNewsArticle::query()->whereKey($newsId)->value('slug');
        }
        $payload['slug'] = $slug !== '' ? $slug : Str::slug($payload['title']);
        if ($payload['slug'] === '') {
            abort(response()->json(['message' => '无法从标题生成 slug'], 422));
        }

        $exists = SeoNewsArticle::query()
            ->where('seo_site_id', $payload['seo_site_id'])
            ->where('locale', $payload['locale'])
            ->where('slug', $payload['slug'])
            ->when($newsId, fn ($query) => $query->where('id', '!=', $newsId))
            ->exists();
        if ($exists) {
            abort(response()->json(['message' => '同一站点和语言下内容 slug 已存在'], 422));
        }

        $bodyHtml = (string) ($payload['body_html'] ?? '');
        $payload['body_html'] = ! empty($payload['is_system_log'])
            ? $this->htmlSanitizer->stripDangerous($bodyHtml)
            : $this->htmlSanitizer->sanitize($bodyHtml);
        $payload['excerpt'] ??= '';
        $payload['cover_image_url'] ??= '';
        $payload['meta_description'] ??= '';
        $payload['sort_order'] ??= 10;
        $payload['is_system_log'] ??= false;
        if (empty($payload['published_at'])) {
            $payload['published_at'] = now()->subHours(8);
        }
        if (! array_key_exists('meta_title', $payload) || $payload['meta_title'] === null) {
            $payload['meta_title'] = $newsId
                ? (string) (SeoNewsArticle::query()->whereKey($newsId)->value('meta_title') ?? '')
                : '';
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(SeoNewsArticle $article): array
    {
        $publicPath = $this->publicPath($article);

        return [
            'id' => $article->id,
            'seo_site_id' => $article->seo_site_id,
            'type' => (int) $article->type,
            'slug' => $article->slug,
            'locale' => $article->locale,
            'title' => $article->title,
            'excerpt' => $article->excerpt,
            'cover_image_url' => $article->cover_image_url,
            'body_html' => $article->body_html,
            'status' => $article->status,
            'published_at' => optional($article->published_at)?->toIso8601String(),
            'meta_title' => $article->meta_title,
            'meta_description' => $article->meta_description,
            'sort_order' => (int) ($article->sort_order ?? 0),
            'is_system_log' => (bool) $article->is_system_log,
            'site' => $article->site ? [
                'id' => $article->site->id,
                'slug' => $article->site->slug,
                'name' => $article->site->name,
            ] : null,
            'preview_path' => $publicPath !== '' ? rtrim(SabHost::origin('www'), '/').$publicPath : '',
            'live_path' => $publicPath !== '' ? 'https://sabexistcount.com'.$publicPath : '',
        ];
    }

    private function publicPath(SeoNewsArticle $article): string
    {
        $slug = trim((string) $article->slug);
        if ($slug === '') {
            return '';
        }

        if ((int) $article->type === SeoNewsArticle::TYPE_STATIC_PAGE) {
            return in_array($slug, self::LEGAL_SLUGS, true) ? '/'.$slug : '';
        }

        return '/news/'.$slug;
    }
}
