<?php

namespace App\Http\Controllers;

use App\Models\DoctrineBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Una página del Doctrinario. Solo con enlace firmado y vigente (middleware
 * «signed» de la ruta) y solo para el propio sitio: otro sitio no puede
 * insertarla ni enlazarla, y el navegador la bloquea fuera de este origen.
 */
class DoctrinePageController extends Controller
{
    public function __invoke(Request $request, DoctrineBook $book, int $page): StreamedResponse
    {
        // Sin publicar, solo lo ve quien gestiona el contenido (vista previa del panel).
        $visible = $book->is_published || Gate::allows('manage-content');
        abort_unless($visible && $book->isReady() && $page >= 1 && $page <= $book->page_count, 404);
        abort_if($this->fromAnotherSite($request), 403);

        $disk = Storage::disk(DoctrineBook::DISK);
        abort_unless($disk->exists($book->pagePath($page)), 404);

        return $disk->response($book->pagePath($page), "pagina-{$page}.webp", [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, max-age=10800',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'X-Robots-Tag' => 'noindex, nofollow, noimageindex',
            'Content-Disposition' => 'inline',
        ]);
    }

    /** Petición hecha desde una página de otro sitio (inserción o enlace directo ajeno). */
    private function fromAnotherSite(Request $request): bool
    {
        $site = $request->header('Sec-Fetch-Site');

        if ($site !== null) {
            return ! in_array($site, ['same-origin', 'none'], true);
        }

        // Navegadores sin Sec-Fetch-*: se mira el Referer, si lo hay.
        $referer = $request->headers->get('referer');

        return $referer !== null && parse_url($referer, PHP_URL_HOST) !== $request->getHost();
    }
}
