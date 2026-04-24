<?php

declare(strict_types=1);

namespace App\Service\Finance;

use App\Entity\BankAccount;
use App\Entity\User;
use App\Entity\Wallet;
use App\Repository\BankAccountRepository;
use App\Repository\FinanceTransactionRepository;
use App\Repository\WalletRepository;

final class SummaryService
{
    public function __construct(
        private readonly WalletRepository $walletRepository,
        private readonly BankAccountRepository $bankAccountRepository,
        private readonly FinanceTransactionRepository $transactionRepository,
        private readonly TransactionService $transactionService,
        private readonly MoneyService $moneyService,
        private readonly CategoryPresentationService $categoryPresentationService,
    ) {
    }

    public function buildAccountsOverview(User $user): array
    {
        $wallets = $this->walletRepository->findByUser($user);
        $banks = $this->bankAccountRepository->findByUser($user);

        $totalWallet = '0.00';
        foreach ($wallets as $wallet) {
            $totalWallet = $this->moneyService->add($totalWallet, $wallet->getCurrentBalance());
        }

        $totalBank = '0.00';
        foreach ($banks as $bank) {
            $totalBank = $this->moneyService->add($totalBank, $bank->getCurrentBalance());
        }

        return [
            'total_balance' => $this->moneyService->toFloat($this->moneyService->add($totalWallet, $totalBank)),
            'total_bank' => $this->moneyService->toFloat($totalBank),
            'total_wallet' => $this->moneyService->toFloat($totalWallet),
            'banks' => array_map(fn (BankAccount $bank): array => $this->serializeBank($bank), $banks),
            'wallets' => array_map(fn (Wallet $wallet): array => $this->serializeWallet($wallet), $wallets),
        ];
    }

    public function buildDashboardSummary(User $user): array
    {
        $overview = $this->buildAccountsOverview($user);

        $monthStart = new \DateTimeImmutable('first day of this month 00:00:00');
        $monthEnd = new \DateTimeImmutable('last day of this month 23:59:59');

        $income = $this->transactionRepository->sumIncomeForRange($user, $monthStart, $monthEnd);
        $expense = $this->transactionRepository->sumExpenseForRange($user, $monthStart, $monthEnd);
        $net = $this->moneyService->subtract($income, $expense);

        $recentTransactions = $this->transactionRepository->findRecentByUser($user, 10);
        $categorySpending = $this->transactionRepository->categoryExpenseSummaryForRange($user, $monthStart, $monthEnd, 8);

        return [
            'total_balance' => $overview['total_balance'],
            'current_month_income' => $this->moneyService->toFloat($income),
            'current_month_expense' => $this->moneyService->toFloat($expense),
            'current_month_net' => $this->moneyService->toFloat($net),
            'recent_transactions' => array_map(
                fn ($transaction): array => $this->transactionService->serializeTransaction($transaction),
                $recentTransactions
            ),
            'category_spending' => array_map(function (array $row): array {
                $presentation = $this->categoryPresentationService->getCategoryPresentation($row['category']);

                return [
                    'category' => $row['category'],
                    'amount' => $this->moneyService->toFloat($row['total']),
                    'icon' => $presentation['icon'],
                    'color' => $presentation['color'],
                ];
            }, $categorySpending),
        ];
    }

    private function serializeBank(BankAccount $bank): array
    {
        return [
            'id' => $bank->getId(),
            'name' => $bank->getBankName(),
            'mask_or_nickname' => $bank->getNickname() ?: $bank->getBankName(),
            'balance' => $this->moneyService->toFloat($bank->getCurrentBalance()),
            'is_default' => $bank->isDefault(),
            'icon' => 'account_balance',
            'color' => '#2C3E50',
        ];
    }

    private function serializeWallet(Wallet $wallet): array
    {
        return [
            'id' => $wallet->getId(),
            'name' => $wallet->getName(),
            'balance' => $this->moneyService->toFloat($wallet->getCurrentBalance()),
            'icon' => null !== $wallet->getIconCodePoint() ? (string) $wallet->getIconCodePoint() : 'account_balance_wallet',
            'color' => $wallet->getColorValue() ?: '#16A085',
        ];
    }
}

