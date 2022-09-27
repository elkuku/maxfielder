<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Waypoint;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class WaypointCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Waypoint::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->onlyOnIndex(),
            Field::new('name'),
            Field::new('lat'),
            Field::new('lon'),
            Field::new('guid'),
        ];
    }
}
