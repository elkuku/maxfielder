<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PortalCalcController extends AbstractController
{
    #[Route('/portalcalc', name: 'app_portalcalc', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('portalcalc/index.html.twig');
    }
}
