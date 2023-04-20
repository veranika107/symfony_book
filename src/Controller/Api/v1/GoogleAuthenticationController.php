<?php

namespace App\Controller\Api\v1;

use App\Service\Google\GoogleUserManager;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use UnexpectedValueException;

class GoogleAuthenticationController extends AbstractController
{
    #[Route('/api/auth/google', name: 'api_auth_with_google', methods: ['POST'])]
    public function authWithGoogle(
        ClientRegistry $clientRegistry,
        Request $request,
        GoogleUserManager $googleUserManager,
        JWTTokenManagerInterface $JWTManager,
        RefreshTokenManagerInterface $refreshTokenManager,
        AuthenticationSuccessHandler $authenticationSuccessHandler
    ): Response
    {
        $data = json_decode($request->getContent(), true);
        $accessToken = $data ? $data['token'] : null;
        if (!$accessToken) {
            return $this->json('Code parameter is missing.', Response::HTTP_BAD_REQUEST);
        }

        $client = $clientRegistry->getClient('google');
        try {
            /** @var GoogleUser $googleUser */
            $googleUser = $client->fetchUserFromToken((new AccessToken(['access_token' => $accessToken])));
        } catch (IdentityProviderException $exception) {
            return $this->json('Access token is invalid.', Response::HTTP_FORBIDDEN);
        }

        try {
            $user = $googleUserManager->getUserFromGoogleUser($googleUser);
        } catch (UnexpectedValueException $exception) {
            return $this->json($exception->getMessage(), Response::HTTP_FORBIDDEN);
        }

        // Create access and refresh token for the user.
        $appAccessToken = $JWTManager->create($user);
        $authenticationSuccessHandler->handleAuthenticationSuccess($user, $appAccessToken);
        $refreshToken = $refreshTokenManager->getLastFromUsername($user->getEmail())->getRefreshToken();
        return $this->json(['token' => $appAccessToken, 'refresh_token' => $refreshToken]);
    }
}