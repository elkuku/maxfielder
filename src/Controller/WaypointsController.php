<?php

namespace App\Controller;

use App\Entity\Waypoint;
use App\Enum\UserRole;
use App\Form\WaypointFormType;
use App\Repository\WaypointRepository;
use App\Service\WayPointHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnexpectedValueException;

class WaypointsController extends AbstractController
{
    public function __construct(
        private readonly WaypointRepository $repository,
        private readonly WayPointHelper $wayPointHelper
    ) {}

    #[Route(path: '/waypoint/{id}', name: 'waypoints_edit', methods: [
        'GET',
        'POST',
    ])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        Waypoint $waypoint,
        EntityManagerInterface $entityManager
    ): RedirectResponse|Response
    {
        $form = $this->createForm(WaypointFormType::class, $waypoint);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $waypoint = $form->getData();
            $entityManager->persist($waypoint);
            $entityManager->flush();
            $this->addFlash('success', 'Waypoint updated!');

            return $this->redirectToRoute('default');
        }

        return $this->render(
            'waypoint/edit.html.twig',
            [
                'form' => $form,
                'waypoint' => $waypoint,
            ]
        );
    }

    #[Route(path: '/waypoint-remove/{id}', name: 'waypoints_remove', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function remove(
        Waypoint $waypoint,
        EntityManagerInterface $entityManager
    ): RedirectResponse
    {
        $entityManager->remove($waypoint);

        $entityManager->flush();

        $this->addFlash('success', 'Waypoint removed!');

        return $this->redirectToRoute('default');
    }

    #[Route(path: '/waypoints_map', name: 'map-waypoints', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    public function map(Request $request): JsonResponse
    {
        $bounds = $request->query->get('bounds');
        if ($bounds) {
            $bounds = explode(',', $bounds);
            if (4 !== count($bounds)) {
                throw new UnexpectedValueException('Invalid bounds');
            }
            $waypoints = $this->repository->findInBounds(
                (float)$bounds[0],
                (float)$bounds[1],
                (float)$bounds[2],
                (float)$bounds[3]
            );
        } else {
            $waypoints = $this->repository->findAll();
        }

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

    #[Route(path: '/waypoints_info/{id}', name: 'waypoints-info', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function info(Waypoint $waypoint): Response
    {
        return $this->render(
            'waypoint/info.html.twig',
            [
                'waypoint' => $waypoint,
            ]
        );
    }

    #[Route(path: '/waypoint_thumb/{guid:waypoint}', name: 'waypoint_thumbnail', methods: ['GET'])]
    #[IsGranted(UserRole::AGENT->value)]
    public function getImageThumbnail(Waypoint $waypoint): BinaryFileResponse
    {
        return new BinaryFileResponse($this->wayPointHelper->getThumbnailPath($waypoint->getGuid(), $waypoint->getImage() ?? ''));
    }
}
