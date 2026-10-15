<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCode
{
    /**
     * SVG del QR. Solo debe recibir URLs propias (nunca datos personales).
     */
    public static function svg(string $content, int $size = 280): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 2), new SvgImageBackEnd));

        // Se elimina la declaración XML para poder incrustarlo en el HTML.
        return trim(preg_replace('/^<\?xml[^>]*\?>/', '', $writer->writeString($content)));
    }
}
