<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BankAccountRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BankAccountRepository::class)]
#[ORM\Table(name: 'bank_account')]
#[ORM\Index(name: 'idx_bank_account_user_default', columns: ['user_id', 'is_default'])]
#[ORM\HasLifecycleCallbacks]
class BankAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'bank_name', type: 'string', length: 120)]
    private string $bankName;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    private ?string $nickname = null;

    #[ORM\Column(name: 'starting_balance', type: 'decimal', precision: 14, scale: 2, options: ['default' => '0.00'])]
    private string $startingBalance = '0.00';

    #[ORM\Column(name: 'current_balance', type: 'decimal', precision: 14, scale: 2, options: ['default' => '0.00'])]
    private string $currentBalance = '0.00';

    #[ORM\Column(name: 'is_default', type: 'boolean', options: ['default' => false])]
    private bool $isDefault = false;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, string $bankName, string $startingBalance)
    {
        $this->user = $user;
        $this->bankName = $bankName;
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

    public function getBankName(): string
    {
        return $this->bankName;
    }

    public function setBankName(string $bankName): self
    {
        $bankName = trim($bankName);
        if ('' !== $bankName) {
            $this->bankName = $bankName;
        }

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): self
    {
        $nickname = null === $nickname ? null : trim($nickname);
        $this->nickname = '' === $nickname ? null : $nickname;

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

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

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
        $name = $this->nickname ?: $this->bankName;

        return sprintf('%s (#%d)', $name, $id);
    }
}
