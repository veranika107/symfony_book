<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AppDevFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

     public static function getGroups(): array
     {
         return ['DevFixtures'];
     }

    public function load(ObjectManager $manager): void
    {
        $minsk = new Conference(city: 'Minsk', year: '2019', isInternational: true);
        $manager->persist($minsk);

        $wroclaw = new Conference(city: 'Wroclaw', year: '2023', isInternational: false);
        $manager->persist($wroclaw);

        $comment1 = new Comment(author: 'Veranika', text: 'This was a great conference.', email: 'veranika@example.com', conference: $minsk, state: 'published');
        $manager->persist($comment1);

        $comment2 = new Comment(author: 'Igor', text: 'I think this one is going to be moderated.', email: 'lucas@example.com', conference: $minsk, state: 'published');
        $manager->persist($comment2);

        $adminPassword = $this->passwordHasherFactory->getPasswordHasher(User::class)->hash('admin');
        $admin = new User(email: 'admin@admin.com', roles: ['ROLE_ADMIN'], userFirstName: 'admin', password: $adminPassword);
        $manager->persist($admin);

        $manager->flush();
    }
}
