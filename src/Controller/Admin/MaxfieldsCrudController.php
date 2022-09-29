<?php

namespace App\Controller\Admin;

use App\Entity\Maxfield;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

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
            AssociationField::new('owner'),
        ];
    }
}
