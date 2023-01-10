<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GoogleIdentityAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private readonly string $oauthGoogleId,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->getPathInfo() === '/connect/google/verify';
    }

    public function authenticate(Request $request): Passport
    {
        $idToken = (string)$request->request->get('credential');

        if (!$idToken) {
            throw new AuthenticationException('Missing credentials :(');
        }

        $payload = (new Client(['client_id' => $this->oauthGoogleId]))
            ->verifyIdToken($idToken);

        if (!$payload) {
            throw new AuthenticationException('Invalid ID token :(');
        }

        $user = $this->getUser(new GoogleUser($payload));

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier()),
            [new RememberMeBadge()],
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): RedirectResponse {
        if ($targetPath = $this->getTargetPath(
            $request->getSession(),
            $firewallName
        )
        ) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('default'));
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): RedirectResponse {
        $message = strtr(
            $exception->getMessageKey(),
            $exception->getMessageData()
        );

        /**
         * @var Session $session
         */
        $session = $request->getSession();
        $session->getFlashBag()->add('danger', $message);

        return new RedirectResponse($this->urlGenerator->generate('login'));
    }

    private function getUser(GoogleUser $googleUser): User
    {
        $user = $this->userRepository->findOneBy(
            ['googleId' => $googleUser->getId()]
        );

        if ($user) {
            return $user;
        }

        // Register a new user
        $user = (new User())
            ->setIdentifier((string)$googleUser->getEmail())
            ->setGoogleId((string)$googleUser->getId());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
