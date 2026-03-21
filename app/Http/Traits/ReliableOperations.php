<?php

namespace App\Http\Traits;

use App\Services\DistributedLockService;
use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Trait to add reliability features to controllers:
 * - Idempotency handling for POST requests
 * - Distributed locking for concurrent access protection
 * - Transaction wrapping for data integrity
 */
trait ReliableOperations
{
    protected IdempotencyService $idempotencyService;
    protected DistributedLockService $lockService;

    /**
     * Get the idempotency service instance.
     */
    protected function idempotency(): IdempotencyService
    {
        if (!isset($this->idempotencyService)) {
            $this->idempotencyService = new IdempotencyService();
        }
        return $this->idempotencyService;
    }

    /**
     * Get the distributed lock service instance.
     */
    protected function lock(): DistributedLockService
    {
        if (!isset($this->lockService)) {
            $this->lockService = new DistributedLockService();
        }
        return $this->lockService;
    }

    /**
     * Execute an operation within a database transaction.
     *
     * @param callable $callback The operation to perform
     * @param int $attempts Number of retry attempts on deadlock
     * @return mixed
     */
    protected function withTransaction(callable $callback, int $attempts = 3): mixed
    {
        return DB::transaction($callback, $attempts);
    }

    /**
     * Execute an operation with both a distributed lock and transaction.
     *
     * @param string $resource The resource to lock
     * @param callable $callback The operation to perform
     * @param int|null $waitTime Lock wait time
     * @param int|null $lifetime Lock lifetime
     * @return mixed
     */
    protected function withLockAndTransaction(string $resource, callable $callback, ?int $waitTime = null, ?int $lifetime = null): mixed
    {
        return $this->lock()->withLock(
            $resource,
            fn() => $this->withTransaction($callback),
            $waitTime,
            $lifetime
        );
    }

    /**
     * Check for idempotency key and return existing response if duplicate.
     * Returns null if new request (caller should proceed and call markProcessed).
     *
     * @param Request $request
     * @return JsonResponse|null Existing response for duplicate, null for new
     */
    protected function checkIdempotency(Request $request): ?JsonResponse
    {
        $idempotencyKey = $request->header('X-Idempotency-Key');

        if (empty($idempotencyKey)) {
            return null;
        }

        $existingResponse = $this->idempotency()->checkAndMark($idempotencyKey);

        if ($existingResponse !== null) {
            Log::info("Idempotency: Returning cached response", ['key' => $idempotencyKey]);
            return response()->json($existingResponse, 200);
        }

        return null;
    }

    /**
     * Mark an idempotency key as processed with the given response.
     *
     * @param Request $request
     * @param JsonResponse $response
     * @return void
     */
    protected function markIdempotencyProcessed(Request $request, JsonResponse $response): void
    {
        $idempotencyKey = $request->header('X-Idempotency-Key');

        if (!empty($idempotencyKey)) {
            $this->idempotency()->markProcessed(
                $idempotencyKey,
                $response->getData(true)
            );
        }
    }

    /**
     * Get resource lock identifier for a model.
     *
     * @param string $modelClass
     * @param int|string $id
     * @return string
     */
    protected function getResourceLockKey(string $modelClass, int|string $id): string
    {
        $shortName = (new \ReflectionClass($modelClass))->getShortName();
        return strtolower($shortName) . ":{$id}";
    }
}
