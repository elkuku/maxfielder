<?php

namespace App\Controller;

use App\Repository\MaxfieldRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends BaseController
{
    #[Route('/', name: 'default', methods: ['GET'])]
    public function index(
        MaxfieldRepository $maxfieldRepository,
        Request $request
    ): Response {
        $user = $this->getUser();

        $favourites = $user ? $user->getFavourites() : [];
        $searchTerm = $request->query->get('q');
        $maxfields = $maxfieldRepository->search($searchTerm);
        $template = 'index';

        if ($request->query->get('preview')) {
            $template = '_searchPreview';
        } elseif ($request->query->get('favourites')) {
            $template = '_favourites';
        }

        return $this->render(
            "default/$template.html.twig",
            [
                'maxfields' => $maxfields,
                'favourites' => $favourites,
                'searchTerm' => $searchTerm,
            ]
        );
    }
}
