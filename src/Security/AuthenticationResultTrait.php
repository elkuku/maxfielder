<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

trait AuthenticationResultTrait
{
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): RedirectResponse
    {
        if ($targetPath = $this->getTargetPath(
            $request->getSession(),
            $firewallName
        )
        ) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->getSuccessRedirectUrl());
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception,
    ): RedirectResponse
    {
        $message = strtr(
            $exception->getMessageKey(),
            $exception->getMessageData()
        );

        /**
         * @var Session $session
         */
        $session = $request->getSession();
        $session->getFlashBag()->add('danger', $message);

        return new RedirectResponse($this->getFailureRedirectUrl());
    }

    abstract private function getSuccessRedirectUrl(): string;

    abstract private function getFailureRedirectUrl(): string;
}
