<?php

namespace Tests\Feature\Idempotency;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests for registration idempotency:
 * - Duplicate registration with same idempotency key returns cached user
 * - Registration without key works normally
 */
class RegisterTest extends BaseReliabilityTest
{
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
}
