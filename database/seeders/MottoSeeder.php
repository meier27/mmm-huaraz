<?php

namespace Database\Seeders;

use App\Models\Motto;
use Illuminate\Database\Seeder;

/**
 * Lema 2026, copiado literalmente de https://pe.mmmoficial.org/lemas/
 * (consultada el 16/10/2026). Solo se crea si ese año no existe; la imagen
 * oficial se sube desde Panel › Lemas.
 */
class MottoSeeder extends Seeder
{
    public function run(): void
    {
        Motto::query()->firstOrCreate(['year' => 2026], [
            'title' => 'Familia',
            'description' => "La familia es la base donde se forma y preserva la fe cristiana. En el hogar se establecen principios que sostienen a las generaciones y afirman la identidad espiritual. La familia transmite un mismo legado, manteniendo un núcleo firme e inalterable a lo largo del tiempo. Cuando los hogares se fortalecen, la obra de Dios se afirma y se proyecta hacia el futuro.\n\nGeneraciones se levantan cuando un hogar decide permanecer firme en la fe.",
            'verse_text' => 'Yo y mi casa serviremos a Jehová',
            'verse_reference' => 'Josué 24:15',
            'is_published' => true,
        ]);
    }
}
