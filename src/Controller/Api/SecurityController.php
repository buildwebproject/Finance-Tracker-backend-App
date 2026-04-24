<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Request\Security\DeleteMpinRequest;
use App\Request\Security\SecurityPreferencesRequest;
use App\Request\Security\UpdateMpinRequest;
use App\Request\Security\VerifyMpinRequest;
use App\Service\Security\InvalidCurrentMpinException;
use App\Service\Security\RateLimitExceededException;
use App\Service\Security\SecuritySettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth/security')]
final class SecurityController extends AbstractController
{
    public function __construct(
        private readonly SecuritySettingsService $securitySettingsService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_auth_security_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $settings = $this->securitySettingsService->getOrCreateSettings($user);

        return $this->successResponse('Security settings fetched.', $this->securitySettingsService->buildResponseData($settings));
    }

    #[Route('/preferences', name: 'api_auth_security_preferences_update', methods: ['PUT'])]
    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        try {
            $payload = $this->getJsonPayload($request);
            $dto = SecurityPreferencesRequest::fromArray($payload);
            $validationResponse = $this->validateRequest($dto);
            if (null !== $validationResponse) {
                return $validationResponse;
            }

            $settings = $this->securitySettingsService->updatePreferences(
                $user,
                $dto->getAppLockEnabled(),
                $dto->getBiometricEnabled(),
                $request->getClientIp()
            );

            return $this->successResponse('Security preferences updated successfully.', $this->securitySettingsService->buildResponseData($settings));
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/mpin', name: 'api_auth_security_mpin_upsert', methods: ['POST'])]
    public function upsertMpin(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        try {
            $payload = $this->getJsonPayload($request);
            $dto = UpdateMpinRequest::fromArray($payload);
            $validationResponse = $this->validateRequest($dto);
            if (null !== $validationResponse) {
                return $validationResponse;
            }

            $settings = $this->securitySettingsService->upsertMpin(
                $user,
                $dto->current_mpin,
                $dto->new_mpin ?? '',
                $request->getClientIp()
            );

            return $this->successResponse('MPIN updated successfully.', $this->securitySettingsService->buildResponseData($settings));
        } catch (InvalidCurrentMpinException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (RateLimitExceededException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_TOO_MANY_REQUESTS, [
                'retry_after_seconds' => $exception->getRetryAfterSeconds(),
            ]);
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/mpin', name: 'api_auth_security_mpin_delete', methods: ['DELETE'])]
    public function deleteMpin(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        try {
            $payload = $this->getJsonPayload($request);
            $dto = DeleteMpinRequest::fromArray($payload);
            $validationResponse = $this->validateRequest($dto);
            if (null !== $validationResponse) {
                return $validationResponse;
            }

            $this->securitySettingsService->removeMpin(
                $user,
                $dto->current_mpin ?? '',
                $request->getClientIp()
            );

            return $this->successResponse('MPIN removed successfully.', ['has_mpin' => false]);
        } catch (InvalidCurrentMpinException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (RateLimitExceededException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_TOO_MANY_REQUESTS, [
                'retry_after_seconds' => $exception->getRetryAfterSeconds(),
            ]);
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/mpin/verify', name: 'api_auth_security_mpin_verify', methods: ['POST'])]
    public function verifyMpin(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        try {
            $payload = $this->getJsonPayload($request);
            $dto = VerifyMpinRequest::fromArray($payload);
            $validationResponse = $this->validateRequest($dto);
            if (null !== $validationResponse) {
                return $validationResponse;
            }

            $verified = $this->securitySettingsService->verifyMpin(
                $user,
                $dto->mpin ?? '',
                $request->getClientIp()
            );

            if (!$verified) {
                return $this->errorResponse('Invalid MPIN.', Response::HTTP_UNPROCESSABLE_ENTITY, ['verified' => false]);
            }

            return $this->successResponse('MPIN verified.', ['verified' => true]);
        } catch (RateLimitExceededException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_TOO_MANY_REQUESTS, [
                'retry_after_seconds' => $exception->getRetryAfterSeconds(),
            ]);
        } catch (\InvalidArgumentException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @return User|JsonResponse
     */
    private function getAuthenticatedUser(): User|JsonResponse
    {
        $authenticatedUser = $this->getUser();

        if (!$authenticatedUser instanceof User) {
            return $this->errorResponse('Unauthenticated.', Response::HTTP_UNAUTHORIZED);
        }

        return $authenticatedUser;
    }

    /**
     * @return array<string, mixed>
     */
    private function getJsonPayload(Request $request): array
    {
        $rawBody = trim($request->getContent());
        if ('' === $rawBody) {
            throw new \InvalidArgumentException('JSON request body is required.');
        }

        /** @var mixed $payload */
        $payload = json_decode($rawBody, true);
        if (!\is_array($payload)) {
            throw new \InvalidArgumentException('Invalid JSON payload.');
        }

        return $payload;
    }

    private function validateRequest(object $dto): ?JsonResponse
    {
        $violations = $this->validator->validate($dto);
        if (0 === $violations->count()) {
            return null;
        }

        $errors = [];
        foreach ($violations as $violation) {
            $path = '' !== $violation->getPropertyPath() ? $violation->getPropertyPath() : 'request';
            $errors[$path][] = $violation->getMessage();
        }

        return $this->errorResponse('Validation failed.', Response::HTTP_UNPROCESSABLE_ENTITY, ['errors' => $errors]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function successResponse(string $message, array $data = []): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function errorResponse(string $message, int $statusCode, array $data = []): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}
