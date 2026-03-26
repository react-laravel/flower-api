<?php

namespace Tests\Feature\Idempotency;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Base class for idempotency reliability tests.
 * Provides common admin user setup and authenticated request helpers.
 */
abstract class BaseReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->adminUser = User::factory()->create(['is_admin' => true]);
        $this->adminToken = $this->adminUser->createToken('admin')->plainTextToken;
    }

    /**
     * Helper to make authenticated requests with admin Bearer token.
     */
    protected function adminRequest(): static
    {
        return $this->withHeader('Authorization', "Bearer {$this->adminToken}");
    }
}
