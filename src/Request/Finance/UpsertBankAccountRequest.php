<?php

declare(strict_types=1);

namespace App\Request\Finance;

use Symfony\Component\Validator\Constraints as Assert;

class UpsertBankAccountRequest
{
    #[Assert\NotBlank(message: 'bank_name is required.')]
    #[Assert\Length(max: 120, maxMessage: 'bank_name must be at most 120 characters.')]
    public ?string $bank_name = null;

    #[Assert\Length(max: 120, maxMessage: 'nickname must be at most 120 characters.')]
    public ?string $nickname = null;

    #[Assert\NotBlank(message: 'starting_balance is required.')]
    #[Assert\Type(type: 'numeric', message: 'starting_balance must be numeric.')]
    public mixed $starting_balance = null;

    #[Assert\Type(type: 'bool', message: 'is_default must be a boolean.')]
    public mixed $is_default = null;

    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->bank_name = self::normalizeString($payload['bank_name'] ?? null);
        $request->nickname = self::normalizeString($payload['nickname'] ?? null);
        $request->starting_balance = $payload['starting_balance'] ?? null;
        $request->is_default = $payload['is_default'] ?? null;

        return $request;
    }

    private static function normalizeString(mixed $value): ?string
    {
        if (!\is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}

