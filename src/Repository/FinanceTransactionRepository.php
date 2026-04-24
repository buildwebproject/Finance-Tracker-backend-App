<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BankAccount;
use App\Entity\FinanceTransaction;
use App\Entity\User;
use App\Entity\Wallet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FinanceTransaction>
 */
class FinanceTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinanceTransaction::class);
    }

    public function findOneByIdAndUser(int $id, User $user): ?FinanceTransaction
    {
        return $this->createQueryBuilder('txn')
            ->leftJoin('txn.wallet', 'wallet')->addSelect('wallet')
            ->leftJoin('txn.bankAccount', 'bank')->addSelect('bank')
            ->leftJoin('txn.financeCategory', 'financeCategory')->addSelect('financeCategory')
            ->andWhere('txn.id = :id')
            ->andWhere('txn.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
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
     *
     * @return array{items: list<FinanceTransaction>, total: int}
     */
    public function findPaginatedByUser(User $user, array $filters, int $page, int $perPage): array
    {
        $qb = $this->createBaseFilterQueryBuilder($user, $filters)
            ->orderBy('txn.occurredAt', 'DESC')
            ->addOrderBy('txn.id', 'DESC');

        $items = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        $countQb = $this->createBaseFilterQueryBuilder($user, $filters)
            ->select('COUNT(txn.id)');

        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * @return list<FinanceTransaction>
     */
    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('txn')
            ->leftJoin('txn.wallet', 'wallet')->addSelect('wallet')
            ->leftJoin('txn.bankAccount', 'bank')->addSelect('bank')
            ->leftJoin('txn.financeCategory', 'financeCategory')->addSelect('financeCategory')
            ->andWhere('txn.user = :user')
            ->setParameter('user', $user)
            ->orderBy('txn.occurredAt', 'DESC')
            ->addOrderBy('txn.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function hasAnyForWallet(Wallet $wallet): bool
    {
        return (int) $this->createQueryBuilder('txn')
            ->select('COUNT(txn.id)')
            ->andWhere('txn.wallet = :wallet')
            ->setParameter('wallet', $wallet)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function hasAnyForBankAccount(BankAccount $bankAccount): bool
    {
        return (int) $this->createQueryBuilder('txn')
            ->select('COUNT(txn.id)')
            ->andWhere('txn.bankAccount = :bankAccount')
            ->setParameter('bankAccount', $bankAccount)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function hasAnyForWalletUser(Wallet $wallet, User $user): bool
    {
        return (int) $this->createQueryBuilder('txn')
            ->select('COUNT(txn.id)')
            ->andWhere('txn.wallet = :wallet')
            ->andWhere('txn.user = :user')
            ->setParameter('wallet', $wallet)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function hasAnyForBankAccountUser(BankAccount $bankAccount, User $user): bool
    {
        return (int) $this->createQueryBuilder('txn')
            ->select('COUNT(txn.id)')
            ->andWhere('txn.bankAccount = :bank')
            ->andWhere('txn.user = :user')
            ->setParameter('bank', $bankAccount)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function sumIncomeForRange(User $user, \DateTimeImmutable $from, \DateTimeImmutable $to): string
    {
        $value = $this->createQueryBuilder('txn')
            ->select('COALESCE(SUM(txn.amount), 0)')
            ->andWhere('txn.user = :user')
            ->andWhere('txn.isIncome = :isIncome')
            ->andWhere('txn.occurredAt >= :from')
            ->andWhere('txn.occurredAt <= :to')
            ->setParameter('user', $user)
            ->setParameter('isIncome', true)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();

        return (string) $value;
    }

    public function sumExpenseForRange(User $user, \DateTimeImmutable $from, \DateTimeImmutable $to): string
    {
        $value = $this->createQueryBuilder('txn')
            ->select('COALESCE(SUM(txn.amount), 0)')
            ->andWhere('txn.user = :user')
            ->andWhere('txn.isIncome = :isIncome')
            ->andWhere('txn.occurredAt >= :from')
            ->andWhere('txn.occurredAt <= :to')
            ->setParameter('user', $user)
            ->setParameter('isIncome', false)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();

        return (string) $value;
    }

    /**
     * @return list<array{category: string, total: string}>
     */
    public function categoryExpenseSummaryForRange(User $user, \DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 10): array
    {
        $rows = $this->createQueryBuilder('txn')
            ->select('txn.category AS category, COALESCE(SUM(txn.amount), 0) AS total')
            ->andWhere('txn.user = :user')
            ->andWhere('txn.isIncome = :isIncome')
            ->andWhere('txn.occurredAt >= :from')
            ->andWhere('txn.occurredAt <= :to')
            ->groupBy('txn.category')
            ->orderBy('total', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('user', $user)
            ->setParameter('isIncome', false)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'category' => (string) ($row['category'] ?? ''),
                'total' => (string) ($row['total'] ?? '0'),
            ],
            $rows
        );
    }

    /**
     * @return list<string>
     */
    public function findDistinctCategoriesByUser(User $user): array
    {
        $rows = $this->createQueryBuilder('txn')
            ->select('DISTINCT txn.category AS category')
            ->andWhere('txn.user = :user')
            ->setParameter('user', $user)
            ->orderBy('txn.category', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $categories = [];
        foreach ($rows as $row) {
            $category = trim((string) ($row['category'] ?? ''));
            if ('' !== $category) {
                $categories[] = $category;
            }
        }

        return $categories;
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
    private function createBaseFilterQueryBuilder(User $user, array $filters)
    {
        $qb = $this->createQueryBuilder('txn')
            ->leftJoin('txn.wallet', 'wallet')->addSelect('wallet')
            ->leftJoin('txn.bankAccount', 'bank')->addSelect('bank')
            ->leftJoin('txn.financeCategory', 'financeCategory')->addSelect('financeCategory')
            ->andWhere('txn.user = :user')
            ->setParameter('user', $user);

        $search = $filters['search'] ?? null;
        if (\is_string($search) && '' !== $search) {
            $qb->andWhere('(LOWER(txn.category) LIKE :search OR LOWER(txn.note) LIKE :search OR LOWER(financeCategory.name) LIKE :search)')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        $type = $filters['type'] ?? null;
        if ('income' === $type) {
            $qb->andWhere('txn.isIncome = :isIncomeTrue')->setParameter('isIncomeTrue', true);
        } elseif ('expense' === $type) {
            $qb->andWhere('txn.isIncome = :isIncomeFalse')->setParameter('isIncomeFalse', false);
        }

        $paymentType = $filters['payment_type'] ?? null;
        if (\is_string($paymentType) && '' !== $paymentType) {
            $qb->andWhere('txn.paymentType = :paymentType')->setParameter('paymentType', $paymentType);
        }

        $category = $filters['category'] ?? null;
        if (\is_string($category) && '' !== $category) {
            $qb->andWhere('LOWER(txn.category) = :category')->setParameter('category', mb_strtolower($category));
        }

        $categoryId = $filters['category_id'] ?? null;
        if (\is_int($categoryId) && $categoryId > 0) {
            $qb->andWhere('financeCategory.id = :categoryId')->setParameter('categoryId', $categoryId);
        }

        $walletId = $filters['wallet_id'] ?? null;
        if (\is_int($walletId) && $walletId > 0) {
            $qb->andWhere('wallet.id = :walletId')->setParameter('walletId', $walletId);
        }

        $bankAccountId = $filters['bank_account_id'] ?? null;
        if (\is_int($bankAccountId) && $bankAccountId > 0) {
            $qb->andWhere('bank.id = :bankAccountId')->setParameter('bankAccountId', $bankAccountId);
        }

        $startDate = $filters['start_date'] ?? null;
        if ($startDate instanceof \DateTimeImmutable) {
            $qb->andWhere('txn.occurredAt >= :startDate')->setParameter('startDate', $startDate);
        }

        $endDate = $filters['end_date'] ?? null;
        if ($endDate instanceof \DateTimeImmutable) {
            $qb->andWhere('txn.occurredAt <= :endDate')->setParameter('endDate', $endDate);
        }

        return $qb;
    }
}
