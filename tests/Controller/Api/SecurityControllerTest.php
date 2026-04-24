<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\User;
use App\Security\ApiTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityControllerTest extends WebTestCase
{
    public function testSecurityEndpointsRequireAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/auth/security');

        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testGetSecurityReturnsDefaultSettings(): void
    {
        $client = static::createClient();
        $user = $this->createUser('security_test_get_'.uniqid());

        $client->request('GET', '/api/auth/security', [], [], $this->authHeaders($user));

        $responseData = $this->decodeJson($client->getResponse()->getContent() ?: '{}');

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertTrue($responseData['success']);
        self::assertSame('Security settings fetched.', $responseData['message']);
        self::assertFalse($responseData['data']['app_lock_enabled']);
        self::assertFalse($responseData['data']['biometric_enabled']);
        self::assertFalse($responseData['data']['has_mpin']);
        self::assertNull($responseData['data']['mpin_updated_at']);
        self::assertArrayNotHasKey('mpin_hash', $responseData['data']);
    }

    public function testUpdatePreferences(): void
    {
        $client = static::createClient();
        $user = $this->createUser('security_test_pref_'.uniqid());

        $client->request(
            'PUT',
            '/api/auth/security/preferences',
            [],
            [],
            $this->jsonHeaders($user),
            json_encode([
                'app_lock_enabled' => true,
                'biometric_enabled' => true,
            ], \JSON_THROW_ON_ERROR)
        );

        $responseData = $this->decodeJson($client->getResponse()->getContent() ?: '{}');

        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertTrue($responseData['success']);
        self::assertTrue($responseData['data']['app_lock_enabled']);
        self::assertTrue($responseData['data']['biometric_enabled']);
    }

    public function testCreateAndVerifyAndDeleteMpinFlow(): void
    {
        $client = static::createClient();
        $user = $this->createUser('security_test_mpin_'.uniqid());

        $client->request(
            'POST',
            '/api/auth/security/mpin',
            [],
            [],
            $this->jsonHeaders($user),
            json_encode([
                'new_mpin' => '5678',
                'confirm_mpin' => '5678',
            ], \JSON_THROW_ON_ERROR)
        );

        $createData = $this->decodeJson($client->getResponse()->getContent() ?: '{}');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertTrue($createData['success']);
        self::assertTrue($createData['data']['has_mpin']);
        self::assertTrue($createData['data']['app_lock_enabled']);
        self::assertArrayNotHasKey('mpin_hash', $createData['data']);

        $client->request(
            'POST',
            '/api/auth/security/mpin/verify',
            [],
            [],
            $this->jsonHeaders($user),
            json_encode(['mpin' => '5678'], \JSON_THROW_ON_ERROR)
        );

        $verifyData = $this->decodeJson($client->getResponse()->getContent() ?: '{}');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertTrue($verifyData['success']);
        self::assertTrue($verifyData['data']['verified']);

        $client->request(
            'DELETE',
            '/api/auth/security/mpin',
            [],
            [],
            $this->jsonHeaders($user),
            json_encode(['current_mpin' => '5678'], \JSON_THROW_ON_ERROR)
        );

        $deleteData = $this->decodeJson($client->getResponse()->getContent() ?: '{}');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertTrue($deleteData['success']);
        self::assertFalse($deleteData['data']['has_mpin']);
    }

    public function testChangingExistingMpinRequiresCurrentMpin(): void
    {
        $client = static::createClient();
        $user = $this->createUser('security_test_change_'.uniqid());

        $client->request(
            'POST',
            '/api/auth/security/mpin',
            [],
            [],
            $this->jsonHeaders($user),
            json_encode(['new_mpin' => '1111', 'confirm_mpin' => '1111'], \JSON_THROW_ON_ERROR)
        );
        self::assertSame(200, $client->getResponse()->getStatusCode());

        $client->request(
            'POST',
            '/api/auth/security/mpin',
            [],
            [],
            $this->jsonHeaders($user),
            json_encode(['new_mpin' => '2222', 'confirm_mpin' => '2222'], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(422, $client->getResponse()->getStatusCode());
    }

    public function testInvalidMpinValidationReturns422(): void
    {
        $client = static::createClient();
        $user = $this->createUser('security_test_invalid_'.uniqid());

        $client->request(
            'POST',
            '/api/auth/security/mpin',
            [],
            [],
            $this->jsonHeaders($user),
            json_encode([
                'new_mpin' => '12',
                'confirm_mpin' => '12',
            ], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(422, $client->getResponse()->getStatusCode());
    }

    public function testMpinChangeEndpointIsRateLimited(): void
    {
        $client = static::createClient();
        $user = $this->createUser('security_test_ratelimit_'.uniqid());

        for ($i = 0; $i < 10; ++$i) {
            $client->request(
                'POST',
                '/api/auth/security/mpin',
                [],
                [],
                $this->jsonHeaders($user),
                json_encode([
                    'new_mpin' => '1234',
                    'confirm_mpin' => '1234',
                    'current_mpin' => '9999',
                ], \JSON_THROW_ON_ERROR)
            );
        }

        $client->request(
            'POST',
            '/api/auth/security/mpin',
            [],
            [],
            $this->jsonHeaders($user),
            json_encode([
                'new_mpin' => '1234',
                'confirm_mpin' => '1234',
                'current_mpin' => '9999',
            ], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(429, $client->getResponse()->getStatusCode());
    }

    private function createUser(string $username): User
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@example.com');
        $user->setEnabled(true);
        $user->setPlainPassword('Password@123');

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(User $user): array
    {
        /** @var ApiTokenManager $tokenManager */
        $tokenManager = self::getContainer()->get(ApiTokenManager::class);
        $tokenData = $tokenManager->issueToken($user);

        return [
            'HTTP_AUTHORIZATION' => 'Bearer '.$tokenData['access_token'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function jsonHeaders(User $user): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
            ...$this->authHeaders($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        $decoded = json_decode($json, true);

        return \is_array($decoded) ? $decoded : [];
    }
}
