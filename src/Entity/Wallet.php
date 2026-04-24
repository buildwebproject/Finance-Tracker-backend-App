<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WalletRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WalletRepository::class)]
#[ORM\Table(name: 'wallet')]
#[ORM\Index(name: 'idx_wallet_user_created', columns: ['user_id', 'created_at'])]
#[ORM\HasLifecycleCallbacks]
class Wallet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 120)]
    private string $name;

    #[ORM\Column(name: 'starting_balance', type: 'decimal', precision: 14, scale: 2, options: ['default' => '0.00'])]
    private string $startingBalance = '0.00';

    #[ORM\Column(name: 'current_balance', type: 'decimal', precision: 14, scale: 2, options: ['default' => '0.00'])]
    private string $currentBalance = '0.00';

    #[ORM\Column(name: 'color_value', type: 'string', length: 32, nullable: true)]
    private ?string $colorValue = null;

    #[ORM\Column(name: 'icon_code_point', type: 'integer', nullable: true)]
    private ?int $iconCodePoint = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, string $name, string $startingBalance)
    {
        $this->user = $user;
        $this->name = $name;
        $this->startingBalance = $startingBalance;
        $this->currentBalance = $startingBalance;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $name = trim($name);
        if ('' !== $name) {
            $this->name = $name;
        }

        return $this;
    }

    public function getStartingBalance(): string
    {
        return $this->startingBalance;
    }

    public function setStartingBalance(string $startingBalance): self
    {
        $this->startingBalance = $startingBalance;

        return $this;
    }

    public function getCurrentBalance(): string
    {
        return $this->currentBalance;
    }

    public function setCurrentBalance(string $currentBalance): self
    {
        $this->currentBalance = $currentBalance;

        return $this;
    }

    public function getColorValue(): ?string
    {
        return $this->colorValue;
    }

    public function setColorValue(?string $colorValue): self
    {
        $colorValue = null === $colorValue ? null : trim($colorValue);
        $this->colorValue = '' === $colorValue ? null : $colorValue;

        return $this;
    }

    public function getIconCodePoint(): ?int
    {
        return $this->iconCodePoint;
    }

    public function setIconCodePoint(?int $iconCodePoint): self
    {
        $this->iconCodePoint = $iconCodePoint;

        return $this;
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

    public function __toString(): string
    {
        $id = $this->id ?? 0;

        return sprintf('%s (#%d)', $this->name, $id);
    }
}
