<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Comment;
use App\Entity\Conference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AppFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

     public static function getGroups(): array
     {
         return ['TestFixtures'];
     }

    public function load(ObjectManager $manager): void
    {
        $amsterdam = new Conference(city: 'Amsterdam', year: '2019', isInternational: true);
        $manager->persist($amsterdam);

        $paris = new Conference(city: 'Paris', year: '2020', isInternational: false);
        $manager->persist($paris);

        $comment1 = new Comment(author: 'Fabien', text: 'This was a great conference.', email: 'fabien@example.com', conference: $amsterdam, state: 'published');
        $manager->persist($comment1);

        $comment2 = new Comment(author: 'Lucas', text: 'I think this one is going to be moderated.', email: 'lucas@example.com', conference: $amsterdam);
        $manager->persist($comment2);

        $adminPassword = $this->passwordHasherFactory->getPasswordHasher(Admin::class)->hash('admin');
        $admin = new Admin(username: 'admin', roles: ['ROLE_ADMIN'], password: $adminPassword);
        $manager->persist($admin);

        $manager->flush();
    }
}
