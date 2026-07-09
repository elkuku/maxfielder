<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Override;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(UserRole::ADMIN->value)]
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(private readonly UserRepository $userRepository) {}

    #[Override]
    public function index(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->render('admin/index.html.twig', [
            'userCount' => count($users),
        ]);
    }

    #[Override]
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Playground One Admin');
    }

    #[Override]
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkTo(UserCrudController::class, 'Users', 'fa fa-users');
        yield MenuItem::linkTo(WaypointCrudController::class, 'Waypoints', 'fa fa-users');
        yield MenuItem::linkTo(MaxfieldsCrudController::class, 'Maxfields', 'fa fa-users');

        yield MenuItem::section();
        yield MenuItem::linkToUrl(
            'Homepage',
            'fas fa-home',
            $this->generateUrl('default')
        );
    }
}
