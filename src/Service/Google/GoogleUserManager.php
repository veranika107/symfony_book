<?php

namespace App\Service\Google;

use App\Entity\User;
use App\Repository\UserRepository;
use League\OAuth2\Client\Provider\GoogleUser;
use UnexpectedValueException;

class GoogleUserManager
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function getUserFromGoogleUser(GoogleUser $googleUser): User
    {
        $email = $googleUser->getEmail();
        if (!$email) {
            throw new UnexpectedValueException('Google user email should not be empty.');
        }
        $userFirstName = $googleUser->getFirstName();
        $userLastName = $googleUser->getLastName();

        // Return existing user if their entity already exists.
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $existingUser;
        }

        // Create a user object if such a user doesn't exist.
        $user = new User(email: $email, userFirstName: $userFirstName, userLastName: $userLastName);
        $this->userRepository->save($user, true);
        return $user;
    }
}