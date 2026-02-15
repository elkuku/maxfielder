<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    use AuthenticationResultTrait;

    public function __construct(
        private readonly string $oauthGoogleId,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function supports(Request $request): bool
    {
        return $request->getPathInfo() === '/connect/google/verify';
    }

    public function authenticate(Request $request): Passport
    {
        $idToken = (string)$request->request->get('credential');

        if ($idToken === '' || $idToken === '0') {
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

    private function getSuccessRedirectUrl(): string
    {
        return $this->urlGenerator->generate('default');
    }

    private function getFailureRedirectUrl(): string
    {
        return $this->urlGenerator->generate('login');
    }

    private function getUser(GoogleUser $googleUser): User
    {
        $user = $this->userRepository->findOneBy(
            ['googleId' => $googleUser->getId()]
        );

        if ($user instanceof User) {
            return $user;
        }

        // Register a new user
        /** @var string $email */
        $email = $googleUser->getEmail();
        /** @var string $googleId */
        $googleId = $googleUser->getId();
        $user = (new User())
            ->setIdentifier($email)
            ->setGoogleId($googleId);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
