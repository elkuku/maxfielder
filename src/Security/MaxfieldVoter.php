<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Maxfield;
use App\Entity\User;
use App\Enum\UserRole;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, mixed> */
class MaxfieldVoter extends Voter
{
    final public const string MODIFY = 'modify';

    public function __construct(private readonly Security $security) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute !== self::MODIFY) {
            return false;
        }

        return $subject instanceof Maxfield;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null
    ): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        if ($this->security->isGranted(UserRole::ADMIN)) {
            return true;
        }

        /** @var Maxfield $maxfield */
        $maxfield = $subject;

        return match ($attribute) {
            self::MODIFY => $this->canModify($maxfield, $user),
            default => throw new LogicException(
                'This code should not be reached!'
            )
        };
    }

    private function canModify(Maxfield $maxfield, User $user): bool
    {
        return $user === $maxfield->getOwner();
    }
}
