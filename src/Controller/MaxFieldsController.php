<?php

declare(strict_types=1);

namespace App\Controller;

use Exception;
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

#[IsGranted('ROLE_AGENT')]
class MaxFieldsController extends BaseController
{
    public function __construct(
        private readonly MaxfieldRepository $maxfieldRepository,
        private readonly MaxFieldHelper $maxFieldHelper,
        private readonly IngressHelper $ingressHelper,
        private readonly WaypointRepository $repository,
        private readonly MaxFieldGenerator $maxFieldGenerator,
        private readonly RouterInterface $router
    ) {}

    #[Route(path: 'maxfield/list', name: 'maxfields', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $pagerfanta = Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter($this->maxfieldRepository->createQueryBuilderSearch()),
            $page,
            20
        );

        $template = 'index';

        $partial = $request->query->get('partial');

        if ($partial) {
            if (in_array(
                $partial,
                ['list_lg', 'list_sm']
            )
            ) {
                $template = '_' . $partial;
            } else {
                throw new UnexpectedValueException('Invalid partial');
            }
        }

        return $this->render(
            sprintf('maxfield/%s.html.twig', $template),
            [
                'favourites' => $this->getUser()?->getFavourites(),
                'pagerfanta' => $pagerfanta,
                'page' => $page,
            ]
        );
    }

    #[Route(path: 'maxfield/check', name: 'maxfields_check', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function check(): Response
    {
        $maxfieldFiles = $this->maxFieldHelper->getList();
        $dbMaxfields = $this->maxfieldRepository->findAll();
        $maxfields = [];
        foreach ($dbMaxfields as $maxfield) {
            $maxfieldStatus = (new MaxfieldStatus($this->maxFieldHelper))
                ->fromMaxfield($maxfield);
            $maxfields[] = $maxfieldStatus;

            $index = array_search($maxfieldStatus->getPath(), $maxfieldFiles, true);
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

    #[Route(path: 'maxfield/show/{path:maxfield}', name: 'max_fields_result', methods: ['GET'])]
    public function display(MaxField $maxfield): Response
    {
        $path = $maxfield->getPath() ?? '';
        $info = $this->maxFieldHelper->getMaxField($path);
        $waypointIdMap = $this->maxFieldHelper->getWaypointsIdMap($path);

        return $this->render(
            'maxfield/result.html.twig',
            [
                'maxfield' => $maxfield,
                'info' => $info,
                'waypointIdMap' => $waypointIdMap,
            ]
        );
    }

    #[Route(path: 'maxfield/clear-user-data/{path:maxfield}', name: 'maxfield_clear_user_data', methods: ['POST'])]
    public function clearUserData(
        MaxField $maxfield,
        EntityManagerInterface $entityManager,
        Request $request,
    ): JsonResponse
    {
        $response = [];
        try {
            /** @var array{agentNum: int|string} $data */
            $data = json_decode($request->getContent(), true);

            $agentNum = (int) $data['agentNum'];

            $maxfield->setCurrentPointWithUser('-1', $agentNum);
            $maxfield->setFarmDoneWithUser([], $agentNum);
            $maxfield->setUserKeysWithUser([], $agentNum);
            $entityManager->flush();
            $response ['result'] = 'cleared';
            $code = 200;
        } catch (Exception $exception) {
            $response['error'] = $exception->getMessage();
            $code = 500;
        }

        return $this->json($response, $code);
    }

    #[Route(path: 'maxfield/submit-user-data/{path:maxfield}', name: 'maxfield_submit_user_data', methods: ['POST'])]
    public function submitUserData(
        MaxField $maxfield,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse
    {
        $response = [];
        $status = 200;

        /** @var array{agentNum: int|string, keys?: string, current_point?: string, farm_done?: array<int>} $data */
        $data = json_decode($request->getContent(), true);

        $agentNum = (int) $data['agentNum'];

        $keys = $data['keys'] ?? null;
        $currentPoint = $data['current_point'] ?? null;
        $farmDone = $data['farm_done'] ?? null;

        if ($currentPoint !== null) {
            $maxfield->setCurrentPointWithUser($currentPoint, $agentNum);
            $entityManager->flush();
        }

        if ($farmDone !== null) {
            $maxfield->setFarmDoneWithUser($farmDone, $agentNum);
            $entityManager->flush();
        }

        if ($keys) {
            $waypointIdMap = $this->maxFieldHelper->getWaypointsIdMap($maxfield->getPath() ?? '');
            try {
                $existingKeys = $this->ingressHelper->getExistingKeysForMaxfield($waypointIdMap, $keys);
                if ($existingKeys !== []) {
                    $maxfield->setUserKeysWithUser($existingKeys, $agentNum);
                    $response['result'] = sprintf('Added keyinfo for %d portals.', count($existingKeys));

                    $entityManager->flush();
                } else {
                    $response['error'] = 'No keys found :(';
                    $status = 404;
                }
            } catch (Exception $exception) {
                $response['error'] = $exception->getMessage();
                $status = 500;
            }
        }

        return $this->json($response, $status);
    }

    #[Route('maxfield/play/{path:maxfield}', name: 'maxfield_play', methods: ['GET'])]
    public function play(Maxfield $maxfield): Response
    {
        $user = $this->getUser();
        $userSettings = $user?->getUserParams();
        $path = $maxfield->getPath() ?? '';

        if ($userSettings && MapProvidersEnum::mapbox === $userSettings->mapProvider) {
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
                    ->getJson($this->maxFieldHelper->getParser($path)),
                'waypointIdMap' => $this->maxFieldHelper->getWaypointsIdMap($path),
            ]
        );
    }

    #[Route('maxfield/get-data/{path:maxfield}', name: 'maxfield_get_data', methods: ['GET'])]
    public function getData(Maxfield $maxfield): JsonResponse
    {
        $path = $maxfield->getPath() ?? '';
        $json = (new JsonHelper())
            ->getJsonData($this->maxFieldHelper->getParser($path));

        return $this->json([
            'jsonData' => $json,
            'waypointIdMap' => $this->maxFieldHelper->getWaypointsIdMap($path),
        ]);


    }

    #[Route('maxfield/get-user-data/{path:maxfield}', name: 'maxfield_get_user_data', methods: ['POST'])]
    public function getUserData(
        Maxfield $maxfield,
        #[MapRequestPayload] UserDataType $data,
    ): JsonResponse
    {
        $userData = $maxfield->getUserData();

        if ($userData && array_key_exists($data->userId, $userData)) {
            return $this->json($userData[$data->userId]);
        }

        return $this->json([]);
    }

    #[Route(path: 'maxfield/export', name: 'export-maxfields', methods: ['POST'])]
    public function generateMaxFields(
        EntityManagerInterface $entityManager,
        Request $request,
        //   #[MapRequestPayload] MaxfieldCreateType $maxfieldType,

    ): Response
    {
        $maxfieldType = new MaxfieldCreateType();
        $maxfieldType->points = (string)$request->request->get('points');
        $maxfieldType->buildName = (string)$request->request->get('buildName');
        $maxfieldType->skipPlots = (bool)$request->request->get('skipPlots');
        $maxfieldType->skipStepPlots = (bool)$request->request->get('skipStepPlots');
        $maxfieldType->playersNum = (int)$request->request->get('playersNum');

        $wayPoints = $this->repository->findBy(['id' => $maxfieldType->getPoints()]);
        $maxField = $this->maxFieldGenerator->convertWayPointsToMaxFields($wayPoints);
        $waypointMap = $this->maxFieldGenerator->getWaypointsMap($wayPoints);

        $options = [
            'skip_plots' => $maxfieldType->skipPlots,
            'skip_step_plots' => $maxfieldType->skipStepPlots,
        ];

        $projectName = $maxfieldType->getProjectName();

        $this->maxFieldGenerator->generate(
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

    #[Route(path: 'maxfield/edit/{id}', name: 'maxfield_edit', methods: [
        'GET',
        'POST',
    ])]
    public function edit(
        Maxfield $maxfield,
        EntityManagerInterface $entityManager,
        Request $request,
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
            sprintf('maxfield/%s.html.twig', $template),
            [
                'form' => $form,
            ]
        );
    }

    #[Route(path: 'maxfield/delete/{id}', name: 'max_fields_delete', methods: ['GET'])]
    public function delete(
        EntityManagerInterface $entityManager,
        Maxfield $maxfield,
        Request $request,
    ): RedirectResponse
    {
        $this->denyAccessUnlessGranted(
            'modify',
            $maxfield,
            'You are not allowed to delete this item :('
        );

        $item = $maxfield->getPath();
        try {
            $this->maxFieldGenerator->remove((string)$item);

            $entityManager->remove($maxfield);
            $entityManager->flush();

            $this->addFlash(
                'success',
                sprintf('%s has been removed.', $item)
            );
        } catch (IOException $ioException) {
            $this->addFlash('warning', $ioException->getMessage());
        }

        $referer = $this->getInternalReferer($request, $this->router);

        return $this->redirectToRoute($referer ?: 'maxfields');
    }

    #[Route(path: 'maxfield/delete-files/{item}', name: 'maxfield_delete_files', methods: ['GET'])]
    public function deleteFiles(string $item): RedirectResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException(
                'You are not allowed to delete this item :('
            );
        }

        try {
            $this->maxFieldGenerator->remove($item);

            $this->addFlash('success', sprintf('%s has been removed.', $item));
        } catch (IOException $ioException) {
            $this->addFlash('warning', $ioException->getMessage());
        }

        return $this->redirectToRoute('maxfields');
    }

    #[Route(path: 'maxfield/status/{id}', name: 'maxfield_status', methods: ['GET'])]
    public function status(Maxfield $maxfield): JsonResponse
    {
        $status = (new MaxfieldStatus($this->maxFieldHelper))
            ->fromMaxfield($maxfield);

        return $this->json($status);
    }

    #[Route(path: 'maxfield/view-status/{id}', name: 'maxfield_view_status', methods: ['GET'])]
    public function viewStatus(Maxfield $maxfield): Response
    {
        return $this->render(
            'maxfield/status.html.twig',
            [
                'maxfield' => $maxfield,
            ]
        );
    }

    #[Route(path: 'maxfield/toggle-favourite/{id}', name: 'maxfield_toggle_favourite', methods: ['GET'])]
    public function toggleFavourite(
        Maxfield $maxfield,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $newState = $this->getUser()?->toggleFavourite($maxfield);

        $entityManager->flush();

        return $this->json([
            'new-state' => $newState,
        ]);
    }

    #[Route(path: 'maxfield/plan', name: 'app_maxfields_plan', methods: ['GET'])]
    public function plan(
        #[Autowire('%env(APP_DEFAULT_LAT)%')] float $defaultLat,
        #[Autowire('%env(APP_DEFAULT_LON)%')] float $defaultLon,
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

    #[Route(path: 'maxfield/plan2', name: 'app_maxfields_plan2', methods: ['GET'])]
    public function plan2(
        #[Autowire('%env(APP_DEFAULT_LAT)%')] float $defaultLat,
        #[Autowire('%env(APP_DEFAULT_LON)%')] float $defaultLon,
        #[Autowire('%env(APP_DEFAULT_ZOOM)%')] float $defaultZoom,
    ): Response
    {
        $user = $this->getUser();
        $userSettings = $user?->getUserParams();

        $lat = $user?->getParam('lat') ?: $defaultLat;
        $lon = $user?->getParam('lon') ?: $defaultLon;
        $zoom = $user?->getParam('zoom') ?: $defaultZoom;

        return $this->render('maxfield/plan2.html.twig', [
            'lat' => $lat,
            'lon' => $lon,
            'zoom' => $zoom,
            'token' => $userSettings->mapboxApiKey ?? '',
        ]);
    }
}
