<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * IdempotencyReliabilityTest — Aggregate test manifest.
 *
 * This class has been refactored. The original 651-line monolithic test
 * has been split into focused test classes under tests/Feature/Idempotency/:
 *
 *   BaseReliabilityTest     — Shared setup (admin user, authenticated request helper)
 *   LockFirstTest           — Lock-first idempotency (409 on concurrent lock, cached retry)
 *   StoreOperationsTest     — Store operation idempotency (Flower/Category/Knowledge/SiteSetting)
 *   DistributedLockTest     — DistributedLockService integration tests
 *   UpdateDeleteTest        — Update/delete idempotency
 *   UploadTest              — Upload idempotency
 *   RegisterTest            — Registration idempotency
 *
 * All test methods from the original file are preserved in their respective classes.
 * This manifest class intentionally contains no tests — it exists for backward
 * compatibility with any external references to this class name.
 *
 * To run all idempotency reliability tests:
 *   php artisan test --filter=Idempotency
 *
 * To run a specific split test class:
 *   php artisan test --filter=LockFirstTest
 *   php artisan test --filter=StoreOperationsTest
 *   php artisan test --filter=DistributedLockTest
 *   php artisan test --filter=UpdateDeleteTest
 *   php artisan test --filter=UploadTest
 *   php artisan test --filter=RegisterTest
 */
class IdempotencyReliabilityTest extends TestCase
{
    // All test methods have been moved to tests/Feature/Idempotency/ classes.
    // See docblock above for the mapping and run instructions.
}
