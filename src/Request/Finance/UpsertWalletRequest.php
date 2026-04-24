<?php

declare(strict_types=1);

namespace App\Request\Finance;

use Symfony\Component\Validator\Constraints as Assert;

class UpsertWalletRequest
{
    #[Assert\NotBlank(message: 'name is required.')]
    #[Assert\Length(max: 120, maxMessage: 'name must be at most 120 characters.')]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'starting_balance is required.')]
    #[Assert\Type(type: 'numeric', message: 'starting_balance must be numeric.')]
    public mixed $starting_balance = null;

    #[Assert\Length(max: 32, maxMessage: 'color_value must be at most 32 characters.')]
    public ?string $color_value = null;

    #[Assert\Type(type: 'integer', message: 'icon_code_point must be an integer.')]
    #[Assert\PositiveOrZero(message: 'icon_code_point must be positive or zero.')]
    public mixed $icon_code_point = null;

    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->name = self::normalizeString($payload['name'] ?? null);
        $request->starting_balance = $payload['starting_balance'] ?? null;
        $request->color_value = self::normalizeString($payload['color_value'] ?? null);
        $request->icon_code_point = self::normalizeNullableInt($payload['icon_code_point'] ?? null);

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

    private static function normalizeNullableInt(mixed $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (\is_int($value)) {
            return $value;
        }

        if (\is_scalar($value) && is_numeric((string) $value)) {
            return (int) $value;
        }

        return null;
    }
}

