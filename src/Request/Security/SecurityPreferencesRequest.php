<?php

declare(strict_types=1);

namespace App\Request\Security;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SecurityPreferencesRequest
{
    #[Assert\Type(type: 'bool', message: 'app_lock_enabled must be a boolean.')]
    public mixed $app_lock_enabled = null;

    #[Assert\Type(type: 'bool', message: 'biometric_enabled must be a boolean.')]
    public mixed $biometric_enabled = null;

    public static function fromArray(array $payload): self
    {
        $request = new self();
        $request->app_lock_enabled = $payload['app_lock_enabled'] ?? null;
        $request->biometric_enabled = $payload['biometric_enabled'] ?? null;

        return $request;
    }

    #[Assert\Callback]
    public function validateAtLeastOneField(ExecutionContextInterface $context): void
    {
        if (null === $this->app_lock_enabled && null === $this->biometric_enabled) {
            $context->buildViolation('At least one of app_lock_enabled or biometric_enabled is required.')
                ->atPath('app_lock_enabled')
                ->addViolation();
        }
    }

    public function getAppLockEnabled(): ?bool
    {
        return \is_bool($this->app_lock_enabled) ? $this->app_lock_enabled : null;
    }

    public function getBiometricEnabled(): ?bool
    {
        return \is_bool($this->biometric_enabled) ? $this->biometric_enabled : null;
    }
}
