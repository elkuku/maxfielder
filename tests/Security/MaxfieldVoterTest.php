<?php

declare(strict_types=1);

namespace App\Tests\Security;

use stdClass;
use App\Entity\Maxfield;
use App\Entity\User;
use App\Enum\UserRole;
use App\Security\MaxfieldVoter;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class MaxfieldVoterTest extends TestCase
{
    private Security&Stub $security;

    private MaxfieldVoter $voter;

    protected function setUp(): void
    {
        $this->security = $this->createStub(Security::class);
        $this->voter = new MaxfieldVoter($this->security);
    }

    public function testSupportsModifyWithMaxfield(): void
    {
        $result = $this->voter->vote(
            $this->createTokenWithUser(new User()),
            new Maxfield(),
            ['modify']
        );

        $this->assertNotSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportOtherAttribute(): void
    {
        $result = $this->voter->vote(
            $this->createTokenWithUser(new User()),
            new Maxfield(),
            ['delete']
        );

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportNonMaxfieldSubject(): void
    {
        $result = $this->voter->vote(
            $this->createTokenWithUser(new User()),
            new stdClass(),
            ['modify']
        );

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDeniesNonUserToken(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, new Maxfield(), ['modify']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testGrantsAdminAccess(): void
    {
        $this->security->method('isGranted')
            ->willReturn(true);

        $user = new User();
        $user->setRole(UserRole::ADMIN);

        $result = $this->voter->vote(
            $this->createTokenWithUser($user),
            new Maxfield(),
            ['modify']
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
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

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
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

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    private function createTokenWithUser(User $user): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
