<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\User\UserInterface;

final class ApiTokenManager
{
    public function __construct(
        #[Autowire('%kernel.secret%')]
        private readonly string $secret,
        #[Autowire('%env(int:API_TOKEN_TTL)%')]
        private readonly int $ttl,
    ) {
    }

    /**
     * @return array{access_token: string, expires_at: int}
     */
    public function issueToken(UserInterface $user): array
    {
        $payload = [
            'sub' => $user->getUserIdentifier(),
            'exp' => time() + max(60, $this->ttl),
        ];

        $encodedPayload = $this->base64UrlEncode(json_encode($payload, \JSON_THROW_ON_ERROR));
        $encodedSignature = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, $this->secret, true));

        return [
            'access_token' => $encodedPayload.'.'.$encodedSignature,
            'expires_at' => $payload['exp'],
        ];
    }

    public function getUserIdentifierFromToken(string $accessToken): string
    {
        ['sub' => $subject, 'exp' => $expiresAt] = $this->getVerifiedPayload($accessToken);

        if (!\is_string($subject) || '' === $subject) {
            throw new \InvalidArgumentException('Token subject is missing.');
        }

        if (!\is_int($expiresAt) || $expiresAt < time()) {
            throw new \InvalidArgumentException('Token expired.');
        }

        return $subject;
    }

    public function getExpiresAtFromToken(string $accessToken): int
    {
        ['exp' => $expiresAt] = $this->getVerifiedPayload($accessToken);
        if (!\is_int($expiresAt)) {
            throw new \InvalidArgumentException('Token expiry is invalid.');
        }

        return $expiresAt;
    }

    /**
     * @return array{string, string}
     */
    private function splitToken(string $accessToken): array
    {
        $parts = explode('.', $accessToken, 2);
        if (2 !== \count($parts) || '' === $parts[0] || '' === $parts[1]) {
            throw new \InvalidArgumentException('Token format is invalid.');
        }

        return [$parts[0], $parts[1]];
    }

    private function assertSignature(string $encodedPayload, string $encodedSignature): void
    {
        $expected = $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, $this->secret, true));
        if (!hash_equals($expected, $encodedSignature)) {
            throw new \InvalidArgumentException('Token signature is invalid.');
        }
    }

    /**
     * @return array{sub: mixed, exp: mixed}
     */
    private function getVerifiedPayload(string $accessToken): array
    {
        [$encodedPayload, $encodedSignature] = $this->splitToken($accessToken);
        $this->assertSignature($encodedPayload, $encodedSignature);

        $payload = json_decode($this->base64UrlDecode($encodedPayload), true, 512, \JSON_THROW_ON_ERROR);
        if (!\is_array($payload)) {
            throw new \InvalidArgumentException('Token payload is invalid.');
        }

        return [
            'sub' => $payload['sub'] ?? null,
            'exp' => $payload['exp'] ?? null,
        ];
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $normalized = strtr($value, '-_', '+/');
        $padding = strlen($normalized) % 4;
        if (0 !== $padding) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalized, true);
        if (false === $decoded) {
            throw new \InvalidArgumentException('Token encoding is invalid.');
        }

        return $decoded;
    }
}
