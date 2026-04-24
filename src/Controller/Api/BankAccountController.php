<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\BankAccount;
use App\Repository\BankAccountRepository;
use App\Request\Finance\UpsertBankAccountRequest;
use App\Service\Finance\BankAccountService;
use App\Service\Finance\FinanceDomainException;
use App\Service\Finance\MoneyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/bank-accounts')]
final class BankAccountController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly BankAccountRepository $bankAccountRepository,
        private readonly BankAccountService $bankAccountService,
        private readonly MoneyService $moneyService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_bank_account_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $items = array_map(
            fn (BankAccount $bank): array => $this->serializeBankAccount($bank),
            $this->bankAccountService->list($user)
        );

        return $this->successResponse('Bank accounts fetched successfully.', [
            'bank_accounts' => $items,
        ]);
    }

    #[Route('', name: 'api_bank_account_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        try {
            $payload = $this->getJsonPayload($request);
            $dto = UpsertBankAccountRequest::fromArray($payload);
            $validationResponse = $this->validateRequest($dto, $this->validator);
            if (null !== $validationResponse) {
                return $validationResponse;
            }

            $bankAccount = $this->bankAccountService->create($user, [
                'bank_name' => $dto->bank_name,
                'nickname' => $dto->nickname,
                'starting_balance' => $dto->starting_balance,
                'is_default' => true === $dto->is_default,
            ]);

            return $this->successResponse('Bank account created successfully.', [
                'bank_account' => $this->serializeBankAccount($bankAccount),
            ]);
        } catch (FinanceDomainException $exception) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id}', name: 'api_bank_account_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $bankAccount = $this->bankAccountRepository->findOneByIdAndUser($id, $user);
        if (!$bankAccount instanceof BankAccount) {
            return $this->errorResponse('Bank account not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse('Bank account fetched successfully.', [
            'bank_account' => $this->serializeBankAccount($bankAccount),
        ]);
    }

    #[Route('/{id}', name: 'api_bank_account_update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $bankAccount = $this->bankAccountRepository->findOneByIdAndUser($id, $user);
        if (!$bankAccount instanceof BankAccount) {
            return $this->errorResponse('Bank account not found.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $this->getJsonPayload($request);
            $updateData = [];

            if (\array_key_exists('bank_name', $payload)) {
                $bankName = trim((string) ($payload['bank_name'] ?? ''));
                if ('' === $bankName) {
                    return $this->errorResponse('bank_name cannot be empty.', Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                if (mb_strlen($bankName) > 120) {
                    return $this->errorResponse('bank_name must be at most 120 characters.', Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $updateData['bank_name'] = $bankName;
            }

            if (\array_key_exists('nickname', $payload)) {
                $nickname = null;
                if (\is_scalar($payload['nickname'])) {
                    $nickname = trim((string) $payload['nickname']);
                    if (mb_strlen($nickname) > 120) {
                        return $this->errorResponse('nickname must be at most 120 characters.', Response::HTTP_UNPROCESSABLE_ENTITY);
                    }
                }
                $updateData['nickname'] = $nickname;
            }

            if (\array_key_exists('starting_balance', $payload)) {
                $updateData['starting_balance'] = $payload['starting_balance'];
            }

            if (\array_key_exists('is_default', $payload)) {
                $updateData['is_default'] = true === $payload['is_default'];
            }

            $bankAccount = $this->bankAccountService->update($bankAccount, $updateData, $user);

            return $this->successResponse('Bank account updated successfully.', [
                'bank_account' => $this->serializeBankAccount($bankAccount),
            ]);
        } catch (FinanceDomainException $exception) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id}', name: 'api_bank_account_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $bankAccount = $this->bankAccountRepository->findOneByIdAndUser($id, $user);
        if (!$bankAccount instanceof BankAccount) {
            return $this->errorResponse('Bank account not found.', Response::HTTP_NOT_FOUND);
        }

        try {
            $this->bankAccountService->delete($bankAccount, $user);

            return $this->successResponse('Bank account deleted successfully.');
        } catch (FinanceDomainException $exception) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        }
    }

    #[Route('/{id}/set-default', name: 'api_bank_account_set_default', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function setDefault(int $id): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $bankAccount = $this->bankAccountRepository->findOneByIdAndUser($id, $user);
        if (!$bankAccount instanceof BankAccount) {
            return $this->errorResponse('Bank account not found.', Response::HTTP_NOT_FOUND);
        }

        $bankAccount = $this->bankAccountService->setDefault($bankAccount, $user);

        return $this->successResponse('Default bank account updated successfully.', [
            'bank_account' => $this->serializeBankAccount($bankAccount),
        ]);
    }

    private function serializeBankAccount(BankAccount $bankAccount): array
    {
        return [
            'id' => $bankAccount->getId(),
            'bank_name' => $bankAccount->getBankName(),
            'nickname' => $bankAccount->getNickname(),
            'starting_balance' => $this->moneyService->toFloat($bankAccount->getStartingBalance()),
            'current_balance' => $this->moneyService->toFloat($bankAccount->getCurrentBalance()),
            'is_default' => $bankAccount->isDefault(),
            'created_at' => $bankAccount->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $bankAccount->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}

