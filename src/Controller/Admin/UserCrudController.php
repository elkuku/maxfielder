<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->onlyOnIndex(),
            TextField::new('identifier'),
            TextField::new('identifier')
                ->hideOnForm()
                ->setSortable(false)
                ->setLabel('Social')
                ->formatValue(static function (string $value, ?User $user):string|bool {
                    if (!$user) {
                        return false;
                    }

                    if ($user->getGoogleId()) {
                        return sprintf(
                            '<i class="fa fa-users" title="Google ID: %s"></i>',
                            $user->getGoogleId()
                        );
                    }

                    if ($user->getGitHubId()) {
                        return sprintf(
                            '<i class="fa fa-users" title="GitHub ID: %s"></i>',
                            $user->getGitHubId()
                        );
                    }

                    return false;
                }),
            ChoiceField::new('roles')
                ->setChoices(User::ROLES)
                ->allowMultipleChoices()
                ->renderExpanded()
                ->renderAsBadges(),
        ];
    }
}
