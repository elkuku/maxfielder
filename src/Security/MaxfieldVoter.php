<?php

namespace App\Security;

use App\Entity\Maxfield;
use App\Entity\User;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, mixed> */
class MaxfieldVoter extends Voter
{
    final public const MODIFY = 'modify';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute !== self::MODIFY) {
            return false;
        }

        if (!$subject instanceof Maxfield) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        if ($this->security->isGranted(User::ROLES['admin'])) {
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
