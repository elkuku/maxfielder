<?php

namespace App\Tests\Security;

use App\Entity\Maxfield;
use App\Entity\User;
use App\Enum\UserRole;
use App\Security\MaxfieldVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class MaxfieldVoterTest extends TestCase
{
    private Security&MockObject $security;
    private MaxfieldVoter $voter;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->voter = new MaxfieldVoter($this->security);
    }

    public function testSupportsModifyWithMaxfield(): void
    {
        $result = $this->voter->vote(
            $this->createTokenWithUser(new User()),
            new Maxfield(),
            ['modify']
        );

        self::assertNotSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportOtherAttribute(): void
    {
        $result = $this->voter->vote(
            $this->createTokenWithUser(new User()),
            new Maxfield(),
            ['delete']
        );

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportNonMaxfieldSubject(): void
    {
        $result = $this->voter->vote(
            $this->createTokenWithUser(new User()),
            new \stdClass(),
            ['modify']
        );

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDeniesNonUserToken(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, new Maxfield(), ['modify']);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testGrantsAdminAccess(): void
    {
        $this->security->method('isGranted')
            ->with(UserRole::ADMIN)
            ->willReturn(true);

        $user = new User();
        $user->setRole(UserRole::ADMIN);

        $result = $this->voter->vote(
            $this->createTokenWithUser($user),
            new Maxfield(),
            ['modify']
        );

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testGrantsOwnerAccess(): void
    {
        $this->security->method('isGranted')->willReturn(false);

        $user = new User();
        $maxfield = new Maxfield();
        $maxfield->setOwner($user);

        $result = $this->voter->vote(
            $this->createTokenWithUser($user),
            $maxfield,
            ['modify']
        );

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDeniesNonOwnerAccess(): void
    {
        $this->security->method('isGranted')->willReturn(false);

        $owner = new User();
        $other = new User();
        $maxfield = new Maxfield();
        $maxfield->setOwner($owner);

        $result = $this->voter->vote(
            $this->createTokenWithUser($other),
            $maxfield,
            ['modify']
        );

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    private function createTokenWithUser(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
