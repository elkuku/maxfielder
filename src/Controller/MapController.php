<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MapController extends BaseController
{
    #[Route(path: '/map/edit', name: 'map-edit', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(): Response
    {
        return $this->render('maps/edit.html.twig');
    }
}
