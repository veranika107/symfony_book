<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AppTestFixtures extends Fixture implements FixtureGroupInterface
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

        $berlin = new Conference(city: 'Berlin', year: '2021', isInternational: true);
        $manager->persist($berlin);

        $comment1 = new Comment(author: 'Fabien', text: 'This was a great conference.', email: 'fabien@example.com', conference: $amsterdam, state: 'published');
        $manager->persist($comment1);

        $comment2 = new Comment(author: 'Lucas', text: 'I think this one is going to be moderated.', email: 'lucas@example.com', conference: $amsterdam);
        $manager->persist($comment2);

        $comment3 = new Comment(author: 'Mike', text: 'Very nice.', email: 'mike@example.com', conference: $berlin, state: 'published');
        $manager->persist($comment3);

        $comment4 = new Comment(author: 'Louisa', text: 'I have seen better.', email: 'louisa@example.com', conference: $berlin, state: 'published');
        $manager->persist($comment4);

        $comment5 = new Comment(author: 'Bob', text: 'I like.', email: 'bob@example.com', conference: $berlin, state: 'published');
        $manager->persist($comment5);

        $comment6 = new Comment(author: 'Spam', text: 'Totally spam.', email: 'spam@example.com', createdAt: new \DateTimeImmutable('2023-01-01 10:10:10'), conference: $berlin, state: 'rejected');
        $manager->persist($comment6);

        $comment7 = new Comment(author: 'Spam2', text: 'Totally spam.', email: 'spam2@example.com', createdAt: new \DateTimeImmutable('2023-01-01 10:10:10'), conference: $berlin, state: 'spam');
        $manager->persist($comment7);

        $comment8 = new Comment(author: 'Spam3', text: 'Totally spam.', email: 'spam3@example.com', createdAt: new \DateTimeImmutable('now'), conference: $berlin, state: 'spam');
        $manager->persist($comment8);

        $user = new User(email: 'user@example.com', userFirstName: 'User', password: '$2y$13$YX8f8dQYgDoFF54KLUpaS..SZLtoN6TUqwubr.bl1A6xG9.t30xqC');
        $manager->persist($user);

        $adminPassword = $this->passwordHasherFactory->getPasswordHasher(User::class)->hash('admin');
        $admin = new User(email: 'admin@admin.com', roles: ['ROLE_ADMIN'], userFirstName: 'admin', password: $adminPassword);
        $manager->persist($admin);

        $manager->flush();
    }
}
