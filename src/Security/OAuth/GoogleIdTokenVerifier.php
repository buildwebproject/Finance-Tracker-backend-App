<?php

namespace App\Security\OAuth;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class GoogleIdTokenVerifier
{
    public function __construct(
        #[Autowire('%env(string:GOOGLE_OAUTH_CLIENT_ID)%')]
        private readonly string $googleClientId,
    ) {
    }

    /**
     * @return array{sub: string, email: string, email_verified: bool, name?: string, picture?: string}
     */
    public function verify(string $idToken): array
    {
        if ('' === $this->googleClientId) {
            throw new \LogicException('GOOGLE_OAUTH_CLIENT_ID is not configured.');
        }

        $response = $this->httpGetJson(sprintf(
            'https://oauth2.googleapis.com/tokeninfo?id_token=%s',
            urlencode($idToken)
        ));

        if (($response['aud'] ?? '') !== $this->googleClientId) {
            throw new \RuntimeException('Google token audience is invalid.');
        }

        $email = (string) ($response['email'] ?? '');
        $subject = (string) ($response['sub'] ?? '');
        $emailVerified = filter_var($response['email_verified'] ?? false, \FILTER_VALIDATE_BOOL);
        $expiresAt = (int) ($response['exp'] ?? 0);

        if ('' === $email || '' === $subject || !$emailVerified) {
            throw new \RuntimeException('Google account payload is invalid.');
        }

        if ($expiresAt <= time()) {
            throw new \RuntimeException('Google token has expired.');
        }

        $payload = [
            'sub' => $subject,
            'email' => $email,
            'email_verified' => true,
        ];

        if (isset($response['name']) && \is_string($response['name']) && '' !== $response['name']) {
            $payload['name'] = $response['name'];
        }

        if (isset($response['picture']) && \is_string($response['picture']) && '' !== $response['picture']) {
            $payload['picture'] = $response['picture'];
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function httpGetJson(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\n",
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);
        if (false === $responseBody) {
            throw new \RuntimeException('Unable to contact Google token endpoint.');
        }

        $statusCode = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
            $statusCode = (int) $matches[1];
        }

        /** @var mixed $decoded */
        $decoded = json_decode($responseBody, true);
        if (!\is_array($decoded)) {
            throw new \RuntimeException('Invalid response from Google token endpoint.');
        }

        if ($statusCode >= 400) {
            $message = (string) ($decoded['error_description'] ?? $decoded['error'] ?? 'Google token is invalid.');
            throw new \RuntimeException($message);
        }

        return $decoded;
    }
}
