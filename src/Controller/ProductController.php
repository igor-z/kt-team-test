<?php

declare(strict_types=1);

namespace App\Controller;

use App\Kernel;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends CRUDController
{
    #[Route('/products/import', 'products_import', methods: ['POST', 'GET'])]
    public function importAction(Request $request, Kernel $kernel, Filesystem $filesystem, Session $session): Response
    {
        if ($request->isMethod('POST')) {
            $fileName = sha1($request->query->get('fileName'));
            $file = "{$kernel->getProjectDir()}/var/uploaded/{$session->getId()}/$fileName";

            $filesystem->remove($file);

            $this->redirect($this->admin->generateUrl('list'));
        }

        return $this->render('products/import.html.twig');
    }

    #[Route('/products/upload-import-file', 'products_upload_import_file', methods: ['POST'])]
    public function uploadImportFileAction(Request $request, Filesystem $filesystem, Kernel $kernel, Session $session): Response
    {
        $contentRange = $this->parseContentRange($request->headers->get('content-range'));

        $fileName = sha1($request->query->get('fileName'));

        $file = "{$kernel->getProjectDir()}/var/uploaded/{$session->getId()}/$fileName";

        if ($contentRange['start'] === 0) {
            $filesystem->dumpFile($file, $request->getContent());
        } else {
            $filesystem->appendToFile($file, $request->getContent());
        }

        return new Response('OK');
    }

    /**
     * @param string $contentRange
     * @return array{
     *     start: int,
     *     end: int,
     *     size: int
     * }
     */
    private function parseContentRange(string $contentRange): array
    {
        if (!preg_match('/(\w+) (\d+)-(\d+)\/(\d+)/i', $contentRange, $matches)) {
            throw new BadRequestHttpException('wrong content-range format');
        }

        [, $unit, $start, $end, $size] = $matches;
        if ($unit !== 'bytes') {
            throw new BadRequestHttpException('content-range supports only bytes');
        }


        return [
            'start' => (int) $start,
            'end' => (int) $end,
            'size' => (int) $size,
        ];
    }
}