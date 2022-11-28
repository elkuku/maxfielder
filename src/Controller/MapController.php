<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/map')]
#[IsGranted('ROLE_ADMIN')]
class MapController extends AbstractController
{
    #[Route(path: '/maxfield', name: 'map-maxfield', methods: ['GET'])]
    public function maxfield(): Response
    {
        return $this->render('maps/maxfield.html.twig');
    }

    #[Route(path: '/edit', name: 'map-edit', methods: ['GET'])]
    public function edit(): Response
    {
        return $this->render('maps/edit.html.twig');
    }
}
