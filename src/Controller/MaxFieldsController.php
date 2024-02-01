<?php

namespace App\Controller;

use App\Entity\Maxfield;
use App\Form\MaxfieldFormType;
use App\Repository\MaxfieldRepository;
use App\Repository\WaypointRepository;
use App\Service\IngressHelper;
use App\Service\MaxFieldGenerator;
use App\Service\MaxFieldHelper;
use App\Type\MaxfieldStatus;
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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;
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

    #[Route(path: '/show/{path}', name: 'max_fields_result', methods: ['GET', 'POST'])]
    public function display(
        MaxFieldHelper         $maxFieldHelper,
        MaxField               $maxfield,
        Request                $request,
        IngressHelper          $ingressHelper,
        EntityManagerInterface $entityManager,
    ): Response
    {
        $keys = $request->request->get('keys');
        $agentNum = $request->request->get('agentNum');
        $info = $maxFieldHelper->getMaxField($maxfield->getPath());
        $existingKeys = [];

        if ($keys) {
            $parsedKeys = $ingressHelper->parseKeysString($keys);

            foreach ($info->keyPrep->getWayPoints() as $keyPrep) {
                foreach ($parsedKeys as $parsedKey) {
                    // TODO check guid to avoid dupes
                    if ($parsedKey->name === $keyPrep->name) {
                        $existingKeys[] = $parsedKey;
                    }
                }
            }
            $userKeys = $maxfield->getUserKeys();
            if ($userKeys) {
                $userKeys[$agentNum] = $existingKeys;
            } else {
                $userKeys = [$agentNum => $existingKeys];
            }

            $maxfield->setUserKeys($userKeys);
            $entityManager->flush();

            $this->addFlash('success', sprintf('%d keys added.', count($existingKeys)));
        }

        return $this->render(
            'maxfield/result.html.twig',
            [
                'maxfield' => $maxfield,
                'info' => $info,
                'existingKeys' => $existingKeys,
            ]
        );
    }

    #[Route('/play/{path}', name: 'maxfield_play', methods: ['GET'])]
    public function play(
        MaxFieldHelper $maxFieldHelper,
        Maxfield       $maxfield
    ): Response
    {
        $json = (new JsonHelper())
            ->getJson($maxFieldHelper->getParser($maxfield->getPath()));

        return $this->render(
            'maxfield/play.html.twig',
            [
                'maxfield' => $maxfield,
                'jsonData' => $json,
                'userKeys' => json_encode($maxfield->getUserKeys(), JSON_THROW_ON_ERROR),
            ]
        );
    }

    #[Route(path: '/export', name: 'export-maxfields', methods: ['POST'])]
    public function generateMaxFields(
        WaypointRepository     $repository,
        MaxFieldGenerator      $maxFieldGenerator,
        EntityManagerInterface $entityManager,
        Request                $request
    ): Response
    {
        $points = $request->request->get('points');
        $ids = array_map('intval', explode(',', $points));

        $wayPoints = $repository->findBy(['id' => $ids]);
        $maxField = $maxFieldGenerator->convertWayPointsToMaxFields($wayPoints);
        $buildName = $request->request->get('build_name');
        $playersNum = (int)$request->request->get('players_num') ?: 1;
        $options = [
            'skip_plots' => $request->request
                ->getBoolean('skip_plots'),
            'skip_step_plots' => $request->request
                ->getBoolean('skip_step_plots'),
        ];

        $projectName = uniqid() . '-' . (new AsciiSlugger())->slug($buildName);

        $maxFieldGenerator->generate(
            $projectName,
            $maxField,
            $playersNum,
            $options
        );

        $maxfield = (new Maxfield())
            ->setName($buildName)
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
    ): Response
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

        // $this->addFlash('warning', 'Temporary disabled....');

        $referer = $this->getInternalReferer($request, $router);

        return $this->redirectToRoute($referer ?: 'maxfields');
    }

    #[Route(path: '/delete-files/{item}', name: 'maxfield_delete_files', methods: ['GET'])]
    public function deleteFiles(
        MaxFieldGenerator $maxFieldGenerator,
        string            $item,
    ): Response
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
