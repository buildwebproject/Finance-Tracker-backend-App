<?php

declare(strict_types=1);

namespace App\Request\Security;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateMpinRequest
{
    #[Assert\Regex(pattern: '/^\d{4}$/', message: 'current_mpin must be exactly 4 digits.')]
    public ?string $current_mpin = null;

    #[Assert\NotBlank(message: 'new_mpin is required.')]
    #[Assert\Regex(pattern: '/^\d{4}$/', message: 'new_mpin must be exactly 4 digits.')]
    public ?string $new_mpin = null;

    #[Assert\NotBlank(message: 'confirm_mpin is required.')]
    #[Assert\Regex(pattern: '/^\d{4}$/', message: 'confirm_mpin must be exactly 4 digits.')]
    #[Assert\EqualTo(propertyPath: 'new_mpin', message: 'confirm_mpin must match new_mpin.')]
    public ?string $confirm_mpin = null;

    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->current_mpin = self::normalizeMpin($payload['current_mpin'] ?? null);
        $request->new_mpin = self::normalizeMpin($payload['new_mpin'] ?? null);
        $request->confirm_mpin = self::normalizeMpin($payload['confirm_mpin'] ?? null);

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
