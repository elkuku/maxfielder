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

        if ($request->query->get('preview')) {
            return $this->render('default/_searchPreview.html.twig', [
                'maxfields' => $maxfields,
                'searchTerm' => $searchTerm,
            ]);
        }

        return $this->render(
            'default/index.html.twig',
            [
                'maxfields' => $maxfields,
                'favourites' => $favourites,
                'searchTerm' => $searchTerm,
            ]
        );
    }
}
