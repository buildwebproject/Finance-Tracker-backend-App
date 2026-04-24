<?php

declare(strict_types=1);

namespace App\Service\Finance;

use App\Entity\User;
use App\Entity\Wallet;
use App\Repository\FinanceTransactionRepository;
use App\Repository\WalletRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class WalletService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WalletRepository $walletRepository,
        private readonly FinanceTransactionRepository $transactionRepository,
        private readonly MoneyService $moneyService,
    ) {
    }

    /**
     * @param array{name: string, starting_balance: mixed, color_value?: ?string, icon_code_point?: ?int} $data
     */
    public function create(User $user, array $data): Wallet
    {
        $startingBalance = $this->moneyService->normalize($data['starting_balance']);

        $wallet = new Wallet(
            $user,
            trim($data['name']),
            $startingBalance
        );

        $wallet->setColorValue($data['color_value'] ?? null);
        $wallet->setIconCodePoint($data['icon_code_point'] ?? null);

        $this->entityManager->persist($wallet);
        $this->entityManager->flush();

        return $wallet;
    }

    /**
     * @param array{name?: ?string, starting_balance?: mixed, color_value?: ?string, icon_code_point?: ?int} $data
     */
    public function update(Wallet $wallet, array $data, User $user): Wallet
    {
        if (\array_key_exists('name', $data) && \is_scalar($data['name'])) {
            $wallet->setName(trim((string) $data['name']));
        }

        if (\array_key_exists('starting_balance', $data)) {
            if ($this->transactionRepository->hasAnyForWalletUser($wallet, $user)) {
                throw new FinanceDomainException('starting_balance cannot be changed after transactions exist.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $startingBalance = $this->moneyService->normalize($data['starting_balance']);
            $wallet->setStartingBalance($startingBalance);
            $wallet->setCurrentBalance($startingBalance);
        }

        if (\array_key_exists('color_value', $data)) {
            $wallet->setColorValue($data['color_value']);
        }

        if (\array_key_exists('icon_code_point', $data)) {
            $wallet->setIconCodePoint(\is_int($data['icon_code_point']) ? $data['icon_code_point'] : null);
        }

        $this->entityManager->flush();

        return $wallet;
    }

    public function delete(Wallet $wallet): void
    {
        if ($this->transactionRepository->hasAnyForWallet($wallet)) {
            throw new FinanceDomainException('Wallet cannot be deleted because linked transactions exist.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->remove($wallet);
        $this->entityManager->flush();
    }

    /**
     * @return list<Wallet>
     */
    public function list(User $user): array
    {
        return $this->walletRepository->findByUser($user);
    }
}

