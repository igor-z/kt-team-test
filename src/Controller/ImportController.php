<?php

declare(strict_types=1);

namespace App\Controller;

use App\Import\Importer;
use App\Import\XMLDataReader;
use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class ImportController extends AbstractController
{
    #[Route('/import', 'products_import', methods: ['POST', 'GET'])]
    public function indexAction(
        Request $request,
        Kernel $kernel,
        Session $session,
        XMLDataReader $dataReader,
        Importer $importer,
        Filesystem $filesystem,
    ): Response {
        if ($request->isMethod('POST')) {
            ini_set('max_execution_time', 0);
            $fileName = sha1($request->request->get('filename'));

            $file = "{$kernel->getProjectDir()}/var/uploaded/{$session->getId()}/$fileName";

            $importer->import(
                $dataReader->read($file)
            );

            $filesystem->remove($file);

            return new Response('');
        }

        return $this->render('products/import.html.twig');
    }
}