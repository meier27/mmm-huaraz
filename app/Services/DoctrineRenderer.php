<?php

namespace App\Services;

use App\Models\DoctrineBook;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Convierte el PDF del Doctrinario en una imagen WebP por página (disco
 * privado), con una marca de agua discreta. Usa pdftoppm (Poppler).
 */
class DoctrineRenderer
{
    public function __construct(private readonly DoctrineIndexer $indexer) {}

    /** Lado mayor de cada página, en píxeles: nítido en pantalla y con zoom. */
    private const MAX_SIDE = 1600;

    private const QUALITY = 82;

    public function render(DoctrineBook $book): DoctrineBook
    {
        $disk = Storage::disk(DoctrineBook::DISK);
        $tmp = storage_path('app/tmp/doctrine-'.$book->id.'-'.bin2hex(random_bytes(4)));
        File::ensureDirectoryExists($tmp);

        $book->forceFill(['status' => DoctrineBook::PROCESSING, 'error' => null])->save();

        try {
            $process = new Process([config('services.pdftoppm'), '-png', '-scale-to', (string) self::MAX_SIDE, $disk->path($book->pdf_path), $tmp.'/p']);
            $process->setTimeout(600)->run();

            $pngs = glob($tmp.'/p-*.png') ?: [];
            natsort($pngs);

            if (! $process->isSuccessful() || $pngs === []) {
                throw new RuntimeException(trim($process->getErrorOutput()) ?: 'No se pudo leer el PDF.');
            }

            $disk->deleteDirectory($book->directory().'/pages');
            [$width, $height] = [0, 0];

            foreach (array_values($pngs) as $i => $png) {
                [$width, $height] = $this->convert($png, $disk->path($book->pagePath($i + 1)));
            }

            $book->forceFill([
                'status' => DoctrineBook::READY,
                'page_count' => count($pngs),
                'page_width' => $width,
                'page_height' => $height,
            ])->save();

            // Índice automático solo si no hay uno (no se pisan las correcciones del panel).
            if (empty($book->toc)) {
                $book->forceFill(['toc' => $this->indexer->detect($book)])->save();
            }
        } catch (\Throwable $e) {
            $book->forceFill(['status' => DoctrineBook::FAILED, 'error' => mb_substr($this->friendly($e), 0, 500)])->save();
        } finally {
            File::deleteDirectory($tmp);
        }

        return $book;
    }

    /** PNG → WebP con marca de agua. Devuelve [ancho, alto]. */
    private function convert(string $png, string $target): array
    {
        $image = imagecreatefrompng($png) ?: throw new RuntimeException('Página ilegible: '.basename($png));
        imagepalettetotruecolor($image);
        $this->watermark($image);

        File::ensureDirectoryExists(dirname($target));
        imagewebp($image, $target, self::QUALITY);

        $size = [imagesx($image), imagesy($image)];
        imagedestroy($image);

        return $size;
    }

    /** Texto tenue en la esquina inferior derecha (identifica el origen si se copia). */
    private function watermark(\GdImage $image): void
    {
        $text = 'MMM Huaraz · '.parse_url(config('app.url'), PHP_URL_HOST);
        $width = imagesx($image);
        $height = imagesy($image);
        $color = imagecolorallocatealpha($image, 90, 70, 55, 95); // 0 = opaco, 127 = transparente
        $font = collect(['C:\Windows\Fonts\arial.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', '/usr/share/fonts/dejavu/DejaVuSans.ttf'])
            ->first(fn (string $f) => is_file($f));

        if ($font && function_exists('imagettftext')) {
            $size = max(9, (int) round($width / 70));
            $box = imagettfbbox($size, 0, $font, $text);
            imagettftext($image, $size, 0, $width - ($box[2] - $box[0]) - (int) ($width * .03), $height - (int) ($height * .02), $color, $font, $text);

            return;
        }

        // Fuente básica de GD: solo ASCII.
        $text = str_replace('·', '-', $text);
        imagestring($image, 3, $width - imagefontwidth(3) * strlen($text) - 12, $height - 24, $text, $color);
    }

    private function friendly(\Throwable $e): string
    {
        return str_contains($e->getMessage(), 'not recognized') || str_contains($e->getMessage(), 'not found') || str_contains($e->getMessage(), 'No such file')
            ? 'No se encontró pdftoppm en el servidor. Instala Poppler y revisa PDFTOPPM_PATH en el .env.'
            : $e->getMessage();
    }
}
