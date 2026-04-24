<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\User;
use App\Security\ApiTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FinanceApiTest extends WebTestCase
{
    public function testCreateExpenseFromWallet(): void
    {
        $client = static::createClient();
        $user = $this->createUser('finance_wallet_expense_'.uniqid());

        $walletId = $this->createWallet($client, $user, 'Cash Wallet', 100.00);
        $this->createTransaction($client, $user, [
            'amount' => 25,
            'is_income' => false,
            'payment_type' => 'cash',
            'category' => 'Food',
            'wallet_id' => $walletId,
            'occurred_at' => '2026-03-05T10:30:00Z',
        ]);

        $wallet = $this->showWallet($client, $user, $walletId);
        self::assertSame(75.0, (float) $wallet['current_balance']);
    }

    public function testCreateExpenseFromBank(): void
    {
        $client = static::createClient();
        $user = $this->createUser('finance_bank_expense_'.uniqid());

        $bankId = $this->createBankAccount($client, $user, 'ABC Bank', 'Primary', 200.00);
        $this->createTransaction($client, $user, [
            'amount' => 80,
            'is_income' => false,
            'payment_type' => 'online',
            'category' => 'Bills',
            'bank_account_id' => $bankId,
            'occurred_at' => '2026-03-05T12:30:00Z',
        ]);

        $bank = $this->showBankAccount($client, $user, $bankId);
        self::assertSame(120.0, (float) $bank['current_balance']);
    }

    public function testCreateIncomeToWallet(): void
    {
        $client = static::createClient();
        $user = $this->createUser('finance_wallet_income_'.uniqid());

        $walletId = $this->createWallet($client, $user, 'Cash Wallet', 100.00);
        $this->createTransaction($client, $user, [
            'amount' => 50,
            'is_income' => true,
            'payment_type' => 'cash',
            'category' => 'Salary',
            'wallet_id' => $walletId,
            'occurred_at' => '2026-03-06T10:30:00Z',
        ]);

        $wallet = $this->showWallet($client, $user, $walletId);
        self::assertSame(150.0, (float) $wallet['current_balance']);
    }

    public function testCreateIncomeToBank(): void
    {
        $client = static::createClient();
        $user = $this->createUser('finance_bank_income_'.uniqid());

        $bankId = $this->createBankAccount($client, $user, 'XYZ Bank', 'Savings', 300.00);
        $this->createTransaction($client, $user, [
            'amount' => 75,
            'is_income' => true,
            'payment_type' => 'online',
            'category' => 'Freelance',
            'bank_account_id' => $bankId,
            'occurred_at' => '2026-03-07T10:30:00Z',
        ]);

        $bank = $this->showBankAccount($client, $user, $bankId);
        self::assertSame(375.0, (float) $bank['current_balance']);
    }

    public function testDeleteTransactionReversesBalance(): void
    {
        $client = static::createClient();
        $user = $this->createUser('finance_delete_reverse_'.uniqid());

        $walletId = $this->createWallet($client, $user, 'Pocket', 100.00);
        $transactionId = $this->createTransaction($client, $user, [
            'amount' => 30,
            'is_income' => false,
            'payment_type' => 'cash',
            'category' => 'Transport',
            'wallet_id' => $walletId,
            'occurred_at' => '2026-03-08T10:30:00Z',
        ]);

        $this->deleteTransaction($client, $user, $transactionId);

        $wallet = $this->showWallet($client, $user, $walletId);
        self::assertSame(100.0, (float) $wallet['current_balance']);
    }

    public function testInvalidOwnershipAccessIsBlocked(): void
    {
        $client = static::createClient();
        $userA = $this->createUser('finance_owner_a_'.uniqid());
        $userB = $this->createUser('finance_owner_b_'.uniqid());

        $walletId = $this->createWallet($client, $userA, 'A Wallet', 100.00);

        $client->request(
            'POST',
            '/api/transactions',
            [],
            [],
            $this->jsonHeaders($userB),
            json_encode([
                'amount' => 10,
                'is_income' => false,
                'payment_type' => 'cash',
                'category' => 'Food',
                'wallet_id' => $walletId,
                'occurred_at' => '2026-03-09T10:30:00Z',
            ], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testDefaultBankBehaviorAndDeleteGuard(): void
    {
        $client = static::createClient();
        $user = $this->createUser('finance_default_bank_'.uniqid());

        $firstBankId = $this->createBankAccount($client, $user, 'First Bank', 'One', 100.00);
        $secondBankId = $this->createBankAccount($client, $user, 'Second Bank', 'Two', 200.00);

        $firstBank = $this->showBankAccount($client, $user, $firstBankId);
        $secondBank = $this->showBankAccount($client, $user, $secondBankId);

        self::assertTrue((bool) $firstBank['is_default']);
        self::assertFalse((bool) $secondBank['is_default']);

        $this->setDefaultBank($client, $user, $secondBankId);
        $secondBank = $this->showBankAccount($client, $user, $secondBankId);
        self::assertTrue((bool) $secondBank['is_default']);

        $this->createTransaction($client, $user, [
            'amount' => 40,
            'is_income' => false,
            'payment_type' => 'online',
            'category' => 'Bills',
            'bank_account_id' => $secondBankId,
            'occurred_at' => '2026-03-09T11:00:00Z',
        ]);

        $client->request('DELETE', '/api/bank-accounts/'.$secondBankId, [], [], $this->authHeaders($user));
        self::assertSame(422, $client->getResponse()->getStatusCode());
    }

    public function testDeleteWalletWithLinkedTransactionsFails(): void
    {
        $client = static::createClient();
        $user = $this->createUser('finance_wallet_delete_guard_'.uniqid());

        $walletId = $this->createWallet($client, $user, 'Guard Wallet', 100.00);
        $this->createTransaction($client, $user, [
            'amount' => 15,
            'is_income' => false,
            'payment_type' => 'cash',
            'category' => 'Food',
            'wallet_id' => $walletId,
            'occurred_at' => '2026-03-09T11:30:00Z',
        ]);

        $client->request('DELETE', '/api/wallets/'.$walletId, [], [], $this->authHeaders($user));
        self::assertSame(422, $client->getResponse()->getStatusCode());
    }

    public function testSearchAndDateFilters(): void
    {
        $client = static::createClient();
        $user = $this->createUser('finance_filters_'.uniqid());

        $walletId = $this->createWallet($client, $user, 'Filter Wallet', 300.00);

        $this->createTransaction($client, $user, [
            'amount' => 20,
            'is_income' => false,
            'payment_type' => 'cash',
            'category' => 'Food',
            'wallet_id' => $walletId,
            'note' => 'Coffee with client',
            'occurred_at' => '2026-03-01T08:00:00Z',
        ]);

        $this->createTransaction($client, $user, [
            'amount' => 60,
            'is_income' => false,
            'payment_type' => 'cash',
            'category' => 'Transport',
            'wallet_id' => $walletId,
            'note' => 'Taxi',
            'occurred_at' => '2026-03-08T08:00:00Z',
        ]);

        $client->request(
            'GET',
            '/api/transactions?search=coffee&start_date=2026-03-01&end_date=2026-03-03&page=1&per_page=20',
            [],
            [],
            $this->authHeaders($user)
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $data = $this->decodeJson($client->getResponse()->getContent() ?: '{}');
        self::assertCount(1, $data['data']['items']);
        self::assertSame('Food', $data['data']['items'][0]['category']);
    }

    private function createUser(string $username): User
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@example.com');
        $user->setEnabled(true);
        $user->setPlainPassword('Password@123');

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function createWallet($client, User $user, string $name, float $startingBalance): int
    {
        $client->request(
            'POST',
            '/api/wallets',
            [],
            [],
            $this->jsonHeaders($user),
            json_encode([
                'name' => $name,
                'starting_balance' => $startingBalance,
                'color_value' => '#123456',
                'icon_code_point' => 12345,
            ], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $data = $this->decodeJson($client->getResponse()->getContent() ?: '{}');

        return (int) $data['data']['wallet']['id'];
    }

    private function createBankAccount($client, User $user, string $bankName, string $nickname, float $startingBalance): int
    {
        $client->request(
            'POST',
            '/api/bank-accounts',
            [],
            [],
            $this->jsonHeaders($user),
            json_encode([
                'bank_name' => $bankName,
                'nickname' => $nickname,
                'starting_balance' => $startingBalance,
            ], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $data = $this->decodeJson($client->getResponse()->getContent() ?: '{}');

        return (int) $data['data']['bank_account']['id'];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createTransaction($client, User $user, array $payload): int
    {
        $client->request(
            'POST',
            '/api/transactions',
            [],
            [],
            $this->jsonHeaders($user),
            json_encode($payload, \JSON_THROW_ON_ERROR)
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $data = $this->decodeJson($client->getResponse()->getContent() ?: '{}');

        return (int) $data['data']['transaction']['id'];
    }

    /**
     * @return array<string, mixed>
     */
    private function showWallet($client, User $user, int $walletId): array
    {
        $client->request('GET', '/api/wallets/'.$walletId, [], [], $this->authHeaders($user));
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $data = $this->decodeJson($client->getResponse()->getContent() ?: '{}');

        return $data['data']['wallet'];
    }

    /**
     * @return array<string, mixed>
     */
    private function showBankAccount($client, User $user, int $bankId): array
    {
        $client->request('GET', '/api/bank-accounts/'.$bankId, [], [], $this->authHeaders($user));
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $data = $this->decodeJson($client->getResponse()->getContent() ?: '{}');

        return $data['data']['bank_account'];
    }

    private function setDefaultBank($client, User $user, int $bankId): void
    {
        $client->request('POST', '/api/bank-accounts/'.$bankId.'/set-default', [], [], $this->authHeaders($user));
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

    private function deleteTransaction($client, User $user, int $transactionId): void
    {
        $client->request('DELETE', '/api/transactions/'.$transactionId, [], [], $this->authHeaders($user));
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(User $user): array
    {
        /** @var ApiTokenManager $tokenManager */
        $tokenManager = self::getContainer()->get(ApiTokenManager::class);
        $tokenData = $tokenManager->issueToken($user);

        return [
            'HTTP_AUTHORIZATION' => 'Bearer '.$tokenData['access_token'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function jsonHeaders(User $user): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
            ...$this->authHeaders($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        $decoded = json_decode($json, true);

        return \is_array($decoded) ? $decoded : [];
    }
}

