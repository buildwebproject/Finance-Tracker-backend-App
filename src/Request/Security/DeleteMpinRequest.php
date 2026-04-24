<?php

declare(strict_types=1);

namespace App\Request\Security;

use Symfony\Component\Validator\Constraints as Assert;

class DeleteMpinRequest
{
    #[Assert\NotBlank(message: 'current_mpin is required.')]
    #[Assert\Regex(pattern: '/^\d{4}$/', message: 'current_mpin must be exactly 4 digits.')]
    public ?string $current_mpin = null;

    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->current_mpin = self::normalizeMpin($payload['current_mpin'] ?? null);

        return $request;
    }

    private static function normalizeMpin(mixed $value): ?string
    {
        if (!\is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
