<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/map')]
class MapController extends BaseController
{
    #[Route(path: '/maxfield', name: 'map-maxfield', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function maxfield(
        #[Autowire('%env(APP_DEFAULT_LAT)%')] float $defaultLat,
        #[Autowire('%env(APP_DEFAULT_LON)%')] float $defaultLon,
        #[Autowire('%env(APP_DEFAULT_ZOOM)%')] float $defaultZoom,
    ): Response {
        $lat = $this->getUser()?->getParam('lat') ?: $defaultLat;
        $lon = $this->getUser()?->getParam('lon') ?: $defaultLon;
        $zoom = $this->getUser()?->getParam('zoom') ?: $defaultZoom;

        return $this->render('maps/maxfield.html.twig', [
            'defaultLat' => $lat,
            'defaultLon' => $lon,
            'defaultZoom' => $zoom,
        ]);
    }

    #[Route(path: '/edit', name: 'map-edit', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(): Response
    {
        return $this->render('maps/edit.html.twig');
    }
}
