<?php

namespace App\Controller;

use App\Repository\MaxfieldRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use UnexpectedValueException;

class DefaultController extends BaseController
{
    public function __construct(private readonly MaxfieldRepository $maxfieldRepository) {}

    #[Route('/', name: 'default', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        $favourites = $user ? $user->getFavourites() : [];
        $searchTerm = $request->query->get('q');
        $maxfields = $this->maxfieldRepository->search($searchTerm);
        $template = 'index';

        $partial = $request->query->get('partial');

        if ($partial) {
            if (in_array(
                $partial,
                ['searchPreview', 'favourites', 'contentList']
            )
            ) {
                $template = "_$partial";
            } else {
                throw new UnexpectedValueException('Invalid partial');
            }
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
