<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Entity\User;
use App\Entity\UserSecuritySettings;
use App\Entity\UserSecuritySettingsLog;
use App\Repository\UserSecuritySettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

final class SecuritySettingsService
{
    private const MPIN_CHANGE_LIMIT = 10;
    private const MPIN_CHANGE_WINDOW_SECONDS = 300;

    private const MPIN_VERIFY_LIMIT = 20;
    private const MPIN_VERIFY_WINDOW_SECONDS = 300;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserSecuritySettingsRepository $securitySettingsRepository,
        private readonly CacheItemPoolInterface $cachePool,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getOrCreateSettings(User $user): UserSecuritySettings
    {
        $settings = $this->securitySettingsRepository->findOneByUser($user);
        if (null !== $settings) {
            return $settings;
        }

        $settings = new UserSecuritySettings($user);
        $this->entityManager->persist($settings);
        $this->entityManager->flush();

        return $settings;
    }

    public function updatePreferences(User $user, ?bool $appLockEnabled, ?bool $biometricEnabled, ?string $ipAddress = null): UserSecuritySettings
    {
        $settings = $this->getOrCreateSettings($user);

        if (null !== $appLockEnabled) {
            $settings->setAppLockEnabled($appLockEnabled);
        }

        if (null !== $biometricEnabled) {
            $settings->setBiometricEnabled($biometricEnabled);
        }

        $this->entityManager->flush();

        $this->logSettingsChange($user, 'preferences_updated', [
            'app_lock_enabled' => $settings->isAppLockEnabled(),
            'biometric_enabled' => $settings->isBiometricEnabled(),
        ], $ipAddress);

        return $settings;
    }

    public function upsertMpin(User $user, ?string $currentMpin, string $newMpin, ?string $ipAddress = null): UserSecuritySettings
    {
        $this->consumeRateLimit($user, 'mpin_change', self::MPIN_CHANGE_LIMIT, self::MPIN_CHANGE_WINDOW_SECONDS);

        $settings = $this->getOrCreateSettings($user);

        if ($settings->hasMpin()) {
            if (null === $currentMpin || !$this->verifyMpinHash($currentMpin, $settings->getMpinHash())) {
                $this->logSettingsChange($user, 'mpin_update_failed', ['reason' => 'invalid_current_mpin'], $ipAddress);
                throw new InvalidCurrentMpinException('current_mpin is incorrect.');
            }
        }

        $settings
            ->setMpinHash($this->hashMpin($newMpin))
            ->setAppLockEnabled(true)
            ->setMpinUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->logSettingsChange($user, 'mpin_updated', [
            'app_lock_enabled' => $settings->isAppLockEnabled(),
            'biometric_enabled' => $settings->isBiometricEnabled(),
            'has_mpin' => $settings->hasMpin(),
        ], $ipAddress);

        return $settings;
    }

    public function removeMpin(User $user, string $currentMpin, ?string $ipAddress = null): UserSecuritySettings
    {
        $this->consumeRateLimit($user, 'mpin_change', self::MPIN_CHANGE_LIMIT, self::MPIN_CHANGE_WINDOW_SECONDS);

        $settings = $this->getOrCreateSettings($user);

        if (!$settings->hasMpin() || !$this->verifyMpinHash($currentMpin, $settings->getMpinHash())) {
            $this->logSettingsChange($user, 'mpin_remove_failed', ['reason' => 'invalid_current_mpin'], $ipAddress);
            throw new InvalidCurrentMpinException('current_mpin is incorrect.');
        }

        $settings
            ->setMpinHash(null)
            ->setMpinUpdatedAt(null);

        $this->entityManager->flush();

        $this->logSettingsChange($user, 'mpin_removed', ['has_mpin' => false], $ipAddress);

        return $settings;
    }

    public function verifyMpin(User $user, string $mpin, ?string $ipAddress = null): bool
    {
        $this->consumeRateLimit($user, 'mpin_verify', self::MPIN_VERIFY_LIMIT, self::MPIN_VERIFY_WINDOW_SECONDS);

        $settings = $this->getOrCreateSettings($user);
        if (!$settings->hasMpin()) {
            $this->logSettingsChange($user, 'mpin_verify_failed', ['reason' => 'mpin_not_set'], $ipAddress);

            return false;
        }

        $verified = $this->verifyMpinHash($mpin, $settings->getMpinHash());

        $this->logSettingsChange(
            $user,
            $verified ? 'mpin_verify_success' : 'mpin_verify_failed',
            ['reason' => $verified ? null : 'invalid_mpin'],
            $ipAddress
        );

        return $verified;
    }

    /**
     * @return array{app_lock_enabled: bool, biometric_enabled: bool, has_mpin: bool, mpin_updated_at: ?string}
     */
    public function buildResponseData(UserSecuritySettings $settings): array
    {
        return [
            'app_lock_enabled' => $settings->isAppLockEnabled(),
            'biometric_enabled' => $settings->isBiometricEnabled(),
            'has_mpin' => $settings->hasMpin(),
            'mpin_updated_at' => $settings->getMpinUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    private function hashMpin(string $mpin): string
    {
        $algorithm = \defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $hash = password_hash($mpin, $algorithm);

        if (!\is_string($hash) || '' === $hash) {
            throw new \RuntimeException('Unable to hash MPIN.');
        }

        return $hash;
    }

    private function verifyMpinHash(string $mpin, ?string $hash): bool
    {
        if (null === $hash || '' === $hash) {
            return false;
        }

        return password_verify($mpin, $hash);
    }

    private function logSettingsChange(User $user, string $action, ?array $details, ?string $ipAddress): void
    {
        $log = (new UserSecuritySettingsLog($user, $action))
            ->setDetails($details)
            ->setIpAddress($ipAddress);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $this->logger->info('User security settings changed.', [
            'user_id' => $user->getId(),
            'action' => $action,
            'ip_address' => $ipAddress,
        ]);
    }

    private function consumeRateLimit(User $user, string $bucket, int $limit, int $windowSeconds): void
    {
        $cacheKey = sprintf('security_rate_limit_%s_%d', $bucket, (int) $user->getId());
        $cacheItem = $this->cachePool->getItem($cacheKey);

        $now = time();
        $value = $cacheItem->isHit() ? $cacheItem->get() : null;
        $count = \is_array($value) && isset($value['count']) ? (int) $value['count'] : 0;
        $resetAt = \is_array($value) && isset($value['reset_at']) ? (int) $value['reset_at'] : $now + $windowSeconds;

        if ($resetAt <= $now) {
            $count = 0;
            $resetAt = $now + $windowSeconds;
        }

        if ($count >= $limit) {
            throw new RateLimitExceededException('Too many requests. Please try again later.', max(1, $resetAt - $now));
        }

        ++$count;

        $cacheItem->set([
            'count' => $count,
            'reset_at' => $resetAt,
        ]);
        $cacheItem->expiresAt((new \DateTimeImmutable())->setTimestamp($resetAt));
        $this->cachePool->save($cacheItem);
    }
}
