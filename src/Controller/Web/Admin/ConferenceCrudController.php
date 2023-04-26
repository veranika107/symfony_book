<?php

namespace App\Controller\Web\Admin;

use App\Entity\Conference;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ConferenceCrudController extends AbstractCrudController
{
    public function createEntity(string $entityFqcn)
    {
        return new $entityFqcn('', '', false);
    }

    public static function getEntityFqcn(): string
    {
        return Conference::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('city');
        yield TextField::new('year');
        yield BooleanField::new('isInternational');
    }
}
