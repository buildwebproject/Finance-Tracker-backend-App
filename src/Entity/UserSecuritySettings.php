<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserSecuritySettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSecuritySettingsRepository::class)]
#[ORM\Table(
    name: 'user_security_settings',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uniq_user_security_settings_user_id', columns: ['user_id']),
    ]
)]
#[ORM\HasLifecycleCallbacks]
class UserSecuritySettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'app_lock_enabled', type: 'boolean', options: ['default' => false])]
    private bool $appLockEnabled = false;

    #[ORM\Column(name: 'biometric_enabled', type: 'boolean', options: ['default' => false])]
    private bool $biometricEnabled = false;

    #[ORM\Column(name: 'mpin_hash', type: 'string', length: 255, nullable: true)]
    private ?string $mpinHash = null;

    #[ORM\Column(name: 'mpin_updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $mpinUpdatedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function isAppLockEnabled(): bool
    {
        return $this->appLockEnabled;
    }

    public function setAppLockEnabled(bool $appLockEnabled): self
    {
        $this->appLockEnabled = $appLockEnabled;

        return $this;
    }

    public function isBiometricEnabled(): bool
    {
        return $this->biometricEnabled;
    }

    public function setBiometricEnabled(bool $biometricEnabled): self
    {
        $this->biometricEnabled = $biometricEnabled;

        return $this;
    }

    public function getMpinHash(): ?string
    {
        return $this->mpinHash;
    }

    public function setMpinHash(?string $mpinHash): self
    {
        $this->mpinHash = $mpinHash;

        return $this;
    }

    public function getMpinUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->mpinUpdatedAt;
    }

    public function setMpinUpdatedAt(?\DateTimeImmutable $mpinUpdatedAt): self
    {
        $this->mpinUpdatedAt = $mpinUpdatedAt;

        return $this;
    }

    public function hasMpin(): bool
    {
        return null !== $this->mpinHash;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function updateTimestampsOnCreate(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function updateTimestampOnUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
