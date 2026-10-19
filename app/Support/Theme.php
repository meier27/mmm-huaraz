<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Apariencia editable: colores principales y fuentes.
 *
 * Tailwind 4 genera las clases sobre variables CSS (--color-primary, …), así
 * que basta con redefinir esas variables en :root; no hace falta recompilar.
 * Solo se emite lo que el equipo cambió: lo vacío conserva app.css.
 */
class Theme
{
    /** Valores de resources/css/app.css (se muestran como referencia en el panel). */
    public const DEFAULT_COLORS = [
        'color_primary' => '#39312d',
        'color_accent' => '#f39c12',
        'color_button' => '#b04e09',
        'color_background' => '#faf3ed',
        'color_gradient_end' => '#5b3b26',
    ];

    public const DEFAULT_FONTS = [
        'font_body' => 'open-sans',
        'font_heading' => 'merriweather',
    ];

    /**
     * Fuentes disponibles: todas alojadas en el propio sitio (paquetes de
     * Fontsource importados en app.css), sin llamadas a Google.
     */
    public const FONTS = [
        'roboto' => ['label' => 'Roboto', 'stack' => "'Roboto Variable', ui-sans-serif, system-ui, sans-serif"],
        'open-sans' => ['label' => 'Open Sans', 'stack' => "'Open Sans Variable', ui-sans-serif, system-ui, sans-serif"],
        'montserrat' => ['label' => 'Montserrat', 'stack' => "'Montserrat Variable', ui-sans-serif, system-ui, sans-serif"],
        'merriweather' => ['label' => 'Merriweather (con serifa)', 'stack' => "'Merriweather Variable', Georgia, 'Times New Roman', serif"],
        'roboto-slab' => ['label' => 'Roboto Slab (con serifa)', 'stack' => "'Roboto Slab Variable', Georgia, serif"],
        'playfair-display' => ['label' => 'Playfair Display (con serifa)', 'stack' => "'Playfair Display Variable', Georgia, serif"],
    ];

    /** Contraste mínimo con texto blanco (WCAG AA, texto normal). */
    public const MIN_CONTRAST_WITH_WHITE = 4.5;

    /** Colores que siempre llevan texto blanco encima. */
    public const NEEDS_WHITE_TEXT = ['color_primary', 'color_button', 'color_gradient_end'];

    /**
     * Bloque CSS con las variables cambiadas, o '' si todo es predeterminado.
     * Los valores ya están validados al guardar; se vuelven a filtrar aquí
     * porque terminan dentro de una etiqueta <style>.
     */
    public static function css(SiteSetting $site): string
    {
        $vars = [];
        $color = fn (string $field) => self::validHex($site->{$field}) ? strtolower($site->{$field}) : null;

        if ($primary = $color('color_primary')) {
            $vars['--color-primary'] = $primary;
            $vars['--color-primary-deep'] = $primary;
            $vars['--color-primary-soft'] = "color-mix(in srgb, {$primary} 73%, white)";
        }

        if ($accent = $color('color_accent')) {
            $vars['--color-accent-light'] = $accent;
            $vars['--color-brand'] = $accent;
            $vars['--color-accent-warm'] = "color-mix(in srgb, {$accent} 85%, #e53e00)";
        }

        if ($button = $color('color_button')) {
            $vars['--color-brand-strong'] = $button;
        }

        if ($background = $color('color_background')) {
            $vars['--color-cream'] = $background;
            $vars['--color-cream-light'] = "color-mix(in srgb, {$background} 50%, white)";
            $vars['--color-sand'] = "color-mix(in srgb, {$background} 96%, #6b4a1f)";
            $vars['--color-sand-dark'] = "color-mix(in srgb, {$background} 86%, #6b4a1f)";
        }

        // El inicio de los degradados es el color principal; el naranja (bloque
        // destacado) ya se deriva en app.css del color de botones.
        if ($gradientEnd = $color('color_gradient_end')) {
            $vars['--color-gradient-end'] = $gradientEnd;
        }

        foreach (['font_body' => '--font-sans', 'font_heading' => '--font-display'] as $field => $var) {
            if (isset(self::FONTS[$site->{$field}])) {
                $vars[$var] = self::FONTS[$site->{$field}]['stack'];
            }
        }

        if ($vars === []) {
            return '';
        }

        return ':root{'.implode('', array_map(fn ($k, $v) => "{$k}:{$v};", array_keys($vars), $vars)).'}';
    }

    public static function validHex(?string $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1;
    }

    /** Contraste WCAG entre un color y el blanco (1 a 21). */
    public static function contrastWithWhite(string $hex): float
    {
        $channels = array_map(function (string $pair) {
            $c = hexdec($pair) / 255;

            return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        $luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];

        return round(1.05 / ($luminance + 0.05), 2);
    }
}
