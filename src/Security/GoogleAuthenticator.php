<?php

declare(strict_types=1);

namespace App\Security;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GoogleAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;
    use AuthenticationResultTrait;

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function supports(Request $request): bool
    {
        return $request->getPathInfo() === '/connect/google/check';
    }

    /**
     * @throws IdentityProviderException
     */
    public function authenticate(Request $request): Passport
    {
        $token = $this->getGoogleClient()->getAccessToken();

        /** @var GoogleUser $googleUser */
        $googleUser = $this->getGoogleClient()
            ->fetchUserFromToken($token);

        $user = $this->getUser($googleUser);

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier()),
            [new RememberMeBadge()],
        );
    }

    private function getGoogleClient(): OAuth2ClientInterface
    {
        return $this->clientRegistry->getClient('google');
    }

    private function getUser(GoogleUser $googleUser): User
    {
        $user = $this->userRepository->findOneBy(
            ['googleId' => $googleUser->getId()]
        );

        if ($user instanceof User) {
            return $user;
        }

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

    private function getSuccessRedirectUrl(): string
    {
        return $this->urlGenerator->generate('default');
    }

    private function getFailureRedirectUrl(): string
    {
        return $this->urlGenerator->generate('login');
    }
}
