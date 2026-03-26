<?php

namespace Tests\Feature;

use App\Models\Flower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests for Update and Delete idempotency.
 * Verifies that PUT and DELETE operations with X-Idempotency-Key
 * return cached responses on retry, and that normal requests without
 * idempotency keys continue to work.
 */
class IdempotencyUpdateDeleteTest extends TestCase
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
     * Test that Flower update is idempotent with X-Idempotency-Key.
     */
    public function test_flower_update_idempotent(): void
    {
        $flower = Flower::create([
            'name' => 'Original',
            'name_en' => 'Original',
            'category' => 'test',
            'price' => 100,
            'original_price' => 120,
            'image' => 'test.jpg',
            'description' => 'Original',
            'meaning' => 'Original',
            'care' => 'Original',
            'stock' => 10,
            'featured' => false,
        ]);

        $idempotencyKey = 'update-flower-' . uniqid();

        $response1 = $this->adminRequest()->putJson("/api/flowers/{$flower->id}", [
            'name' => 'Updated',
            'name_en' => 'Updated',
            'category' => 'test',
            'price' => 150,
            'original_price' => 180,
            'image' => 'updated.jpg',
            'description' => 'Updated',
            'meaning' => 'Updated',
            'care' => 'Updated',
            'stock' => 20,
            'featured' => true,
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response1->assertStatus(200);
        $this->assertEquals('Updated', $response1->json('data.name'));

        // Retry returns cached (old) response — name should still be 'Updated'
        $response2 = $this->adminRequest()->putJson("/api/flowers/{$flower->id}", [
            'name' => 'Should Not Apply',
            'name_en' => 'Should Not Apply',
            'category' => 'test',
            'price' => 999,
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);
        $flower->refresh();
        $this->assertEquals('Updated', $flower->name); // not changed to 'Should Not Apply'
    }

    /**
     * Test that Flower delete is idempotent with X-Idempotency-Key.
     */
    public function test_flower_delete_idempotent(): void
    {
        $flower = Flower::create([
            'name' => 'To Be Deleted',
            'name_en' => 'To Be Deleted',
            'category' => 'test',
            'price' => 100,
            'original_price' => 120,
            'image' => 'test.jpg',
            'description' => 'Test',
            'meaning' => 'Test',
            'care' => 'Test',
            'stock' => 10,
            'featured' => false,
        ]);

        $flowerId = $flower->id;
        $idempotencyKey = 'delete-flower-' . uniqid();

        $response1 = $this->adminRequest()->deleteJson("/api/flowers/{$flowerId}", [], [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        $response1->assertStatus(200);
        $this->assertNull(Flower::find($flowerId));

        // Retry should return cached (already deleted) response
        $response2 = $this->adminRequest()->deleteJson("/api/flowers/{$flowerId}", [], [
            'X-Idempotency-Key' => $idempotencyKey,
        ]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);
    }

    /**
     * Test that request without idempotency key still works normally (no locking).
     */
    public function test_request_without_idempotency_key_works_normally(): void
    {
        $response = $this->adminRequest()->postJson('/api/flowers', [
            'name' => 'No Idempotency Key',
            'name_en' => 'No Idempotency Key',
            'category' => 'test',
            'price' => 50,
            'original_price' => 60,
            'image' => 'test.jpg',
            'description' => 'Test',
            'meaning' => 'Test',
            'care' => 'Test',
            'stock' => 10,
            'featured' => false,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('flowers', ['name' => 'No Idempotency Key']);
    }
}
