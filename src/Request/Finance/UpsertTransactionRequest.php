<?php

declare(strict_types=1);

namespace App\Request\Finance;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UpsertTransactionRequest
{
    #[Assert\NotBlank(message: 'amount is required.')]
    #[Assert\Type(type: 'numeric', message: 'amount must be numeric.')]
    public mixed $amount = null;

    #[Assert\NotNull(message: 'is_income is required.')]
    #[Assert\Type(type: 'bool', message: 'is_income must be a boolean.')]
    public mixed $is_income = null;

    #[Assert\NotBlank(message: 'payment_type is required.')]
    #[Assert\Choice(choices: ['cash', 'online'], message: 'payment_type must be cash or online.')]
    public ?string $payment_type = null;

    #[Assert\Length(max: 120, maxMessage: 'category must be at most 120 characters.')]
    public ?string $category = null;

    #[Assert\Type(type: 'integer', message: 'category_id must be an integer.')]
    #[Assert\Positive(message: 'category_id must be greater than 0.')]
    public mixed $category_id = null;

    #[Assert\Type(type: 'integer', message: 'wallet_id must be an integer.')]
    #[Assert\Positive(message: 'wallet_id must be greater than 0.')]
    public mixed $wallet_id = null;

    #[Assert\Type(type: 'integer', message: 'bank_account_id must be an integer.')]
    #[Assert\Positive(message: 'bank_account_id must be greater than 0.')]
    public mixed $bank_account_id = null;

    #[Assert\Length(max: 1000, maxMessage: 'note must be at most 1000 characters.')]
    public ?string $note = null;

    #[Assert\NotBlank(message: 'occurred_at is required.')]
    public ?string $occurred_at = null;

    #[Assert\Type(type: 'bool', message: 'is_system_generated must be a boolean.')]
    public mixed $is_system_generated = null;

    #[Assert\Length(max: 80, maxMessage: 'source_type must be at most 80 characters.')]
    public ?string $source_type = null;

    #[Assert\Length(max: 120, maxMessage: 'source_id must be at most 120 characters.')]
    public ?string $source_id = null;

    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->amount = $payload['amount'] ?? null;
        $request->is_income = $payload['is_income'] ?? null;
        $request->payment_type = self::normalizeString($payload['payment_type'] ?? null);
        $request->category = self::normalizeString($payload['category'] ?? null);
        $request->category_id = self::normalizeNullableInt($payload['category_id'] ?? null);
        $request->wallet_id = self::normalizeNullableInt($payload['wallet_id'] ?? null);
        $request->bank_account_id = self::normalizeNullableInt($payload['bank_account_id'] ?? null);
        $request->note = self::normalizeString($payload['note'] ?? null);
        $request->occurred_at = self::normalizeString($payload['occurred_at'] ?? null);
        $request->is_system_generated = $payload['is_system_generated'] ?? null;
        $request->source_type = self::normalizeString($payload['source_type'] ?? null);
        $request->source_id = self::normalizeString($payload['source_id'] ?? null);

        return $request;
    }

    #[Assert\Callback]
    public function validateSourceSelection(ExecutionContextInterface $context): void
    {
        if (null === $this->category_id && null === $this->category) {
            $context->buildViolation('category or category_id is required.')
                ->atPath('category')
                ->addViolation();
        }

        if ('cash' === $this->payment_type && null === $this->wallet_id) {
            $context->buildViolation('wallet_id is required when payment_type is cash.')
                ->atPath('wallet_id')
                ->addViolation();
        }

        if ('online' === $this->payment_type && null === $this->bank_account_id) {
            $context->buildViolation('bank_account_id is required when payment_type is online.')
                ->atPath('bank_account_id')
                ->addViolation();
        }

        if ('cash' === $this->payment_type && null !== $this->bank_account_id) {
            $context->buildViolation('bank_account_id must be null when payment_type is cash.')
                ->atPath('bank_account_id')
                ->addViolation();
        }

        if ('online' === $this->payment_type && null !== $this->wallet_id) {
            $context->buildViolation('wallet_id must be null when payment_type is online.')
                ->atPath('wallet_id')
                ->addViolation();
        }
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
