<?php

namespace Database\Seeders;

use App\Enums\AboutBlockType;
use App\Models\AboutBlock;
use Illuminate\Database\Seeder;

/**
 * Contenido inicial de «Nosotros», copiado literalmente de la página oficial
 * https://pe.mmmoficial.org/nosotros/ (consultada el 16/10/2026). Solo se crea
 * si aún no hay bloques; las fotos se suben desde el panel (con autorización).
 */
class AboutBlockSeeder extends Seeder
{
    public function run(): void
    {
        if (AboutBlock::query()->exists()) {
            return;
        }

        $blocks = [
            [AboutBlockType::Text, "Somos una organización que nació en el corazón de Dios para esta hora final, cuando tantos creyentes, congregaciones y concilios, se están deslizando por la corriente del pecado; una obra con fundamento bíblico que defiende la sana doctrina.\n\nNuestra organización Cristiana Evangélica, Movimiento Misionero Mundial, está presente en todo el mundo. Las actividades que ejercemos radican en la predicación de los Santos Evangelios, enfatizados en la Sana Doctrina Bíblica. Realizamos también campañas de evangelización, instaurando iglesias, estudios bíblicos, formando obreros y estableciendo nuevos templos.", null, null],
            [AboutBlockType::Highlight, "Con casi 59 años de constitución, hemos llegado a más de 80 países, naciones que han reconocido a Jesucristo como su Señor; alrededor de 10,000 congregaciones doblan sus rodillas ante el soberano Dios y un cuerpo ministerial que sobrepasa los 11,000 obreros, claman día y noche para que la misericordia de Dios se extienda a más naciones.\n\nNuestro fundador es el Rev. Luis M. Ortiz y las oficinas principales se establecen en Puerto Rico. La Junta Oficial de esta institución tiene la siguiente estructura: Presidente, Vicepresidente, Secretario, Tesorero y Vocales; aquellos que velan y dirigen los principios, objetivos y metas de esta gran organización. También está constituida por Supervisores Misioneros, Supervisores Nacionales y Representantes de la Misión, los cuales son nombrados por la Junta Oficial.", null, null],
            [AboutBlockType::Quote, '“La fuerza que nos impulsa y motiva es **el Espíritu Santo**; la pasión que nos conmueve es **la situación de las almas** y el motivo que nos inspira es **la Gloria de Dios**; la fuente que nos somete es **el crecimiento de la obra misionera**; la urgencia que nos moviliza es **la brevedad del tiempo** y la realidad que nos alienta es **el abundante fruto**”.', 'Rev. Luis M. Ortiz', 'Fundador del M.M.M'],
        ];

        foreach ($blocks as $i => [$type, $body, $author, $role]) {
            AboutBlock::create([
                'type' => $type, 'body' => $body, 'author_name' => $author, 'author_role' => $role,
                'is_published' => true, 'sort_order' => $i + 1,
            ]);
        }
    }
}
