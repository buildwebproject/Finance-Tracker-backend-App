<?php

declare(strict_types=1);

namespace App\Service\Finance;

final class FinanceDomainException extends \RuntimeException
{
    public function __construct(string $message, private readonly int $statusCode = 422)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

