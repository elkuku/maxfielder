<?php

namespace App\Controller\Admin;

use App\Entity\Maxfield;
use App\Entity\User;
use App\Entity\Waypoint;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractDashboardController
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    #[Route('/admin', name: 'admin', methods: ['GET', 'POST'])]
    #[IsGranted(User::ROLES['admin'])]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->render('admin/index.html.twig', [
            'userCount' => count($users),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Playground One Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Users', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Waypoints', 'fa fa-users', Waypoint::class);
        yield MenuItem::linkToCrud('Maxfields', 'fa fa-users', Maxfield::class);

        yield MenuItem::section();
        yield MenuItem::linkToUrl(
            'Homepage',
            'fas fa-home',
            $this->generateUrl('default')
        );
    }
}
