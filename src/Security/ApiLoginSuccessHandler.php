<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final class ApiLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private readonly ApiTokenManager $apiTokenManager)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return new JsonResponse(['message' => 'Authentication failed.'], Response::HTTP_UNAUTHORIZED);
        }

        $tokenData = $this->apiTokenManager->issueToken($user);

        return new JsonResponse([
            'token_type' => 'Bearer',
            'access_token' => $tokenData['access_token'],
            'expires_at' => (new \DateTimeImmutable('@'.$tokenData['expires_at']))->format(\DateTimeInterface::ATOM),
        ]);
    }
}
