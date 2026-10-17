<?php

namespace Database\Seeders;

use App\Models\MediaOutlet;
use Illuminate\Database\Seeder;

/**
 * Medios oficiales del movimiento (los mismos que enlaza https://pe.mmmoficial.org/medios/).
 * Solo se crean si aún no hay medios; los logotipos se suben desde Panel › Medios.
 * Los colores de fondo son los de cada logotipo oficial.
 */
class MediaOutletSeeder extends Seeder
{
    public function run(): void
    {
        if (MediaOutlet::query()->exists()) {
            return;
        }

        $outlets = [
            ['Bethel Televisión', '/bethel-tv', '#7F31D2'],
            ['Bethel Radio', 'https://bethelradio.fm/', '#01AE5E'],
            ['Impacto Evangelístico', 'https://impactoevangelistico.net/', '#373E44'],
        ];

        foreach ($outlets as $i => [$name, $url, $color]) {
            MediaOutlet::create([
                'name' => $name, 'url' => $url, 'background_color' => $color,
                'is_published' => true, 'sort_order' => $i + 1,
            ]);
        }
    }
}
