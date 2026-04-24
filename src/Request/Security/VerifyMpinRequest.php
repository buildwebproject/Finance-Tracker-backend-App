<?php

declare(strict_types=1);

namespace App\Request\Security;

use Symfony\Component\Validator\Constraints as Assert;

class VerifyMpinRequest
{
    #[Assert\NotBlank(message: 'mpin is required.')]
    #[Assert\Regex(pattern: '/^\d{4}$/', message: 'mpin must be exactly 4 digits.')]
    public ?string $mpin = null;

    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->mpin = self::normalizeMpin($payload['mpin'] ?? null);

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
