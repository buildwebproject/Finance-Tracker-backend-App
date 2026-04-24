<?php

namespace App\Security;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ApiTokenRevocationStore
{
    public function __construct(
        #[Autowire(service: 'cache.app')]
        private readonly CacheItemPoolInterface $cache,
    )
    {
    }

    public function revoke(string $accessToken, int $expiresAt): void
    {
        $ttl = $expiresAt - time();
        if ($ttl <= 0) {
            return;
        }

        $key = $this->buildKey($accessToken);
        $item = $this->cache->getItem($key);
        $item->set(true);
        $item->expiresAfter($ttl);
        $this->cache->save($item);
    }

    public function isRevoked(string $accessToken): bool
    {
        $key = $this->buildKey($accessToken);
        $item = $this->cache->getItem($key);

        return $item->isHit() && true === $item->get();
    }

    private function buildKey(string $accessToken): string
    {
        return 'api_token_revoked_'.hash('sha256', $accessToken);
    }
}
