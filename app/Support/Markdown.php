<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Markdown de contenido administrable, convertido de forma segura: el HTML
 * escrito a mano se elimina y los enlaces peligrosos (javascript:, data:) se
 * descartan. Los enlaces externos se abren en pestaña nueva sin referrer.
 */
class Markdown
{
    public static function render(?string $text): HtmlString
    {
        if (blank($text)) {
            return new HtmlString('');
        }

        $html = Str::markdown($text, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
        ]);

        $html = preg_replace('/<a href="(https?:\/\/[^"]+)"/i', '<a href="$1" target="_blank" rel="noopener noreferrer"', $html);

        return new HtmlString($html);
    }

    /** Texto plano para resúmenes y metadatos. */
    public static function plain(?string $text, int $limit = 160): string
    {
        return Str::limit(trim(strip_tags(static::render($text)->toHtml())), $limit);
    }
}
