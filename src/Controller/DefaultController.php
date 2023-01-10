<?php

namespace App\Controller;

use App\Repository\MaxfieldRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends BaseController
{
    #[Route('/', name: 'default', methods: ['GET'])]
    public function index(MaxfieldRepository $maxfieldRepository): Response
    {
        $user = $this->getUser();

        $favourites = $user ? $user->getFavourites() : [];
        return $this->render(
            'default/index.html.twig',
            [
                'maxfields' => $maxfieldRepository->findAll(),
                'favourites' => $favourites,
            ]
        );
    }
}
