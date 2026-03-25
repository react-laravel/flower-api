<?php

namespace App\Http\Traits;

use App\Services\IdempotencyService;

/**
 * Handles lock acquisition and release for idempotency.
 * Part of the Idempotency trait split to reduce complexity.
 */
trait IdempotencyLocking
{
    protected IdempotencyService $idempotencyService;

    /**
     * Acquire a lock for the idempotency key.
     * Returns the lock token if acquired, false if another request holds it.
     */
    protected function acquireLock(string $idempotencyKey, int $ttlSeconds = 30): string|false
    {
        return $this->idempotencyService->acquireLock($idempotencyKey, $ttlSeconds);
    }

    /**
     * Release the lock for the idempotency key.
     *
     * @param string $idempotencyKey The idempotency key
     * @param string $token The lock token (must match the token used to acquire)
     * @return bool True if released, false if token mismatch
     */
    protected function releaseLock(string $idempotencyKey, string $token): bool
    {
        return $this->idempotencyService->releaseLock($idempotencyKey, $token);
    }
}
