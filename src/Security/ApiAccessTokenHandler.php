<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class ApiAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private readonly ApiTokenManager $apiTokenManager)
    {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        try {
            $userIdentifier = $this->apiTokenManager->getUserIdentifierFromToken($accessToken);
        } catch (\InvalidArgumentException $exception) {
            throw new BadCredentialsException('Invalid API token.', 0, $exception);
        }

        return new UserBadge($userIdentifier);
    }
}
