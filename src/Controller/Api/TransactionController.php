<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\FinanceTransaction;
use App\Repository\FinanceTransactionRepository;
use App\Request\Finance\UpsertTransactionRequest;
use App\Service\Finance\FinanceDomainException;
use App\Service\Finance\TransactionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/transactions')]
final class TransactionController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly FinanceTransactionRepository $transactionRepository,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_transaction_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $filters = [
            'search' => $this->normalizeQueryString($request->query->get('search')),
            'type' => $this->normalizeQueryString($request->query->get('type')),
            'payment_type' => $this->normalizeQueryString($request->query->get('payment_type')),
            'category' => $this->normalizeQueryString($request->query->get('category')),
            'category_id' => $this->normalizeQueryInt($request->query->get('category_id')),
            'wallet_id' => $this->normalizeQueryInt($request->query->get('wallet_id')),
            'bank_account_id' => $this->normalizeQueryInt($request->query->get('bank_account_id')),
            'start_date' => $this->normalizeQueryDate($request->query->get('start_date')),
            'end_date' => $this->normalizeQueryDate($request->query->get('end_date'), true),
        ];

        $page = $this->normalizeQueryInt($request->query->get('page')) ?? 1;
        $perPage = min(100, max(1, $this->normalizeQueryInt($request->query->get('per_page')) ?? 20));

        $result = $this->transactionService->list($user, $filters, $page, $perPage);

        return $this->successResponse('Transactions fetched successfully.', $result);
    }

    #[Route('', name: 'api_transaction_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        try {
            $payload = $this->getJsonPayload($request);
            $dto = UpsertTransactionRequest::fromArray($payload);
            $validationResponse = $this->validateRequest($dto, $this->validator);
            if (null !== $validationResponse) {
                return $validationResponse;
            }

            $transaction = $this->transactionService->create($user, $payload);

            return $this->successResponse('Transaction created successfully.', [
                'transaction' => $this->transactionService->serializeTransaction($transaction),
            ]);
        } catch (FinanceDomainException $exception) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id}', name: 'api_transaction_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $transaction = $this->transactionRepository->findOneByIdAndUser($id, $user);
        if (!$transaction instanceof FinanceTransaction) {
            return $this->errorResponse('Transaction not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse('Transaction fetched successfully.', [
            'transaction' => $this->transactionService->serializeTransaction($transaction),
        ]);
    }

    #[Route('/{id}', name: 'api_transaction_update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $transaction = $this->transactionRepository->findOneByIdAndUser($id, $user);
        if (!$transaction instanceof FinanceTransaction) {
            return $this->errorResponse('Transaction not found.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $this->getJsonPayload($request);
            $dto = UpsertTransactionRequest::fromArray($payload);
            $validationResponse = $this->validateRequest($dto, $this->validator);
            if (null !== $validationResponse) {
                return $validationResponse;
            }

            $transaction = $this->transactionService->update($transaction, $user, $payload);

            return $this->successResponse('Transaction updated successfully.', [
                'transaction' => $this->transactionService->serializeTransaction($transaction),
            ]);
        } catch (FinanceDomainException $exception) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id}', name: 'api_transaction_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $transaction = $this->transactionRepository->findOneByIdAndUser($id, $user);
        if (!$transaction instanceof FinanceTransaction) {
            return $this->errorResponse('Transaction not found.', Response::HTTP_NOT_FOUND);
        }

        $this->transactionService->delete($transaction);

        return $this->successResponse('Transaction deleted successfully.');
    }

    #[Route('/meta', name: 'api_transaction_meta', methods: ['GET'])]
    public function meta(): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        return $this->successResponse('Transaction meta fetched successfully.', $this->transactionService->buildMeta($user));
    }
}
