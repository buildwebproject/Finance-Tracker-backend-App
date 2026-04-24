<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait ApiResponseTrait
{
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

    private function validateRequest(object $dto, ValidatorInterface $validator): ?JsonResponse
    {
        $violations = $validator->validate($dto);
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

    private function normalizeQueryString(mixed $value): ?string
    {
        if (!\is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }

    private function normalizeQueryInt(mixed $value): ?int
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

    private function normalizeQueryDate(mixed $value, bool $endOfDay = false): ?\DateTimeImmutable
    {
        $stringValue = $this->normalizeQueryString($value);
        if (null === $stringValue) {
            return null;
        }

        try {
            $date = new \DateTimeImmutable($stringValue);

            return $endOfDay ? $date->setTime(23, 59, 59) : $date->setTime(0, 0, 0);
        } catch (\Throwable) {
            return null;
        }
    }
}

