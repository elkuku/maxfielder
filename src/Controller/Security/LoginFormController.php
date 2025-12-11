<?php

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginFormController extends AbstractController
{
    public function __construct(private readonly AuthenticationUtils $authenticationUtils) {}

    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(string $oauthGoogleId): Response
    {
        return $this->render(
            'auth/login.html.twig',
            [
                'last_username' => $this->authenticationUtils->getLastUsername(),
                'error' => $this->authenticationUtils->getLastAuthenticationError(),
                'oauthGoogleId' => $oauthGoogleId,
            ]
        );
    }

    #[Route('/logout', name: 'logout', methods: ['GET'])]
    public function logout(): void {}
}
