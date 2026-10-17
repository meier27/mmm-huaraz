<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Livewire\Mechanisms\FrontendAssets\FrontendAssets;

/**
 * @livewireScripts con soporte para instalaciones en un subdirectorio
 * (p. ej. XAMPP: http://localhost/mmm-huaraz/public/).
 *
 * Livewire emite rutas absolutas desde la raíz del host ("/livewire/livewire.js"
 * y "/livewire/update") sin la base de la petición; en un subdirectorio dan 404
 * y con ellas no carga Alpine (menús, carrusel, formularios). Aquí se antepone
 * la base solo a esas dos rutas. En la raíz del dominio el resultado es idéntico.
 */
class LivewireAssets
{
    public static function scripts(): HtmlString
    {
        // También marca los scripts como renderizados: Livewire no los reinyecta.
        $html = FrontendAssets::scripts();
        $base = rtrim(request()->getBaseUrl(), '/');

        if ($base === '') {
            return new HtmlString($html);
        }

        return new HtmlString(str_replace(
            ['src="/livewire/', 'data-update-uri="/livewire/'],
            ['src="'.$base.'/livewire/', 'data-update-uri="'.$base.'/livewire/'],
            $html,
        ));
    }
}
