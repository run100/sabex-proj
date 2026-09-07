<?php

namespace App\Services\Seo;

class HtmlSanitizerService
{
    public const PROFILE_DEFAULT = 'default';

    private const ALLOWED_TAGS = '<h2><h3><p><br><strong><em><ul><ol><li><blockquote><a><img><code><pre><table><thead><tbody><tr><th><td>';

    /** @var array<string, list<string>> */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'th' => ['colspan', 'rowspan'],
        'td' => ['colspan', 'rowspan'],
    ];

    public function stripDangerous(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html) ?? '';

        return trim($html);
    }

    public function sanitize(string $html, string $profile = self::PROFILE_DEFAULT): string
    {
        unset($profile);

        $html = preg_replace('#<(script|style|iframe|object|embed|form|button)[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('#<(input)[^>]*>#is', '', $html) ?? '';
        $html = strip_tags($html, self::ALLOWED_TAGS);
        if (trim($html) === '') {
            return '';
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->documentElement;
        if ($root instanceof \DOMElement) {
            $this->cleanNode($root);
        }

        $result = '';
        foreach ($root?->childNodes ?? [] as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    private function cleanNode(\DOMNode $node): void
    {
        if ($node instanceof \DOMElement) {
            $tag = strtolower($node->tagName);
            $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];
            $remove = [];

            foreach ($node->attributes as $attribute) {
                $name = strtolower($attribute->name);
                if (! in_array($name, $allowed, true) || str_starts_with($name, 'on')) {
                    $remove[] = $attribute->name;

                    continue;
                }

                if ($name === 'href' && ! $this->isSafeUrl($attribute->value)) {
                    $remove[] = $attribute->name;
                }

                if ($name === 'src' && ! $this->isSafeImageUrl($attribute->value)) {
                    $remove[] = $attribute->name;
                }
            }

            foreach ($remove as $name) {
                $node->removeAttribute($name);
            }

            if ($tag === 'a') {
                $href = $node->getAttribute('href');
                if ($href !== '' && preg_match('#^https?://#i', $href)) {
                    $node->setAttribute('target', '_blank');
                    $node->setAttribute('rel', 'nofollow noopener noreferrer');
                }
            }
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $this->cleanNode($child);
        }
    }

    private function isSafeImageUrl(string $url): bool
    {
        return str_starts_with($url, '/')
            || preg_match('#^https://#i', $url) === 1;
    }

    private function isSafeUrl(string $url): bool
    {
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        return preg_match('#^https?://#i', $url) === 1;
    }
}
