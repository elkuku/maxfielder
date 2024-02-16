<?php

namespace App\Controller;

use App\Entity\Waypoint;
use App\Form\ImportFormType;
use App\Parser\WayPointParser;
use App\Repository\WaypointRepository;
use App\Service\WayPointHelper;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ImportController extends AbstractController
{
    #[Route(path: '/import', name: 'import', methods: ['GET', 'POST'])]
    public function index(
        Request                $request,
        WaypointRepository     $waypointRepo,
        WayPointParser         $wayPointParser,
        WayPointHelper         $wayPointHelper,
        EntityManagerInterface $entityManager,
    ): Response
    {
        $form = $this->createForm(ImportFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();
                $waypoints = $wayPointParser->parse($data);
                $count = $this->storeWayPoints(
                    $waypoints,
                    $waypointRepo,
                    $wayPointHelper,
                    $entityManager,
                    isset($data['forceUpdate']) && $data['forceUpdate'],
                );
                if ($count) {
                    $this->addFlash('success', $count . ' Waypoint(s) imported!');
                } else {
                    $this->addFlash('warning', 'No Waypoints imported!');
                }

                return $this->redirectToRoute('default');
            } catch (Exception $exception) {
                $this->addFlash('danger', $exception->getMessage());
            }
        }

        return $this->render(
            'import/index.html.twig',
            [
                'form' => $form,
            ]
        );
    }

    /**
     * @param array<Waypoint> $wayPoints
     */
    private function storeWayPoints(
        array                  $wayPoints,
        WaypointRepository     $repository,
        WayPointHelper         $wayPointHelper,
        EntityManagerInterface $entityManager,
        bool                   $forceUpdate = false,
    ): int
    {
        $currentWayPoints = $repository->findAll();

        $cnt = 0;

        foreach ($wayPoints as $wayPoint) {
            foreach ($currentWayPoints as $currentWayPoint) {
                if ($wayPoint->getLat() === $currentWayPoint->getLat()
                    && $wayPoint->getLon() === $currentWayPoint->getLon()
                ) {
                    if ($currentWayPoint->getGuid() === $wayPoint->getGuid()) {
                        // Waypoint already in DB
                        if ($forceUpdate) {
                            $currentWayPoint
                                ->setName($wayPointHelper->cleanName((string)$wayPoint->getName()))
                                ->setLat($wayPoint->getLat())
                                ->setLon($wayPoint->getLon())
                                ->setImage($wayPoint->getImage());

                            $entityManager->persist($currentWayPoint);
                            ++$cnt;
                        }

                        continue 2;
                    }

                    if (!$currentWayPoint->getGuid() && $wayPoint->getGuid()) {
                        // guid is missing
                        $currentWayPoint->setGuid($wayPoint->getGuid());

                        $entityManager->persist($currentWayPoint);
                    }

                    continue 2;
                }
            }

            // Waypoint not in DB
            $wayPoint->setName(
                $wayPointHelper->cleanName((string)$wayPoint->getName())
            );

            $entityManager->persist($wayPoint);

            ++$cnt;
        }

        $entityManager->flush();

        return $cnt;
    }
}
