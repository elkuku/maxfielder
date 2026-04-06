<?php

declare(strict_types=1);

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
    public function __construct(
        private readonly WaypointRepository $waypointRepo,
        private readonly WayPointParser $wayPointParser,
        private readonly WayPointHelper $wayPointHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route(path: '/import', name: 'import', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
    ): Response
    {
        $form = $this->createForm(ImportFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var array<string> $data */
                $data = $form->getData();
                $waypoints = $this->wayPointParser->parse($data);
                $count = $this->storeWayPoints(
                    $waypoints,
                    $this->waypointRepo,
                    $this->wayPointHelper,
                    $this->entityManager,
                    isset($data['forceUpdate']) && (bool) $data['forceUpdate'],
                );
                if ($count !== 0) {
                    $this->addFlash('success', $count.' Waypoint(s) imported!');
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
        array $wayPoints,
        WaypointRepository $repository,
        WayPointHelper $wayPointHelper,
        EntityManagerInterface $entityManager,
        bool $forceUpdate = false,
    ): int
    {
        $currentWayPoints = $repository->findAll();
        $cnt = 0;

        foreach ($wayPoints as $wayPoint) {
            $existing = $this->findExistingWaypoint($wayPoint, $currentWayPoints);

            if ($existing instanceof Waypoint) {
                if ($this->updateExistingWaypoint($existing, $wayPoint, $wayPointHelper, $entityManager, $forceUpdate)) {
                    ++$cnt;
                }

                continue;
            }

            $wayPoint->setName($wayPointHelper->cleanName((string)$wayPoint->getName()));
            $entityManager->persist($wayPoint);
            ++$cnt;
        }

        $entityManager->flush();

        return $cnt;
    }

    /**
     * @param Waypoint[] $existingWayPoints
     */
    private function findExistingWaypoint(Waypoint $newWayPoint, array $existingWayPoints): ?Waypoint
    {
        foreach ($existingWayPoints as $currentWayPoint) {
            if ($newWayPoint->getLat() === $currentWayPoint->getLat()
                && $newWayPoint->getLon() === $currentWayPoint->getLon()
            ) {
                return $currentWayPoint;
            }
        }

        return null;
    }

    private function updateExistingWaypoint(
        Waypoint $existing,
        Waypoint $newWayPoint,
        WayPointHelper $wayPointHelper,
        EntityManagerInterface $entityManager,
        bool $forceUpdate
    ): bool {
        if ($existing->getGuid() === $newWayPoint->getGuid()) {
            if ($forceUpdate) {
                $existing
                    ->setName($wayPointHelper->cleanName((string)$newWayPoint->getName()))
                    ->setLat($newWayPoint->getLat() ?? 0.0)
                    ->setLon($newWayPoint->getLon() ?? 0.0)
                    ->setImage($newWayPoint->getImage());

                $entityManager->persist($existing);
                return true;
            }

            return false;
        }

        if (!$existing->getGuid() && $newWayPoint->getGuid()) {
            $existing->setGuid($newWayPoint->getGuid());
            $entityManager->persist($existing);
        }

        return false;
    }
}
