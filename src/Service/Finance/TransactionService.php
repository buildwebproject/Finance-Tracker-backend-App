<?php

declare(strict_types=1);

namespace App\Service\Finance;

use App\Entity\BankAccount;
use App\Entity\FinanceCategory;
use App\Entity\FinanceTransaction;
use App\Entity\User;
use App\Entity\Wallet;
use App\Repository\BankAccountRepository;
use App\Repository\FinanceCategoryRepository;
use App\Repository\FinanceTransactionRepository;
use App\Repository\WalletRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class TransactionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FinanceTransactionRepository $transactionRepository,
        private readonly WalletRepository $walletRepository,
        private readonly BankAccountRepository $bankAccountRepository,
        private readonly FinanceCategoryRepository $categoryRepository,
        private readonly MoneyService $moneyService,
        private readonly CategoryPresentationService $categoryPresentationService,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(User $user, array $data): FinanceTransaction
    {
        $payload = $this->normalizePayload($user, $data);
        $transaction = new FinanceTransaction(
            $user,
            $payload['amount'],
            $payload['is_income'],
            $payload['payment_type'],
            $payload['category'],
            $payload['occurred_at']
        );

        $this->assignSource($transaction, $payload['wallet'], $payload['bank_account']);
        $transaction->setFinanceCategory($payload['finance_category']);
        $transaction->setNote($payload['note']);
        $transaction->setIsSystemGenerated($payload['is_system_generated']);
        $transaction->setSourceType($payload['source_type']);
        $transaction->setSourceId($payload['source_id']);

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $this->applyBalanceImpact($transaction);
            $this->entityManager->persist($transaction);
            $this->entityManager->flush();
            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }

        return $transaction;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(FinanceTransaction $transaction, User $user, array $data): FinanceTransaction
    {
        $payload = $this->normalizePayload($user, $data);

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $this->reverseBalanceImpact($transaction);

            $transaction->setAmount($payload['amount']);
            $transaction->setIsIncome($payload['is_income']);
            $transaction->setPaymentType($payload['payment_type']);
            $transaction->setCategory($payload['category']);
            $transaction->setFinanceCategory($payload['finance_category']);
            $transaction->setOccurredAt($payload['occurred_at']);
            $transaction->setNote($payload['note']);
            $transaction->setIsSystemGenerated($payload['is_system_generated']);
            $transaction->setSourceType($payload['source_type']);
            $transaction->setSourceId($payload['source_id']);
            $this->assignSource($transaction, $payload['wallet'], $payload['bank_account']);

            $this->applyBalanceImpact($transaction);
            $this->entityManager->flush();
            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }

        return $transaction;
    }

    public function delete(FinanceTransaction $transaction): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $this->reverseBalanceImpact($transaction);
            $this->entityManager->remove($transaction);
            $this->entityManager->flush();
            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    /**
     * @param array{
     *     search?: ?string,
     *     type?: ?string,
     *     payment_type?: ?string,
     *     category?: ?string,
     *     category_id?: ?int,
     *     wallet_id?: ?int,
     *     bank_account_id?: ?int,
     *     start_date?: ?\DateTimeImmutable,
     *     end_date?: ?\DateTimeImmutable
     * } $filters
     */
    public function list(User $user, array $filters, int $page, int $perPage): array
    {
        $result = $this->transactionRepository->findPaginatedByUser($user, $filters, $page, $perPage);
        $items = array_map(fn (FinanceTransaction $transaction): array => $this->serializeTransaction($transaction), $result['items']);

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $result['total'],
                'total_pages' => (int) max(1, ceil($result['total'] / max(1, $perPage))),
            ],
        ];
    }

    public function serializeTransaction(FinanceTransaction $transaction): array
    {
        $presentation = $this->categoryPresentationService->getCategoryPresentation($transaction->getCategory());
        $signedAmount = $transaction->isIncome()
            ? $this->moneyService->toFloat($transaction->getAmount())
            : -$this->moneyService->toFloat($transaction->getAmount());

        return [
            'id' => $transaction->getId(),
            'amount' => $signedAmount,
            'is_income' => $transaction->isIncome(),
            'payment_type' => $transaction->getPaymentType(),
            'category' => $transaction->getCategory(),
            'category_id' => $transaction->getFinanceCategory()?->getId(),
            'category_icon' => $transaction->getFinanceCategory()?->getIconName(),
            'category_icon_url' => null !== $transaction->getFinanceCategory()?->getIconName() ? '/uploads/categories/'.$transaction->getFinanceCategory()?->getIconName() : null,
            'wallet_id' => $transaction->getWallet()?->getId(),
            'bank_account_id' => $transaction->getBankAccount()?->getId(),
            'note' => $transaction->getNote(),
            'occurred_at' => $transaction->getOccurredAt()->format(\DateTimeInterface::ATOM),
            'is_system_generated' => $transaction->isSystemGenerated(),
            'source_type' => $transaction->getSourceType(),
            'source_id' => $transaction->getSourceId(),
            'created_at' => $transaction->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'title' => $presentation['title'],
            'subtitle' => $transaction->getNote() ?: $transaction->getCategory(),
            'source_label' => $this->buildSourceLabel($transaction),
            'icon' => $presentation['icon'],
            'color' => $presentation['color'],
            'absolute_amount' => $this->moneyService->toFloat($this->moneyService->absolute($transaction->getAmount())),
        ];
    }

    public function buildMeta(User $user): array
    {
        $wallets = $this->walletRepository->findByUser($user);
        $banks = $this->bankAccountRepository->findByUser($user);
        $definedCategories = $this->categoryRepository->findActiveOrdered();

        $dynamicCategories = $this->transactionRepository->findDistinctCategoriesByUser($user);
        $categories = array_values(array_unique(array_merge($this->categoryPresentationService->defaultCategories(), $dynamicCategories)));
        $defaultWallet = $wallets[0] ?? null;

        return [
            'categories' => $categories,
            'category_items' => array_map(
                static fn (FinanceCategory $category): array => [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'icon' => $category->getIconName(),
                    'icon_url' => null !== $category->getIconName() ? '/uploads/categories/'.$category->getIconName() : null,
                ],
                $definedCategories
            ),
            'payment_types' => [FinanceTransaction::PAYMENT_TYPE_CASH, FinanceTransaction::PAYMENT_TYPE_ONLINE],
            'wallets' => array_map(fn (Wallet $wallet): array => [
                'id' => $wallet->getId(),
                'name' => $wallet->getName(),
                'current_balance' => $this->moneyService->toFloat($wallet->getCurrentBalance()),
            ], $wallets),
            'bank_accounts' => array_map(fn (BankAccount $bank): array => [
                'id' => $bank->getId(),
                'bank_name' => $bank->getBankName(),
                'nickname' => $bank->getNickname(),
                'current_balance' => $this->moneyService->toFloat($bank->getCurrentBalance()),
                'is_default' => $bank->isDefault(),
            ], $banks),
            'default_bank_account' => array_values(array_filter(array_map(
                static fn (BankAccount $bank): ?array => $bank->isDefault() ? [
                    'id' => $bank->getId(),
                    'bank_name' => $bank->getBankName(),
                    'nickname' => $bank->getNickname(),
                ] : null,
                $banks
            )))[0] ?? null,
            'default_wallet' => $defaultWallet instanceof Wallet ? [
                'id' => $defaultWallet->getId(),
                'name' => $defaultWallet->getName(),
            ] : null,
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{
     *     amount: string,
     *     is_income: bool,
     *     payment_type: string,
     *     category: string,
     *     finance_category: ?FinanceCategory,
     *     note: ?string,
     *     occurred_at: \DateTimeImmutable,
     *     is_system_generated: bool,
     *     source_type: ?string,
     *     source_id: ?string,
     *     wallet: ?Wallet,
     *     bank_account: ?BankAccount
     * }
     */
    private function normalizePayload(User $user, array $data): array
    {
        $amount = $this->moneyService->normalize($data['amount'] ?? null);
        if (!$this->moneyService->isGreaterThanZero($amount)) {
            throw new FinanceDomainException('amount must be greater than 0.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!\array_key_exists('is_income', $data) || !\is_bool($data['is_income'])) {
            throw new FinanceDomainException('is_income is required and must be a boolean.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paymentType = mb_strtolower(trim((string) ($data['payment_type'] ?? '')));
        if (!in_array($paymentType, [FinanceTransaction::PAYMENT_TYPE_CASH, FinanceTransaction::PAYMENT_TYPE_ONLINE], true)) {
            throw new FinanceDomainException('payment_type must be cash or online.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $category = trim((string) ($data['category'] ?? ''));
        $categoryId = $this->normalizeNullableInt($data['category_id'] ?? null);
        $financeCategory = null;
        if (null !== $categoryId) {
            $financeCategory = $this->categoryRepository->findOneActiveById($categoryId);
            if (!$financeCategory instanceof FinanceCategory) {
                throw new FinanceDomainException('category_id is invalid.', Response::HTTP_NOT_FOUND);
            }
            $category = $financeCategory->getName();
        }

        if ('' === $category) {
            throw new FinanceDomainException('category or category_id is required.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $occurredAtRaw = trim((string) ($data['occurred_at'] ?? ''));
        if ('' === $occurredAtRaw) {
            throw new FinanceDomainException('occurred_at is required.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        try {
            $occurredAt = new \DateTimeImmutable($occurredAtRaw);
        } catch (\Throwable) {
            throw new FinanceDomainException('occurred_at must be a valid datetime.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $walletId = $this->normalizeNullableInt($data['wallet_id'] ?? null);
        $bankAccountId = $this->normalizeNullableInt($data['bank_account_id'] ?? null);

        if (FinanceTransaction::PAYMENT_TYPE_CASH === $paymentType) {
            if (null === $walletId || null !== $bankAccountId) {
                throw new FinanceDomainException('For cash transactions, wallet_id is required and bank_account_id must be null.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        if (FinanceTransaction::PAYMENT_TYPE_ONLINE === $paymentType) {
            if (null === $bankAccountId || null !== $walletId) {
                throw new FinanceDomainException('For online transactions, bank_account_id is required and wallet_id must be null.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $wallet = null;
        if (null !== $walletId) {
            $wallet = $this->walletRepository->findOneByIdAndUser($walletId, $user);
            if (!$wallet instanceof Wallet) {
                throw new FinanceDomainException('wallet_id is invalid.', Response::HTTP_NOT_FOUND);
            }
        }

        $bankAccount = null;
        if (null !== $bankAccountId) {
            $bankAccount = $this->bankAccountRepository->findOneByIdAndUser($bankAccountId, $user);
            if (!$bankAccount instanceof BankAccount) {
                throw new FinanceDomainException('bank_account_id is invalid.', Response::HTTP_NOT_FOUND);
            }
        }

        $note = null;
        if (\array_key_exists('note', $data) && null !== $data['note']) {
            if (!\is_scalar($data['note'])) {
                throw new FinanceDomainException('note must be a string.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $note = trim((string) $data['note']);
            if (mb_strlen($note) > 1000) {
                throw new FinanceDomainException('note must be at most 1000 characters.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            if ('' === $note) {
                $note = null;
            }
        }

        return [
            'amount' => $amount,
            'is_income' => $data['is_income'],
            'payment_type' => $paymentType,
            'category' => $category,
            'finance_category' => $financeCategory,
            'note' => $note,
            'occurred_at' => $occurredAt,
            'is_system_generated' => \is_bool($data['is_system_generated'] ?? null) ? $data['is_system_generated'] : false,
            'source_type' => $this->normalizeNullableString($data['source_type'] ?? null),
            'source_id' => $this->normalizeNullableString($data['source_id'] ?? null),
            'wallet' => $wallet,
            'bank_account' => $bankAccount,
        ];
    }

    private function assignSource(FinanceTransaction $transaction, ?Wallet $wallet, ?BankAccount $bankAccount): void
    {
        $transaction->setWallet($wallet);
        $transaction->setBankAccount($bankAccount);
    }

    private function applyBalanceImpact(FinanceTransaction $transaction): void
    {
        if (FinanceTransaction::PAYMENT_TYPE_CASH === $transaction->getPaymentType()) {
            $wallet = $transaction->getWallet();
            if (!$wallet instanceof Wallet) {
                throw new FinanceDomainException('wallet_id is required for cash transactions.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $wallet->setCurrentBalance($this->calculateNextBalance($wallet->getCurrentBalance(), $transaction->getAmount(), $transaction->isIncome()));

            return;
        }

        $bank = $transaction->getBankAccount();
        if (!$bank instanceof BankAccount) {
            throw new FinanceDomainException('bank_account_id is required for online transactions.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $bank->setCurrentBalance($this->calculateNextBalance($bank->getCurrentBalance(), $transaction->getAmount(), $transaction->isIncome()));
    }

    private function reverseBalanceImpact(FinanceTransaction $transaction): void
    {
        if (FinanceTransaction::PAYMENT_TYPE_CASH === $transaction->getPaymentType()) {
            $wallet = $transaction->getWallet();
            if ($wallet instanceof Wallet) {
                $wallet->setCurrentBalance($this->calculateNextBalance($wallet->getCurrentBalance(), $transaction->getAmount(), !$transaction->isIncome()));
            }

            return;
        }

        $bank = $transaction->getBankAccount();
        if ($bank instanceof BankAccount) {
            $bank->setCurrentBalance($this->calculateNextBalance($bank->getCurrentBalance(), $transaction->getAmount(), !$transaction->isIncome()));
        }
    }

    private function calculateNextBalance(string $currentBalance, string $amount, bool $isIncome): string
    {
        if ($isIncome) {
            return $this->moneyService->add($currentBalance, $amount);
        }

        return $this->moneyService->subtract($currentBalance, $amount);
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (\is_int($value) && $value > 0) {
            return $value;
        }

        if (\is_scalar($value) && is_numeric((string) $value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (!\is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }

    private function buildSourceLabel(FinanceTransaction $transaction): string
    {
        if (FinanceTransaction::PAYMENT_TYPE_CASH === $transaction->getPaymentType()) {
            return 'Wallet: '.($transaction->getWallet()?->getName() ?? 'Unknown');
        }

        $bankName = $transaction->getBankAccount()?->getNickname() ?: $transaction->getBankAccount()?->getBankName() ?: 'Unknown';

        return 'Bank: '.$bankName;
    }
}
