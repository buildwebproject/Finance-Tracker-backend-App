<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class ApiAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly ApiTokenManager $apiTokenManager,
        private readonly ApiTokenRevocationStore $apiTokenRevocationStore,
    )
    {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        try {
            if ($this->apiTokenRevocationStore->isRevoked($accessToken)) {
                throw new \InvalidArgumentException('Token revoked.');
            }

            $userIdentifier = $this->apiTokenManager->getUserIdentifierFromToken($accessToken);
        } catch (\InvalidArgumentException $exception) {
            throw new BadCredentialsException('Invalid API token.', 0, $exception);
        }

        return new UserBadge($userIdentifier);
    }
}
