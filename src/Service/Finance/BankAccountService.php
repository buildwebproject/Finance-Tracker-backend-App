<?php

declare(strict_types=1);

namespace App\Service\Finance;

use App\Entity\BankAccount;
use App\Entity\User;
use App\Repository\BankAccountRepository;
use App\Repository\FinanceTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class BankAccountService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BankAccountRepository $bankAccountRepository,
        private readonly FinanceTransactionRepository $transactionRepository,
        private readonly MoneyService $moneyService,
    ) {
    }

    /**
     * @param array{bank_name: string, nickname?: ?string, starting_balance: mixed, is_default?: bool} $data
     */
    public function create(User $user, array $data): BankAccount
    {
        $startingBalance = $this->moneyService->normalize($data['starting_balance']);
        $isDefaultRequested = (bool) ($data['is_default'] ?? false);
        $isFirst = 0 === $this->bankAccountRepository->countByUser($user);

        $bankAccount = new BankAccount(
            $user,
            trim($data['bank_name']),
            $startingBalance
        );

        $bankAccount->setNickname($data['nickname'] ?? null);
        $bankAccount->setIsDefault($isFirst || $isDefaultRequested);

        if ($bankAccount->isDefault()) {
            $this->clearExistingDefault($user);
        }

        $this->entityManager->persist($bankAccount);
        $this->entityManager->flush();

        return $bankAccount;
    }

    /**
     * @param array{bank_name?: ?string, nickname?: ?string, starting_balance?: mixed, is_default?: bool} $data
     */
    public function update(BankAccount $bankAccount, array $data, User $user): BankAccount
    {
        if (\array_key_exists('bank_name', $data) && \is_scalar($data['bank_name'])) {
            $bankAccount->setBankName(trim((string) $data['bank_name']));
        }

        if (\array_key_exists('nickname', $data)) {
            $bankAccount->setNickname($data['nickname']);
        }

        if (\array_key_exists('starting_balance', $data)) {
            if ($this->transactionRepository->hasAnyForBankAccountUser($bankAccount, $user)) {
                throw new FinanceDomainException('starting_balance cannot be changed after transactions exist.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $startingBalance = $this->moneyService->normalize($data['starting_balance']);
            $bankAccount->setStartingBalance($startingBalance);
            $bankAccount->setCurrentBalance($startingBalance);
        }

        if (\array_key_exists('is_default', $data) && true === $data['is_default']) {
            $this->setDefault($bankAccount, $user);
        } else {
            $this->entityManager->flush();
        }

        return $bankAccount;
    }

    public function setDefault(BankAccount $bankAccount, User $user): BankAccount
    {
        $this->clearExistingDefault($user);
        $bankAccount->setIsDefault(true);
        $this->entityManager->flush();

        return $bankAccount;
    }

    public function delete(BankAccount $bankAccount, User $user): void
    {
        if ($this->transactionRepository->hasAnyForBankAccount($bankAccount)) {
            throw new FinanceDomainException('Bank account cannot be deleted because linked transactions exist.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->hasLinkedFinanceModules($bankAccount, $user)) {
            throw new FinanceDomainException('Bank account cannot be deleted because it is linked to active finance modules.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $wasDefault = $bankAccount->isDefault();

        $this->entityManager->remove($bankAccount);
        $this->entityManager->flush();

        if ($wasDefault) {
            $next = $this->bankAccountRepository->findByUser($user)[0] ?? null;
            if ($next instanceof BankAccount) {
                $next->setIsDefault(true);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * @return list<BankAccount>
     */
    public function list(User $user): array
    {
        return $this->bankAccountRepository->findByUser($user);
    }

    private function clearExistingDefault(User $user): void
    {
        $currentDefault = $this->bankAccountRepository->findDefaultByUser($user);
        if (null !== $currentDefault) {
            $currentDefault->setIsDefault(false);
        }
    }

    private function hasLinkedFinanceModules(BankAccount $bankAccount, User $user): bool
    {
        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        $candidateTables = array_filter($tables, static fn (string $table): bool => str_contains($table, 'emi') || str_contains($table, 'loan'));
        foreach ($candidateTables as $table) {
            $columns = array_map(static fn ($column): string => $column->getName(), $schemaManager->listTableColumns($table));
            if (!in_array('bank_account_id', $columns, true)) {
                continue;
            }

            $sql = sprintf('SELECT COUNT(*) FROM %s WHERE bank_account_id = :bankAccountId', $table);
            $params = ['bankAccountId' => $bankAccount->getId()];
            $types = ['bankAccountId' => \PDO::PARAM_INT];

            if (in_array('user_id', $columns, true)) {
                $sql .= ' AND user_id = :userId';
                $params['userId'] = $user->getId();
                $types['userId'] = \PDO::PARAM_INT;
            }

            $count = (int) $connection->executeQuery($sql, $params, $types)->fetchOne();
            if ($count > 0) {
                return true;
            }
        }

        return false;
    }
}
