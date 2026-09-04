<?php

namespace App\Services\Seo;

use App\Models\SeoGame;
use App\Models\SeoSite;

class SabSiteContext
{
    public const SITE_MODE_FULL = 'full';

    public const SITE_MODE_CALCULATOR_ONLY = 'calculator_only';

    public const SITE_SLUG_SAB_CALCULATOR = 'sabcalculator';

    public const EXTERNAL_EXIST_COUNT_URL = 'https://sabexistcount.com/';

    public const EXTERNAL_MM2_CALCULATOR_URL = 'https://mm2calculator.com/';

    public const GAME_SLUG = 'steal-a-brainrot';

    public const CSS_HREF_SAB_CALCULATOR = '/static/css/sabcalculator.css';

    public const FAVICON_SAB_CALCULATOR = '/uploads/images/sab/sabcalculator/favicon.ico';

    public const APPLE_TOUCH_SAB_CALCULATOR = '/uploads/images/sab/sabcalculator/apple-touch-icon.png';

    public function __construct(
        private readonly string $siteSlug = SabRenderService::SITE_SLUG,
    ) {}

    public function siteSlug(): string
    {
        return $this->siteSlug;
    }

    public function resolve(): SeoSite
    {
        return SeoSite::query()->where('slug', $this->siteSlug)->firstOrFail();
    }

    public function isCalculatorOnly(?SeoSite $site = null): bool
    {
        $site ??= $this->resolve();

        return $this->siteMode($site) === self::SITE_MODE_CALCULATOR_ONLY;
    }

    public function siteMode(?SeoSite $site = null): string
    {
        $site ??= $this->resolve();
        $mode = data_get($site->settings_json, 'site_mode', self::SITE_MODE_FULL);

        return in_array($mode, [self::SITE_MODE_FULL, self::SITE_MODE_CALCULATOR_ONLY], true)
            ? $mode
            : self::SITE_MODE_FULL;
    }

    public function dataSiteSlug(?SeoSite $site = null): string
    {
        $site ??= $this->resolve();
        $slug = trim((string) data_get($site->settings_json, 'data_site_slug', ''));

        return $slug !== '' ? $slug : $site->slug;
    }

    public function dataSite(?SeoSite $site = null): SeoSite
    {
        $site ??= $this->resolve();
        $slug = $this->dataSiteSlug($site);

        if ($slug === $site->slug) {
            return $site;
        }

        return SeoSite::query()->where('slug', $slug)->firstOrFail();
    }

    public function dataGame(?SeoSite $site = null): SeoGame
    {
        $dataSite = $this->dataSite($site);

        return SeoGame::query()
            ->where('seo_site_id', $dataSite->id)
            ->where('slug', self::GAME_SLUG)
            ->firstOrFail();
    }

    public function previewUrlPrefix(): string
    {
        if ($this->siteSlug === SabRenderService::SITE_SLUG) {
            return '/seo/sab/preview';
        }

        return '/seo/sab/preview/' . $this->siteSlug;
    }

    public function publicUrlPrefix(string $locale = SabRenderService::HOME_LOCALES[0]): string
    {
        if ($this->isCalculatorOnly()) {
            return '';
        }

        return $locale === 'en' ? '' : '/' . $locale;
    }

    public function cssHrefForRender(): string
    {
        return $this->isCalculatorOnly()
            ? self::CSS_HREF_SAB_CALCULATOR
            : SabRenderService::CSS_HREF_LARAVEL;
    }

    public function cssSourceFilename(): string
    {
        return $this->isCalculatorOnly() ? 'sabcalculator.css' : 'sabexistcount.css';
    }

    /**
     * @return array<string, mixed>
     */
    public function brand(?SeoSite $site = null): array
    {
        $site ??= $this->resolve();
        $brand = data_get($site->settings_json, 'brand', []);

        return is_array($brand) ? $brand : [];
    }

    /**
     * @return array<string, string>
     */
    public function calculatorCopy(?SeoSite $site = null): array
    {
        $site ??= $this->resolve();
        $defaults = SabRenderService::defaultCalculatorCopy();
        $stored = data_get($site->settings_json, 'sab_copy.calculator', []);

        if (! is_array($stored)) {
            $stored = [];
        }

        $merged = [];
        foreach ($defaults as $key => $default) {
            $value = $stored[$key] ?? $default;
            $merged[$key] = is_string($value) ? trim($value) : (string) $default;
        }

        return $merged;
    }

    public static function expandHomePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, '~/')) {
            $home = rtrim((string) ($_SERVER['HOME'] ?? getenv('HOME') ?: ''), '/');
            if ($home === '') {
                return $path;
            }

            return $home . '/' . ltrim(substr($path, 2), '/');
        }

        return $path;
    }
}
