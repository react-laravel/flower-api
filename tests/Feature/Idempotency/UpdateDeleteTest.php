<?php

namespace Tests\Feature\Idempotency;

use App\Models\Flower;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests for update and delete idempotency:
 * - Flower update with X-Idempotency-Key returns cached response on retry
 * - Flower delete with X-Idempotency-Key returns cached (already-deleted) response
 */
class UpdateDeleteTest extends BaseReliabilityTest
{
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
}
