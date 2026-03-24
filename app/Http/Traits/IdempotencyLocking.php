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
     * Returns true if lock was acquired, false if another request holds it.
     */
    protected function acquireLock(string $idempotencyKey, int $ttlSeconds = 30): bool
    {
        return $this->idempotencyService->acquireLock($idempotencyKey, $ttlSeconds);
    }

    /**
     * Release the lock for the idempotency key.
     */
    protected function releaseLock(string $idempotencyKey): void
    {
        $this->idempotencyService->releaseLock($idempotencyKey);
    }
}
