<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FinanceTransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FinanceTransactionRepository::class)]
#[ORM\Table(name: 'finance_transaction')]
#[ORM\Index(name: 'idx_finance_transaction_user_occurred', columns: ['user_id', 'occurred_at'])]
#[ORM\Index(name: 'idx_finance_transaction_payment_type', columns: ['payment_type'])]
#[ORM\Index(name: 'idx_finance_transaction_category', columns: ['category'])]
#[ORM\Index(name: 'idx_finance_transaction_finance_category', columns: ['finance_category_id'])]
#[ORM\HasLifecycleCallbacks]
class FinanceTransaction
{
    public const PAYMENT_TYPE_CASH = 'cash';
    public const PAYMENT_TYPE_ONLINE = 'online';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Wallet::class)]
    #[ORM\JoinColumn(name: 'wallet_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    private ?Wallet $wallet = null;

    #[ORM\ManyToOne(targetEntity: BankAccount::class)]
    #[ORM\JoinColumn(name: 'bank_account_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    private ?BankAccount $bankAccount = null;

    #[ORM\Column(type: 'decimal', precision: 14, scale: 2)]
    private string $amount;

    #[ORM\Column(name: 'is_income', type: 'boolean')]
    private bool $isIncome;

    #[ORM\Column(name: 'payment_type', type: 'string', length: 10)]
    private string $paymentType;

    #[ORM\Column(name: 'category', type: 'string', length: 120)]
    private string $category;

    #[ORM\ManyToOne(targetEntity: FinanceCategory::class)]
    #[ORM\JoinColumn(name: 'finance_category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?FinanceCategory $financeCategory = null;

    #[ORM\Column(name: 'note', type: 'string', length: 1000, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(name: 'occurred_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column(name: 'is_system_generated', type: 'boolean', options: ['default' => false])]
    private bool $isSystemGenerated = false;

    #[ORM\Column(name: 'source_type', type: 'string', length: 80, nullable: true)]
    private ?string $sourceType = null;

    #[ORM\Column(name: 'source_id', type: 'string', length: 120, nullable: true)]
    private ?string $sourceId = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, string $amount, bool $isIncome, string $paymentType, string $category, \DateTimeImmutable $occurredAt)
    {
        $this->user = $user;
        $this->amount = $amount;
        $this->isIncome = $isIncome;
        $this->paymentType = $paymentType;
        $this->category = $category;
        $this->occurredAt = $occurredAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }

    public function setWallet(?Wallet $wallet): self
    {
        $this->wallet = $wallet;

        return $this;
    }

    public function getBankAccount(): ?BankAccount
    {
        return $this->bankAccount;
    }

    public function setBankAccount(?BankAccount $bankAccount): self
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function isIncome(): bool
    {
        return $this->isIncome;
    }

    public function setIsIncome(bool $isIncome): self
    {
        $this->isIncome = $isIncome;

        return $this;
    }

    public function getPaymentType(): string
    {
        return $this->paymentType;
    }

    public function setPaymentType(string $paymentType): self
    {
        $this->paymentType = $paymentType;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $category = trim($category);
        if ('' !== $category) {
            $this->category = $category;
        }

        return $this;
    }

    public function getFinanceCategory(): ?FinanceCategory
    {
        return $this->financeCategory;
    }

    public function setFinanceCategory(?FinanceCategory $financeCategory): self
    {
        $this->financeCategory = $financeCategory;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $note = null === $note ? null : trim($note);
        $this->note = '' === $note ? null : $note;

        return $this;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(\DateTimeImmutable $occurredAt): self
    {
        $this->occurredAt = $occurredAt;

        return $this;
    }

    public function isSystemGenerated(): bool
    {
        return $this->isSystemGenerated;
    }

    public function setIsSystemGenerated(bool $isSystemGenerated): self
    {
        $this->isSystemGenerated = $isSystemGenerated;

        return $this;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(?string $sourceType): self
    {
        $sourceType = null === $sourceType ? null : trim($sourceType);
        $this->sourceType = '' === $sourceType ? null : $sourceType;

        return $this;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    public function setSourceId(?string $sourceId): self
    {
        $sourceId = null === $sourceId ? null : trim($sourceId);
        $this->sourceId = '' === $sourceId ? null : $sourceId;

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

        return sprintf(
            '%s %s (%s) #%d',
            $this->isIncome ? 'Income' : 'Expense',
            $this->amount,
            $this->category,
            $id
        );
    }
}
