<?php

namespace App\Controller;

use App\Entity\Maxfield;
use App\Repository\MaxfieldRepository;
use App\Repository\WaypointRepository;
use App\Service\MaxFieldGenerator;
use App\Service\MaxFieldHelper;
use App\Type\MaxfieldStatus;
use Doctrine\ORM\EntityManagerInterface;
use Elkuku\MaxfieldParser\JsonHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: 'max-fields')]
#[IsGranted('ROLE_ADMIN')]
class MaxFieldsController extends BaseController
{
    #[Route(path: '/', name: 'max_fields')]
    public function index(
        MaxfieldRepository $maxfieldRepository,
        MaxFieldHelper $maxFieldHelper
    ): Response {
        $maxfieldFiles = $maxFieldHelper->getList();
        $maxfields = [];

        foreach ($maxfieldRepository->findAll() as $maxfield) {

            $maxfieldStatus = (new MaxfieldStatus($maxFieldHelper))
                ->fromMaxfield($maxfield);
            $maxfields[] = $maxfieldStatus;

            $index = array_search($maxfieldStatus->getPath(), $maxfieldFiles);
            if ($index) {
                unset($maxfieldFiles[$index]);
            }
        }

        return $this->render(
            'maxfield/index.html.twig',
            [

                'maxfields'      => $maxfields,
                'maxfieldFiles' => $maxfieldFiles,
            ]
        );
    }

    #[Route(path: '/show/{item}', name: 'max_fields_result')]
    public function display(
        MaxFieldHelper $maxFieldHelper,
        string $item
    ): Response {
        return $this->render(
            'maxfield/result.html.twig',
            [
                'item'            => $item,
                'info'            => $maxFieldHelper->getMaxField($item),
                'maxfieldVersion' => $maxFieldHelper->getMaxfieldVersion(),
            ]
        );
    }

    #[Route('/play/{id}', name: 'maxfield_play', methods: ['GET'])]
    public function play(
        MaxFieldHelper $maxFieldHelper,
        Maxfield $maxfield
    ): Response {
        $json = (new JsonHelper())
            ->getJson($maxFieldHelper->getParser($maxfield->getPath()));

        return $this->render(
            'maxfield/play.html.twig',
            [
                'maxfield' => $maxfield,
                'jsonData' => $json,
            ]
        );
    }

    #[Route(path: '/export', name: 'export-maxfields')]
    public function generateMaxFields(
        WaypointRepository $repository,
        MaxFieldGenerator $maxFieldGenerator,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $points = $request->request->all('points');

        if (!$points) {
            throw new NotFoundHttpException('No waypoints selected.');
        }

        $wayPoints = $repository->findBy(['id' => $points]);
        $maxField = $maxFieldGenerator->convertWayPointsToMaxFields($wayPoints);

        $buildName = $request->request->get('buildName');
        $playersNum = (int)$request->request->get('players_num') ?: 1;
        $options = [
            'skip_plots'      => $request->request->getBoolean('skip_plots'),
            'skip_step_plots' => $request->request->getBoolean(
                'skip_step_plots'
            ),
        ];

        $timeStamp = date('Y-m-d');
        $projectName = $playersNum.'pl-'.$timeStamp.'-'.$buildName;

        $maxFieldGenerator->generate(
            $projectName,
            $maxField,
            $playersNum,
            $options
        );

        $maxfield = (new Maxfield())
            ->setName($projectName)
            ->setPath($projectName)
            ->setOwner($this->getUser());

        $entityManager->persist($maxfield);
        $entityManager->flush();

        return $this->render(
            'maxfield/status.html.twig',
            [
                'maxfield' => $maxfield,
            ]
        );
    }

    #[Route(path: '/delete/{id}', name: 'max_fields_delete')]
    public function delete(
        MaxFieldGenerator $maxFieldGenerator,
        EntityManagerInterface $entityManager,
        Maxfield $maxfield,
    ): Response {
        $item = $maxfield->getPath();
        try {
            $maxFieldGenerator->remove((string)$item);

            $entityManager->remove($maxfield);
            $entityManager->flush();

            $this->addFlash('success', sprintf('%s has been removed.', $item));
        } catch (IOException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        }

        return $this->redirectToRoute('max_fields');
    }

    #[Route(path: '/delete-files/{item}', name: 'maxfield_delete_files')]
    public function deleteFiles(
        MaxFieldGenerator $maxFieldGenerator,
        string $item,
    ): Response {
        try {
            $maxFieldGenerator->remove($item);

            $this->addFlash('success', sprintf('%s has been removed.', $item));
        } catch (IOException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        }

        return $this->redirectToRoute('max_fields');
    }

    #[Route(path: '/status/{id}', name: 'maxfield_status')]
    public function status(MaxFieldHelper $maxFieldHelper, Maxfield $maxfield):JsonResponse
    {
        $status = (new MaxfieldStatus($maxFieldHelper))
            ->fromMaxfield($maxfield);

        return $this->json($status);
    }

    #[Route(path: '/view-status/{id}', name: 'maxfield_view_status')]
    public function viewStatus(Maxfield $maxfield):Response
    {
        return $this->render(
            'maxfield/status.html.twig',
            [
                'maxfield' => $maxfield,
            ]
        );
    }
}
