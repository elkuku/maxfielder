<?php

namespace App\Controller;

use App\Entity\Waypoint;
use App\Form\WaypointFormType;
use App\Form\WaypointFormTypeDetails;
use App\Helper\Paginator\PaginatorTrait;
use App\Repository\WaypointRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_ADMIN')]
class WaypointsController extends AbstractController
{
    // use PaginatorTrait;

    #[Route(path: '/waypoints', name: 'waypoints')]
    public function index(
        WaypointRepository $repository,
        Request $request
    ): Response {
        $paginatorOptions = $this->getPaginatorOptions($request);

        $waypoints = $repository->getRawList($paginatorOptions);

        $paginatorOptions->setMaxPages(
            (int)ceil(
                $waypoints->count() / $paginatorOptions->getLimit()
            )
        );

        return $this->render(
            'waypoints/index.html.twig',
            [
                'waypoints'        => $waypoints,
                'paginatorOptions' => $paginatorOptions,
            ]
        );
    }

    #[Route(path: '/waypoint/{id}', name: 'waypoints_edit')]
    public function edit(
        Request $request,
        Waypoint $waypoint,
        EntityManagerInterface $entityManager
    ): RedirectResponse|Response {
        $form = $this->createForm(WaypointFormType::class, $waypoint);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $waypoint = $form->getData();
            $entityManager->persist($waypoint);
            $entityManager->flush();
            $this->addFlash('success', 'Waypoint updated!');

            return $this->redirectToRoute('waypoints');
        }

        return $this->render(
            'waypoint/edit.html.twig',
            [
                'form'     => $form->createView(),
                'waypoint' => $waypoint,
            ]
        );
    }

    #[Route(path: '/waypoint-details/{id}', name: 'waypoints_edit_details')]
    public function editDetails(
        Request $request,
        Waypoint $waypoint,
        EntityManagerInterface $entityManager
    ): RedirectResponse|Response {
        $form = $this->createForm(WaypointFormTypeDetails::class, $waypoint);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $waypoint = $form->getData();
            $entityManager->persist($waypoint);
            $entityManager->flush();
            $this->addFlash('success', 'Waypoint updated!');

            $redirectUri = (string)$request->request->get('redirectUri');

            if ($redirectUri) {
                return $this->redirect($redirectUri);
            }

            return $this->redirectToRoute('waypoints');
        }

        return $this->render(
            'waypoints/edit_details.html.twig',
            [
                'form'     => $form->createView(),
                'waypoint' => $waypoint,
            ]
        );
    }

    #[Route(path: '/waypoint-remove/{id}', name: 'waypoints_remove')]
    public function remove(
        Waypoint $waypoint,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $entityManager->remove($waypoint);

        $entityManager->flush();

        $this->addFlash('success', 'Waypoint removed!');

        return $this->redirectToRoute('waypoints');
    }

    #[Route(path: '/waypoints_run', name: 'run-waypoints')]
    public function waypoints(WaypointRepository $repository): Response
    {
        return $this->render(
            'waypoints/index.html.twig',
            [
                'waypoints' => $repository->findAll(),
            ]
        );
    }

    #[Route(path: '/waypoints_map', name: 'map-waypoints')]
    public function map(WaypointRepository $repository): JsonResponse
    {
        $waypoints = $repository->findAll();

        $wps = [];

        foreach ($waypoints as $waypoint) {
            $w = [];

            $w['name'] = $waypoint->getName();
            $w['lat'] = $waypoint->getLat();
            $w['lng'] = $waypoint->getLon();
            $w['id'] = $waypoint->getId();

            $wps[] = $w;
        }

        return $this->json($wps);
    }

    #[Route(path: '/waypoints_info/{id}', name: 'waypoints-info')]
    public function info(Waypoint $waypoint): Response
    {
        return $this->render(
            'waypoint/info.html.twig',
            [
                'waypoint' => $waypoint,
            ]
        );
    }
}
