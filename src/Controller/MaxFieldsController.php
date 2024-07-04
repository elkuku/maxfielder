<?php

namespace App\Controller;

use App\Entity\Maxfield;
use App\Enum\MapBoxProfilesEnum;
use App\Enum\MapBoxStylesEnum;
use App\Enum\MapProvidersEnum;
use App\Form\MaxfieldFormType;
use App\Repository\MaxfieldRepository;
use App\Repository\WaypointRepository;
use App\Service\IngressHelper;
use App\Service\MaxFieldGenerator;
use App\Service\MaxFieldHelper;
use App\Type\MaxfieldCreateType;
use App\Type\MaxfieldStatus;
use App\Type\UserDataType;
use Doctrine\ORM\EntityManagerInterface;
use Elkuku\MaxfieldParser\JsonHelper;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UnexpectedValueException;

#[Route(path: 'maxfield')]
#[IsGranted('ROLE_AGENT')]
class MaxFieldsController extends BaseController
{
    #[Route(path: '/list', name: 'maxfields', methods: ['GET'])]
    public function index(
        MaxfieldRepository $maxfieldRepository,
        Request            $request,
    ): Response
    {
        $page = $request->query->get('page', 1);
        $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter($maxfieldRepository->createQueryBuilderSearch()),
            $page,
            9999
        );

        $template = 'index';

        $partial = $request->query->get('partial');

        if ($partial) {
            if (in_array(
                $partial,
                ['list_lg', 'list_sm']
            )
            ) {
                $template = "_$partial";
            } else {
                throw new UnexpectedValueException('Invalid partial');
            }
        }

        return $this->render(
            "maxfield/$template.html.twig",
            [
                'favourites' => $this->getUser()?->getFavourites(),
                'pagerfanta' => $pagerfanta,
                'page' => $page,
            ]
        );
    }

    #[Route(path: '/check', name: 'maxfields_check', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function check(
        MaxfieldRepository $maxfieldRepository,
        MaxFieldHelper     $maxFieldHelper,
    ): Response
    {
        $maxfieldFiles = $maxFieldHelper->getList();
        $dbMaxfields = $maxfieldRepository->findAll();

        $maxfields = [];

        foreach ($dbMaxfields as $maxfield) {
            $maxfieldStatus = (new MaxfieldStatus($maxFieldHelper))
                ->fromMaxfield($maxfield);
            $maxfields[] = $maxfieldStatus;

            $index = array_search($maxfieldStatus->getPath(), $maxfieldFiles);
            if (false !== $index) {
                unset($maxfieldFiles[$index]);
            }
        }

        return $this->render(
            'maxfield/check.html.twig',
            [
                'maxfields' => $maxfields,
                'maxfieldFiles' => $maxfieldFiles,
            ]
        );
    }

    #[Route(path: '/show/{path:maxfield}', name: 'max_fields_result', methods: ['GET'])]
    public function display(
        MaxFieldHelper $maxFieldHelper,
        MaxField       $maxfield,
    ): Response
    {
        $info = $maxFieldHelper->getMaxField($maxfield->getPath());
        $waypointIdMap = $maxFieldHelper->getWaypointsIdMap($maxfield->getPath());

        return $this->render(
            'maxfield/result.html.twig',
            [
                'maxfield' => $maxfield,
                'info' => $info,
                'waypointIdMap' => $waypointIdMap,
            ]
        );
    }

    #[Route(path: '/clear-user-data/{path:maxfield}', name: 'maxfield_clear_user_data', methods: ['POST'])]
    public function clearUserData(
        MaxField               $maxfield,
        EntityManagerInterface $entityManager,
        Request                $request,
    ): JsonResponse
    {
        $response = [];
        try {
            $data = json_decode($request->getContent(), true);

            $agentNum = (int)$data['agentNum'];

            $maxfield->setCurrentPointWithUser('-1', $agentNum);
            $maxfield->setFarmDoneWithUser([], $agentNum);
            $maxfield->setUserKeysWithUser([], $agentNum);
            $entityManager->flush();
            $response ['result'] = 'cleared';
            $code = 200;
        } catch (\Exception $exception) {
            $response['error'] = $exception->getMessage();
            $code = 500;
        }

        return $this->json($response, $code);
    }

    #[Route(path: '/submit-user-data/{path:maxfield}', name: 'maxfield_submit_user_data', methods: ['POST'])]
    public function submitUserData(
        MaxFieldHelper         $maxFieldHelper,
        MaxField               $maxfield,
        Request                $request,
        IngressHelper          $ingressHelper,
        EntityManagerInterface $entityManager,
    ): JsonResponse
    {
        $response = [];
        $status = 200;

        $data = json_decode($request->getContent(), true);

        $agentNum = (int)$data['agentNum'];

        $keys = isset($data['keys']) ? (string)$data['keys'] : null;
        $currentPoint = isset($data['current_point']) ? (string)$data['current_point'] : null;
        $farmDone = isset($data['farm_done']) ? (array)$data['farm_done'] : null;

        if ($currentPoint !== null) {
            $maxfield->setCurrentPointWithUser($currentPoint, $agentNum);
            $entityManager->flush();
        }

        if ($farmDone !== null) {
            $maxfield->setFarmDoneWithUser($farmDone, $agentNum);
            $entityManager->flush();
        }

        if ($keys) {
            $waypointIdMap = $maxFieldHelper->getWaypointsIdMap($maxfield->getPath());
            try {
                $existingKeys = $ingressHelper->getExistingKeysForMaxfield($waypointIdMap, $keys);
                if ($existingKeys) {
                    $maxfield->setUserKeysWithUser($existingKeys, $agentNum);
                    $response['result'] = sprintf('Added keyinfo for %d portals.', count($existingKeys));

                    $entityManager->flush();
                } else {
                    $response['error'] = 'No keys found :(';
                    $status = 404;
                }
            } catch (\Exception $exception) {
                $response['error'] = $exception->getMessage();
                $status = 500;
            }
        }

        return $this->json($response, $status);
    }

    #[Route('/play/{path:maxfield}', name: 'maxfield_play', methods: ['GET'])]
    public function play(
        MaxFieldHelper $maxFieldHelper,
        Maxfield       $maxfield
    ): Response
    {
        $user = $this->getUser();
        $userSettings = $user?->getUserParams();

        if (MapProvidersEnum::mapbox === $userSettings->mapProvider) {
            return $this->render(
                'maxfield/play2.html.twig',
                [
                    'maxfield' => $maxfield,
                    'mapboxGlToken' => $userSettings->mapboxApiKey,
                    'mapboxStylesOptions' => MapBoxStylesEnum::forSelect(),
                    'mapboxProfilesOptions' => MapBoxProfilesEnum::forSelect(),
                    'defaultStyle' => $userSettings->defaultStyle,
                    'defaultProfile' => $userSettings->defaultProfile,
                ]
            );
        }

        return $this->render(
            'maxfield/play.html.twig',
            [
                'maxfield' => $maxfield,
                'jsonData' => (new JsonHelper())
                    ->getJson($maxFieldHelper->getParser($maxfield->getPath())),
                'waypointIdMap' => $maxFieldHelper->getWaypointsIdMap($maxfield->getPath()),
            ]
        );
    }

    #[Route('/get-data/{path:maxfield}', name: 'maxfield_get_data', methods: ['GET'])]
    public function getData(
        MaxFieldHelper $maxFieldHelper,
        Maxfield       $maxfield
    ): JsonResponse
    {
        $json = (new JsonHelper())
            ->getJsonData($maxFieldHelper->getParser($maxfield->getPath()));

        return $this->json([
            'jsonData' => $json,
            'waypointIdMap' => $maxFieldHelper->getWaypointsIdMap($maxfield->getPath()),
        ]);


    }

    #[Route('/get-user-data/{path:maxfield}', name: 'maxfield_get_user_data', methods: ['POST'])]
    public function getUserData(
        Maxfield                          $maxfield,
        #[MapRequestPayload] UserDataType $data,
    ): JsonResponse
    {
        $userData = $maxfield->getUserData();

        if ($userData && array_key_exists($data->userId, $userData)) {
            return $this->json($userData[$data->userId]);
        }

        return $this->json([]);
    }

    #[Route(path: '/export', name: 'export-maxfields', methods: ['POST'])]
    public function generateMaxFields(
        WaypointRepository                      $repository,
        MaxFieldGenerator                       $maxFieldGenerator,
        EntityManagerInterface                  $entityManager,
        #[MapRequestPayload] MaxfieldCreateType $maxfieldType,

    ): Response
    {
        $wayPoints = $repository->findBy(['id' => $maxfieldType->getPoints()]);
        $maxField = $maxFieldGenerator->convertWayPointsToMaxFields($wayPoints);
        $waypointMap = $maxFieldGenerator->getWaypointsMap($wayPoints);

        $options = [
            'skip_plots' => $maxfieldType->skipPlots,
            'skip_step_plots' => $maxfieldType->skipStepPlots,
        ];

        $projectName = $maxfieldType->getProjectName();

        $maxFieldGenerator->generate(
            $projectName,
            $maxField,
            $waypointMap,
            $maxfieldType->getPlayersNum(),
            $options
        );

        $maxfield = (new Maxfield())
            ->setName($maxfieldType->buildName)
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

    #[Route(path: '/edit/{id}', name: 'maxfield_edit', methods: [
        'GET',
        'POST',
    ])]
    public function edit(
        Maxfield               $maxfield,
        EntityManagerInterface $entityManager,
        Request                $request,
    ): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted(
            'modify',
            $maxfield,
            'You are not allowed to edit this item :('
        );

        $form = $this->createForm(MaxfieldFormType::class, $maxfield);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $maxfield = $form->getData();
            $entityManager->persist($maxfield);
            $entityManager->flush();
            $this->addFlash('success', 'Maxfield updated!');

            return $this->redirectToRoute('maxfields');
        }

        $template = $request->query->get('partial') ? '_form' : 'edit';

        return $this->render(
            "maxfield/$template.html.twig",
            [
                'form' => $form,
            ]
        );
    }

    #[Route(path: '/delete/{id}', name: 'max_fields_delete', methods: ['GET'])]
    public function delete(
        MaxFieldGenerator      $maxFieldGenerator,
        EntityManagerInterface $entityManager,
        Maxfield               $maxfield,
        Request                $request,
        RouterInterface        $router,
    ): RedirectResponse
    {
        $this->denyAccessUnlessGranted(
            'modify',
            $maxfield,
            'You are not allowed to delete this item :('
        );

        $item = $maxfield->getPath();
        try {
            $maxFieldGenerator->remove((string)$item);

            $entityManager->remove($maxfield);
            $entityManager->flush();

            $this->addFlash(
                'success',
                sprintf('%s has been removed.', $item)
            );
        } catch (IOException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        }

        $referer = $this->getInternalReferer($request, $router);

        return $this->redirectToRoute($referer ?: 'maxfields');
    }

    #[Route(path: '/delete-files/{item}', name: 'maxfield_delete_files', methods: ['GET'])]
    public function deleteFiles(
        MaxFieldGenerator $maxFieldGenerator,
        string            $item,
    ): RedirectResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException(
                'You are not allowed to delete this item :('
            );
        }

        try {
            $maxFieldGenerator->remove($item);

            $this->addFlash('success', sprintf('%s has been removed.', $item));
        } catch (IOException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        }

        return $this->redirectToRoute('maxfields');
    }

    #[Route(path: '/status/{id}', name: 'maxfield_status', methods: ['GET'])]
    public function status(
        MaxFieldHelper $maxFieldHelper,
        Maxfield       $maxfield
    ): JsonResponse
    {
        $status = (new MaxfieldStatus($maxFieldHelper))
            ->fromMaxfield($maxfield);

        return $this->json($status);
    }

    #[Route(path: '/view-status/{id}', name: 'maxfield_view_status', methods: ['GET'])]
    public function viewStatus(Maxfield $maxfield): Response
    {
        return $this->render(
            'maxfield/status.html.twig',
            [
                'maxfield' => $maxfield,
            ]
        );
    }

    #[Route(path: '/toggle-favourite/{id}', name: 'maxfield_toggle_favourite', methods: ['GET'])]
    public function toggleFavourite(
        Maxfield               $maxfield,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $newState = $this->getUser()?->toggleFavourite($maxfield);

        $entityManager->flush();

        return $this->json([
            'new-state' => $newState,
        ]);
    }

    #[Route(path: '/plan', name: 'app_maxfields_plan', methods: ['GET'])]
    public function plan(
        #[Autowire('%env(APP_DEFAULT_LAT)%')] float  $defaultLat,
        #[Autowire('%env(APP_DEFAULT_LON)%')] float  $defaultLon,
        #[Autowire('%env(APP_DEFAULT_ZOOM)%')] float $defaultZoom,
    ): Response
    {
        $lat = $this->getUser()?->getParam('lat') ?: $defaultLat;
        $lon = $this->getUser()?->getParam('lon') ?: $defaultLon;
        $zoom = $this->getUser()?->getParam('zoom') ?: $defaultZoom;

        return $this->render('maxfield/plan.html.twig', [
            'defaultLat' => $lat,
            'defaultLon' => $lon,
            'defaultZoom' => $zoom,
        ]);
    }
}
