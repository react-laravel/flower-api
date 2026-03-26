<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\IdempotencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests for Register idempotency.
 * Verifies that duplicate registration requests with the same idempotency key
 * return cached responses, and that concurrent locked requests return 409.
 */
class IdempotencyRegisterTest extends TestCase
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

    /**
     * Test that duplicate registration requests with same idempotency key return cached response.
     */
    public function test_register_duplicate_request_returns_cached_response(): void
    {
        $idempotencyKey = 'register-idempotent-' . uniqid();

        // First request - register user
        $response1 = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response1->assertStatus(201);
        $userData1 = $response1->json('data.user');
        $this->assertEquals('test@example.com', $userData1['email']);

        // Second request with same key - should return cached response
        $response2 = $this->postJson('/api/auth/register', [
            'name' => 'Different Name',
            'email' => 'different@example.com',
            'password' => 'different123',
            'password_confirmation' => 'different123',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);
        $userData2 = $response2->json('data.user');

        // Should return the same user (cached)
        $this->assertEquals($userData1['id'], $userData2['id']);
        $this->assertEquals('test@example.com', $userData2['email']);

        // Only one user should exist
        $this->assertEquals(1, User::where('email', 'test@example.com')->count());
    }

    /**
     * Test that register without idempotency key works normally.
     */
    public function test_register_without_idempotency_key_works_normally(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Normal User',
            'email' => 'normal@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'normal@example.com']);
    }

    /**
     * Test that concurrent locked register request returns 409.
     */
    public function test_register_concurrent_locked_request_returns_409(): void
    {
        $idempotencyKey = 'register-concurrent-' . uniqid();
        $idempotencyService = new IdempotencyService();
        $idempotencyService->acquireLock($idempotencyKey, 30);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Concurrent User',
            'email' => 'concurrent@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response->assertStatus(409);

        $idempotencyService->releaseLock($idempotencyKey);
    }
}
