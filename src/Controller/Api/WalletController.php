<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Wallet;
use App\Repository\WalletRepository;
use App\Request\Finance\UpsertWalletRequest;
use App\Service\Finance\FinanceDomainException;
use App\Service\Finance\MoneyService;
use App\Service\Finance\WalletService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/wallets')]
final class WalletController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly WalletRepository $walletRepository,
        private readonly WalletService $walletService,
        private readonly MoneyService $moneyService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_wallet_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $wallets = array_map(
            fn (Wallet $wallet): array => $this->serializeWallet($wallet),
            $this->walletService->list($user)
        );

        return $this->successResponse('Wallets fetched successfully.', [
            'wallets' => $wallets,
        ]);
    }

    #[Route('', name: 'api_wallet_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        try {
            $payload = $this->getJsonPayload($request);
            $dto = UpsertWalletRequest::fromArray($payload);
            $validationResponse = $this->validateRequest($dto, $this->validator);
            if (null !== $validationResponse) {
                return $validationResponse;
            }

            $wallet = $this->walletService->create($user, [
                'name' => $dto->name,
                'starting_balance' => $dto->starting_balance,
                'color_value' => $dto->color_value,
                'icon_code_point' => $dto->icon_code_point,
            ]);

            return $this->successResponse('Wallet created successfully.', [
                'wallet' => $this->serializeWallet($wallet),
            ]);
        } catch (FinanceDomainException $exception) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id}', name: 'api_wallet_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $wallet = $this->walletRepository->findOneByIdAndUser($id, $user);
        if (!$wallet instanceof Wallet) {
            return $this->errorResponse('Wallet not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse('Wallet fetched successfully.', [
            'wallet' => $this->serializeWallet($wallet),
        ]);
    }

    #[Route('/{id}', name: 'api_wallet_update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $wallet = $this->walletRepository->findOneByIdAndUser($id, $user);
        if (!$wallet instanceof Wallet) {
            return $this->errorResponse('Wallet not found.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $this->getJsonPayload($request);
            $updateData = [];

            if (\array_key_exists('name', $payload)) {
                $name = trim((string) ($payload['name'] ?? ''));
                if ('' === $name) {
                    return $this->errorResponse('name cannot be empty.', Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                if (mb_strlen($name) > 120) {
                    return $this->errorResponse('name must be at most 120 characters.', Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $updateData['name'] = $name;
            }

            if (\array_key_exists('starting_balance', $payload)) {
                $updateData['starting_balance'] = $payload['starting_balance'];
            }

            if (\array_key_exists('color_value', $payload)) {
                $updateData['color_value'] = \is_scalar($payload['color_value']) ? trim((string) $payload['color_value']) : null;
            }

            if (\array_key_exists('icon_code_point', $payload)) {
                $updateData['icon_code_point'] = \is_int($payload['icon_code_point']) ? $payload['icon_code_point'] : null;
            }

            $wallet = $this->walletService->update($wallet, $updateData, $user);

            return $this->successResponse('Wallet updated successfully.', [
                'wallet' => $this->serializeWallet($wallet),
            ]);
        } catch (FinanceDomainException $exception) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id}', name: 'api_wallet_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $wallet = $this->walletRepository->findOneByIdAndUser($id, $user);
        if (!$wallet instanceof Wallet) {
            return $this->errorResponse('Wallet not found.', Response::HTTP_NOT_FOUND);
        }

        try {
            $this->walletService->delete($wallet);

            return $this->successResponse('Wallet deleted successfully.');
        } catch (FinanceDomainException $exception) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        }
    }

    private function serializeWallet(Wallet $wallet): array
    {
        return [
            'id' => $wallet->getId(),
            'name' => $wallet->getName(),
            'starting_balance' => $this->moneyService->toFloat($wallet->getStartingBalance()),
            'current_balance' => $this->moneyService->toFloat($wallet->getCurrentBalance()),
            'color_value' => $wallet->getColorValue(),
            'icon_code_point' => $wallet->getIconCodePoint(),
            'created_at' => $wallet->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $wallet->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}

