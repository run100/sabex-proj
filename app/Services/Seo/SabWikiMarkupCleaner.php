<?php

namespace App\Services\Seo;

class SabWikiMarkupCleaner
{
    public function clean(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // External: [https://example.com Label] -> Label
        $text = preg_replace('/\[(https?:\/\/[^\]\s]+)\s+([^\]]+)\]/u', '$2', $text) ?? $text;
        // External: [https://example.com] -> remove
        $text = preg_replace('/\[(https?:\/\/[^\]]+)\]/u', '', $text) ?? $text;
        // Internal: [[Page|Label]] or [[Page]] -> Label or Page
        $text = preg_replace('/\[\[(?:[^\]|]*\|)?([^\]]*)\]\]/u', '$1', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?: '');
    }
}
