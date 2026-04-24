<?php

declare(strict_types=1);

namespace App\Service\Finance;

final class MoneyService
{
    public function normalize(mixed $value): string
    {
        if (\is_int($value) || \is_float($value) || \is_string($value)) {
            $stringValue = trim((string) $value);
            if ('' !== $stringValue && is_numeric($stringValue)) {
                return number_format((float) $stringValue, 2, '.', '');
            }
        }

        throw new \InvalidArgumentException('Amount must be numeric.');
    }

    public function toFloat(string $value): float
    {
        return (float) number_format((float) $value, 2, '.', '');
    }

    public function isGreaterThanZero(string $value): bool
    {
        return $this->toCents($value) > 0;
    }

    public function add(string $left, string $right): string
    {
        return $this->fromCents($this->toCents($left) + $this->toCents($right));
    }

    public function subtract(string $left, string $right): string
    {
        return $this->fromCents($this->toCents($left) - $this->toCents($right));
    }

    public function absolute(string $value): string
    {
        return $this->fromCents(abs($this->toCents($value)));
    }

    private function toCents(string $value): int
    {
        $normalized = number_format((float) $value, 2, '.', '');
        $negative = str_starts_with($normalized, '-');
        $normalized = ltrim($normalized, '-');
        [$whole, $fraction] = explode('.', $normalized);
        $cents = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        return $negative ? -$cents : $cents;
    }

    private function fromCents(int $value): string
    {
        $negative = $value < 0;
        $absolute = abs($value);
        $whole = intdiv($absolute, 100);
        $fraction = $absolute % 100;
        $result = sprintf('%d.%02d', $whole, $fraction);

        return $negative ? '-'.$result : $result;
    }
}

