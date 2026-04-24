<?php

declare(strict_types=1);

namespace App\Service\Security;

final class RateLimitExceededException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $retryAfterSeconds,
    ) {
        parent::__construct($message);
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
