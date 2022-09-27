<?php

namespace App\Controller\Admin;

use App\Entity\Maxfield;
use App\Entity\User;
use App\Entity\Waypoint;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MaxfieldsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Maxfield::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->onlyOnIndex(),
            Field::new('name'),
            Field::new('path'),
            // Field::new('jsondata'),
            // Field::new('owner'),
        ];
    }
}
