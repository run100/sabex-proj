<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoSite;
use App\Services\Seo\SabSiteContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{
    public function index(): JsonResponse
    {
        $sites = SeoSite::query()
            ->withCount('games')
            ->orderBy('id')
            ->get();

        return response()->json([
            'sites' => $sites->map(fn (SeoSite $site): array => $this->serialize($site))->all(),
        ]);
    }

    public function update(Request $request, SeoSite $site): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:190'],
            'domain' => ['sometimes', 'nullable', 'string', 'max:190'],
            'base_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'output_path' => ['sometimes', 'nullable', 'string', 'max:500'],
            'settings' => ['sometimes', 'array'],
            'settings.site_mode' => ['sometimes', Rule::in([
                SabSiteContext::SITE_MODE_FULL,
                SabSiteContext::SITE_MODE_CALCULATOR_ONLY,
            ])],
            'settings.data_site_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'settings.local_preview_base_url' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        if ($data === []) {
            return response()->json([
                'message' => 'At least one field is required.',
            ], 422);
        }

        foreach (['name', 'domain', 'base_url', 'output_path'] as $field) {
            if (array_key_exists($field, $data)) {
                $site->{$field} = $data[$field];
            }
        }

        if (array_key_exists('settings', $data)) {
            $settings = is_array($site->settings_json) ? $site->settings_json : [];
            if (array_key_exists('site_mode', $data['settings'])) {
                $settings['site_mode'] = $data['settings']['site_mode'];
            }
            if (array_key_exists('data_site_slug', $data['settings'])) {
                $slug = trim((string) ($data['settings']['data_site_slug'] ?? ''));
                if ($slug === '') {
                    unset($settings['data_site_slug']);
                } else {
                    $settings['data_site_slug'] = $slug;
                }
            }
            if (array_key_exists('local_preview_base_url', $data['settings'])) {
                $preview = trim((string) ($data['settings']['local_preview_base_url'] ?? ''));
                if ($preview === '') {
                    unset($settings['local_preview_base_url']);
                } else {
                    $settings['local_preview_base_url'] = $preview;
                }
            }
            $site->settings_json = $settings;
        }

        $site->save();

        return response()->json([
            'site' => $this->serialize($site->loadCount('games')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(SeoSite $site): array
    {
        $settings = is_array($site->settings_json) ? $site->settings_json : [];
        $mode = $settings['site_mode'] ?? SabSiteContext::SITE_MODE_FULL;
        $previewBase = trim((string) ($settings['local_preview_base_url'] ?? ''));
        if ($previewBase === '') {
            $previewBase = rtrim((string) ($site->base_url ?? ''), '/');
        }

        return [
            'id' => $site->id,
            'slug' => $site->slug,
            'name' => $site->name,
            'domain' => $site->domain,
            'base_url' => $site->base_url,
            'output_path' => $site->output_path,
            'games_count' => (int) ($site->games_count ?? 0),
            'site_mode' => $mode,
            'data_site_slug' => $settings['data_site_slug'] ?? '',
            'local_preview_base_url' => $settings['local_preview_base_url'] ?? '',
            'preview_url' => $previewBase !== '' ? rtrim($previewBase, '/') : '',
            'is_calculator_only' => $mode === SabSiteContext::SITE_MODE_CALCULATOR_ONLY
                || $site->slug === SabSiteContext::SITE_SLUG_SAB_CALCULATOR,
        ];
    }
}
