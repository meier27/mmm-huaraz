<?php

namespace App\Console\Commands;

use App\Enums\TeachingCategory;
use App\Models\Banner;
use App\Models\ChurchEvent;
use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\Leader;
use App\Models\ProgramDay;
use App\Models\ProgramItem;
use App\Models\SiteSetting;
use App\Models\Teaching;
use App\Models\Verse;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Contenido de EJEMPLO para revisar el diseño mientras llega el material
 * oficial. Todo queda marcado con "[Ejemplo]" (o "(texto de ejemplo)" en los
 * datos del sitio) y se elimina con --remove. No es información oficial.
 *
 * Deliberadamente NO crea métodos de donación ni teléfonos/WhatsApp: un
 * número de cuenta o de teléfono inventado podría pertenecer a otra persona.
 * Los versículos son citas literales de la Reina-Valera 1909 (dominio público).
 */
class DemoContent extends Command
{
    private const PREFIX = '[Ejemplo]';

    private const MARK = ' (texto de ejemplo)';

    protected $signature = 'app:demo-content
        {--remove : Elimina el contenido de ejemplo}';

    protected $description = 'Crea (o elimina con --remove) contenido de ejemplo para todas las secciones, con imágenes de prueba';

    public function handle(): int
    {
        return $this->option('remove') ? $this->remove() : $this->create();
    }

    private function create(): int
    {
        if (Banner::where('title', 'like', self::PREFIX.'%')->exists()) {
            $this->warn('Ya existe contenido de ejemplo. Usa --remove para eliminarlo antes de volver a crearlo.');

            return self::FAILURE;
        }

        // El programa de ejemplo va en el evento destacado.
        $event = ChurchEvent::featured();
        if (! $event) {
            $this->error('No hay un evento destacado. Ejecuta «php artisan db:seed» o marca uno en Panel › Eventos.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($event) {
            $this->createSiteTexts();
            $this->createBanners();
            $this->createGalleryPhotos();
            $this->createTeachings();
            $this->createVerses();
            $this->createLeaders();
            $this->createEvents();
            $this->createFaqs();
            $this->createProgram($event);
        });

        $this->info('Contenido de ejemplo creado en todas las secciones (marcado con «[Ejemplo]»).');
        $this->line('No se crean donaciones ni teléfonos de contacto: complétalos con los datos oficiales desde el panel.');

        return self::SUCCESS;
    }

    // ---------------------------------------------------------------------
    // Sitio
    // ---------------------------------------------------------------------

    /** @return array<string, string> */
    private function siteTexts(): array
    {
        return [
            'tagline' => 'Una iglesia que predica a Cristo y acompaña a las familias de Huaraz'.self::MARK,
            'welcome_text' => "Te damos la bienvenida a nuestra iglesia. Aquí encontrarás las enseñanzas, los eventos y la información de la convención.\n\nEste párrafo es de ejemplo: reemplázalo desde Panel › Sitio.",
            'history' => "Este es un texto de ejemplo para la historia de la iglesia.\n\nCuenta aquí **cómo y cuándo comenzó la obra** en Huaraz, quiénes fueron los primeros hermanos y cómo ha crecido la congregación.\n\n- Primeros cultos y lugar de reunión.\n- Construcción del templo.\n- Obra misionera en la región.".self::MARK,
            'mission' => 'Texto de ejemplo para la misión: predicar el evangelio y formar discípulos en Huaraz y la región.',
            'vision' => 'Texto de ejemplo para la visión: familias restauradas y una iglesia que sirve a su ciudad.',
            'service_schedule' => "Domingo · 10:00 a. m. · Culto general (ejemplo)\nMartes · 7:00 p. m. · Oración (ejemplo)\nJueves · 7:00 p. m. · Estudio bíblico (ejemplo)\nSábado · 4:00 p. m. · Jóvenes (ejemplo)",
            'contact_email' => 'contacto@ejemplo.test',
        ];
    }

    /** Solo completa los campos vacíos: nunca pisa lo que el equipo ya escribió. */
    private function createSiteTexts(): void
    {
        $site = SiteSetting::query()->first() ?? new SiteSetting(SiteSetting::defaults());

        foreach ($this->siteTexts() as $field => $value) {
            if (blank($site->getAttribute($field))) {
                $site->setAttribute($field, $value);
            }
        }

        $site->save();
    }

    private function createBanners(): void
    {
        $banners = [
            ['Bienvenidos a nuestra iglesia', 'Banner de ejemplo: sustitúyelo por una foto autorizada desde el panel.', 'Conócenos', '/nosotros', [[18, 52, 92], [196, 120, 60]]],
            ['Convención 2026 en Huaraz', 'Programa, sede y todo para participar.', 'Ver la convención', '/eventos/convencion', [[113, 50, 24], [244, 127, 18]]],
            ['Bethel Televisión en vivo', 'Sigue la señal del canal oficial del movimiento.', 'Ver en vivo', '/bethel-tv', [[0, 46, 93], [120, 170, 200]]],
        ];

        foreach ($banners as $i => [$title, $subtitle, $cta, $url, $colors]) {
            $banner = Banner::create([
                'title' => self::PREFIX.' '.$title, 'subtitle' => $subtitle,
                'image_alt' => 'Imagen de prueba', 'cta_label' => $cta, 'cta_url' => $url,
                'is_published' => true, 'sort_order' => $i + 1,
            ]);
            $banner->replaceImage($this->image(1920, 900, $colors[0], $colors[1], 'mountains'));
        }
    }

    private function createGalleryPhotos(): void
    {
        $colors = [[[57, 49, 45], [196, 120, 60]], [[113, 50, 24], [243, 156, 18]], [[90, 70, 55], [231, 216, 195]], [[60, 90, 40], [190, 200, 120]]];

        foreach ($colors as $i => [$from, $to]) {
            $photo = GalleryPhoto::create([
                'image_alt' => self::PREFIX.' Foto de prueba '.($i + 1),
                'is_published' => true, 'sort_order' => $i + 1,
            ]);
            $photo->replaceImage($this->image(1200, 1200, $from, $to, 'mountains'));
        }
    }

    private function createTeachings(): void
    {
        $teachings = [
            [TeachingCategory::Family, 'La familia que sirve a Dios', 'Josué 24:15', '…que yo y mi casa serviremos á Jehová.', [[113, 50, 24], [196, 120, 60]]],
            [TeachingCategory::Sermon, 'Esperanza para tu casa', 'Hechos 16:31', 'Cree en el Señor Jesucristo, y serás salvo tú, y tu casa.', [[0, 46, 93], [90, 140, 190]]],
            [TeachingCategory::Devotional, 'El Dios de esperanza', 'Romanos 15:13', 'Y el Dios de esperanza os llene de todo gozo y paz creyendo, para que abundéis en esperanza por la virtud del Espíritu Santo.', null],
            [TeachingCategory::BibleStudy, 'Si Jehová no edificare la casa', 'Salmos 127:1', 'Si Jehová no edificare la casa, En vano trabajan los que la edifican…', [[60, 90, 40], [190, 200, 120]]],
            [TeachingCategory::Youth, 'Instruidos desde la juventud', 'Proverbios 22:6', 'Instruye al niño en su carrera: Aun cuando fuere viejo no se apartará de ella.', [[120, 35, 35], [235, 130, 100]]],
            [TeachingCategory::Doctrine, 'Andar en la luz', '1 Juan 1:7', 'Mas si andamos en luz, como Él está en luz, tenemos comunión entre nosotros, y la sangre de Jesucristo su Hijo nos limpia de todo pecado.', null],
        ];

        foreach ($teachings as $i => [$category, $title, $reference, $quote, $colors]) {
            $fullTitle = self::PREFIX.' '.$title;

            $teaching = Teaching::create([
                'title' => $fullTitle,
                'slug' => Teaching::uniqueSlug('ejemplo '.$title),
                'category' => $category,
                'excerpt' => 'Resumen de ejemplo de la enseñanza basada en '.$reference.'. Reemplázalo desde el panel.',
                'body' => "> {$quote}\n> — {$reference} (RVR1909)\n\n".
                    "Este es el **contenido de ejemplo** de una enseñanza. Aquí irá el texto de la predicación o del estudio.\n\n".
                    "## Primer punto\n\nDesarrollo del primer punto de la enseñanza, con sus citas bíblicas.\n\n".
                    "## Segundo punto\n\n- Idea principal.\n- Aplicación para la familia.\n- Llamado a la oración.\n\n".
                    'Reemplaza este texto desde Panel › Enseñanzas.',
                'author_name' => 'Predicador por confirmar',
                'image_alt' => $colors ? 'Imagen de prueba' : null,
                'published_on' => today()->subDays($i * 5)->toDateString(),
                'is_published' => true,
            ]);

            if ($colors) {
                $teaching->replaceImage($this->image(1280, 720, $colors[0], $colors[1], 'mountains'));
            }
        }
    }

    private function createVerses(): void
    {
        $verses = [
            ['Hechos 16:31', 'Y ellos dijeron: Cree en el Señor Jesucristo, y serás salvo tú, y tu casa.'],
            ['Efesios 6:4', 'Y vosotros, padres, no provoquéis á ira á vuestros hijos; sino criadlos en disciplina y amonestación del Señor.'],
        ];

        foreach ($verses as $i => [$reference, $text]) {
            Verse::create([
                'reference' => self::PREFIX.' '.$reference, 'text' => $text, 'version' => 'RVR1909',
                'is_published' => true, 'sort_order' => $i + 1,
            ]);
        }
    }

    /** Sin fotos: no se usan rostros de personas que no han dado su autorización. */
    private function createLeaders(): void
    {
        foreach (['Pastor de la iglesia', 'Copastora', 'Líder de jóvenes'] as $i => $role) {
            Leader::create([
                'name' => self::PREFIX.' Nombre y apellido',
                'role' => $role,
                'bio' => 'Reseña de ejemplo. Escribe aquí una breve presentación aprobada por la persona.',
                'is_published' => true, 'sort_order' => $i + 1,
            ]);
        }
    }

    private function createEvents(): void
    {
        $events = [
            ['Vigilia de oración', 10, null, '9:00 p. m.', true],
            ['Campaña evangelística', 24, 26, '7:00 p. m.', true],
            ['Retiro de jóvenes', 45, 46, null, false],
            ['Culto de acción de gracias', -20, null, '10:00 a. m.', false],
        ];

        foreach ($events as [$title, $startOffset, $endOffset, $time, $withImage]) {
            $event = ChurchEvent::create([
                'title' => self::PREFIX.' '.$title,
                'slug' => ChurchEvent::uniqueSlug('ejemplo '.$title),
                'starts_on' => today()->addDays($startOffset)->toDateString(),
                'ends_on' => $endOffset ? today()->addDays($endOffset)->toDateString() : null,
                'time_label' => $time,
                'location' => 'Lugar por confirmar',
                'summary' => 'Resumen de ejemplo del evento. Reemplázalo desde Panel › Eventos.',
                'body' => "Descripción de ejemplo del evento.\n\n- Qué se hará.\n- A quién está dirigido.\n- Qué llevar.",
                'image_alt' => $withImage ? 'Imagen de prueba' : null,
                'is_published' => true,
            ]);

            if ($withImage) {
                $event->replaceImage($this->image(1280, 720, [0, 46, 93], [244, 127, 18], 'mountains'));
            }
        }
    }

    private function createFaqs(): void
    {
        $faqs = [
            ['¿Necesito inscribirme para asistir a la convención?', 'Respuesta de ejemplo. Escribe aquí la indicación oficial de la organización.'],
            ['¿Qué debo llevar a los cultos?', 'Respuesta de ejemplo. Indica aquí las recomendaciones de la organización (tu Biblia, abrigo para el clima de Huaraz…).'],
            ['¿Hay lugar para niños?', 'Respuesta de ejemplo. Indica aquí si habrá actividades o espacio para niños.'],
        ];

        foreach ($faqs as $i => [$question, $answer]) {
            Faq::create(['question' => self::PREFIX.' '.$question, 'answer' => $answer, 'is_published' => true, 'sort_order' => $i + 1]);
        }
    }

    // ---------------------------------------------------------------------
    // Convención
    // ---------------------------------------------------------------------

    private function createProgram(ChurchEvent $event): void
    {
        $slots = [
            ['09:00', '10:30', 'Oración de la mañana'],
            ['11:00', '13:00', 'Enseñanza bíblica'],
            ['16:00', '17:30', 'Reunión de jóvenes'],
            ['19:00', '22:00', 'Culto evangelístico'],
        ];

        // Sin fechas no hay días de programa que crear.
        foreach ($event->hasDates() ? CarbonPeriod::create($event->starts_on, $event->endDate()) : [] as $date) {
            $day = ProgramDay::query()->forEvent($event)->whereDate('date', $date)->first()
                ?? ProgramDay::query()->forceCreate(['church_event_id' => $event->id, 'date' => Carbon::parse($date)->toDateString()]);

            foreach ($slots as [$start, $end, $title]) {
                ProgramItem::create([
                    'program_day_id' => $day->id, 'starts_at' => $start, 'ends_at' => $end,
                    'title' => self::PREFIX.' '.$title, 'speaker' => 'Por confirmar', 'is_published' => true,
                ]);
            }
        }
    }

    // ---------------------------------------------------------------------
    // Eliminación
    // ---------------------------------------------------------------------

    private function remove(): int
    {
        $removed = 0;
        $like = self::PREFIX.'%';

        // Uno a uno (no con delete masivo) para borrar imágenes y auditar.
        foreach ([
            [Banner::class, 'title'], [GalleryPhoto::class, 'image_alt'], [Teaching::class, 'title'], [ChurchEvent::class, 'title'],
            [Verse::class, 'reference'], [Leader::class, 'name'], [Faq::class, 'question'],
            [ProgramItem::class, 'title'],
        ] as [$model, $column]) {
            foreach ($model::where($column, 'like', $like)->get() as $record) {
                $record->delete();
                $removed++;
            }
        }

        // Textos del sitio: solo se vacían los que siguen siendo los de ejemplo.
        if ($site = SiteSetting::query()->first()) {
            foreach ($this->siteTexts() as $field => $value) {
                if ($site->getAttribute($field) === $value) {
                    $site->setAttribute($field, null);
                }
            }
            $site->save();
        }

        $this->info("Elementos de ejemplo eliminados: {$removed}.");

        return self::SUCCESS;
    }

    // ---------------------------------------------------------------------
    // Imágenes de prueba
    // ---------------------------------------------------------------------

    /**
     * Imagen JPG de prueba generada con GD (degradado, motivo y rótulo).
     *
     * @param  array{int, int, int}  $from
     * @param  array{int, int, int}  $to
     */
    private function image(int $width, int $height, array $from, array $to, string $motif): UploadedFile
    {
        $img = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            $t = $y / $height;
            $color = imagecolorallocate($img, ...array_map(fn ($a, $b) => (int) round($a + ($b - $a) * $t), $from, $to));
            imageline($img, 0, $y, $width, $y, $color);
        }

        $shade = imagecolorallocatealpha($img, 0, 0, 0, 80);
        $light = imagecolorallocatealpha($img, 255, 255, 255, 70);

        if ($motif === 'mountains') {
            imagefilledpolygon($img, [0, $height, (int) ($width * .22), (int) ($height * .45), (int) ($width * .4), (int) ($height * .7), (int) ($width * .62), (int) ($height * .35), (int) ($width * .85), (int) ($height * .65), $width, (int) ($height * .5), $width, $height], $shade);
            imagefilledpolygon($img, [(int) ($width * .62), (int) ($height * .35), (int) ($width * .58), (int) ($height * .42), (int) ($width * .66), (int) ($height * .42)], $light);
        } else {
            imagefilledellipse($img, (int) ($width / 2), (int) ($height * .55), (int) ($width * .55), (int) ($height * .62), $shade);
            imagefilledellipse($img, (int) ($width / 2), (int) ($height * .52), (int) ($width * .46), (int) ($height * .5), $light);
        }

        $this->label($img, 'IMAGEN DE PRUEBA', $width, $height);

        $path = tempnam(sys_get_temp_dir(), 'demo').'.jpg';
        imagejpeg($img, $path, 82);

        return new UploadedFile($path, 'demo.jpg', 'image/jpeg', null, true);
    }

    private function label(\GdImage $img, string $text, int $width, int $height): void
    {
        $white = imagecolorallocatealpha($img, 255, 255, 255, 30);
        $font = collect(['C:\Windows\Fonts\arialbd.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'])->first(fn ($f) => is_file($f));

        // Esquina superior derecha: no compite con el texto ni con los controles del carrusel.
        if ($font && function_exists('imagettftext')) {
            $size = (int) ($width / 60);
            $box = imagettfbbox($size, 0, $font, $text);
            imagettftext($img, $size, 0, $width - ($box[2] - $box[0]) - (int) ($width * .03), (int) ($height * .08) + $size, $white, $font, $text);

            return;
        }

        imagestring($img, 5, $width - strlen($text) * 9 - 20, 20, $text, $white);
    }
}
