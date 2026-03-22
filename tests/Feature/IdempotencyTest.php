<?php

namespace Tests\Feature;

use App\Models\Flower;
use App\Models\User;
use App\Services\IdempotencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        // Use array cache for testing
        Cache::flush();

        // Create admin user for authentication
        $this->adminUser = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);
    }

    /**
     * Test that duplicate requests with same idempotency key return cached response
     */
    public function test_duplicate_request_returns_cached_response(): void
    {
        Sanctum::actingAs($this->adminUser);

        $idempotencyKey = 'test-key-' . uniqid();

        // First request - create a flower
        $response1 = $this->postJson('/api/flowers', [
            'name' => 'Rose',
            'name_en' => 'Rose',
            'category' => 'romantic',
            'price' => 100,
            'original_price' => 120,
            'image' => 'rose.jpg',
            'description' => 'A beautiful red rose',
            'meaning' => 'Love',
            'care' => 'Water daily',
            'stock' => 50,
            'featured' => true,
            'holiday' => 'valentine',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response1->assertStatus(201);
        $flowerData = $response1->json('data');
        $this->assertEquals('Rose', $flowerData['name']);

        // Verify flower was created
        $this->assertDatabaseHas('flowers', ['name' => 'Rose']);

        // Second request with same idempotency key - should return cached response
        $response2 = $this->postJson('/api/flowers', [
            'name' => 'Tulip',
            'name_en' => 'Tulip',
            'category' => 'spring',
            'price' => 80,
            'original_price' => 90,
            'image' => 'tulip.jpg',
            'description' => 'A spring flower',
            'meaning' => 'Perfect love',
            'care' => 'Water regularly',
            'stock' => 30,
            'featured' => false,
            'holiday' => 'spring',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);

        // Verify only ONE flower was created (not two)
        $this->assertEquals(1, Flower::where('name', 'Rose')->count());
        $this->assertEquals(0, Flower::where('name', 'Tulip')->count());
    }

    /**
     * Test that request without idempotency key works normally
     */
    public function test_request_without_idempotency_key_works_normally(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/flowers', [
            'name' => 'Daisy',
            'name_en' => 'Daisy',
            'category' => 'nature',
            'price' => 50,
            'original_price' => 60,
            'image' => 'daisy.jpg',
            'description' => 'A simple daisy',
            'meaning' => 'Innocence',
            'care' => 'Easy to care',
            'stock' => 100,
            'featured' => false,
            'holiday' => 'none',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('flowers', ['name' => 'Daisy']);
    }

    /**
     * Test that concurrent locked requests get 409 error
     */
    public function test_concurrent_locked_requests_get_409(): void
    {
        Sanctum::actingAs($this->adminUser);

        $idempotencyKey = 'concurrent-key-' . uniqid();

        // Simulate a lock being held by setting it directly
        $idempotencyService = new IdempotencyService();
        $idempotencyService->acquireLock($idempotencyKey, 30);

        // Request should get 409 since lock is held
        $response = $this->postJson('/api/flowers', [
            'name' => 'Orchid',
            'name_en' => 'Orchid',
            'category' => 'exotic',
            'price' => 150,
            'original_price' => 180,
            'image' => 'orchid.jpg',
            'description' => 'An exotic flower',
            'meaning' => 'Luxury',
            'care' => 'Special care needed',
            'stock' => 10,
            'featured' => true,
            'holiday' => 'none',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response->assertStatus(409);
    }

    /**
     * Test flower update with idempotency
     */
    public function test_flower_update_with_idempotency_key(): void
    {
        Sanctum::actingAs($this->adminUser);

        $flower = Flower::create([
            'name' => 'Original Rose',
            'name_en' => 'Original Rose',
            'category' => 'romantic',
            'price' => 100,
            'original_price' => 120,
            'image' => 'rose.jpg',
            'description' => 'Original description',
            'meaning' => 'Love',
            'care' => 'Water daily',
            'stock' => 50,
            'featured' => true,
            'holiday' => 'valentine',
        ]);

        $idempotencyKey = 'update-key-' . uniqid();

        // First update request
        $response1 = $this->putJson("/api/flowers/{$flower->id}", [
            'name' => 'Updated Rose',
            'name_en' => 'Updated Rose',
            'category' => 'romantic',
            'price' => 110,
            'original_price' => 130,
            'image' => 'updated-rose.jpg',
            'description' => 'Updated description',
            'meaning' => 'Deep Love',
            'care' => 'Water twice daily',
            'stock' => 45,
            'featured' => true,
            'holiday' => 'valentine',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response1->assertStatus(200);
        $this->assertEquals('Updated Rose', $response1->json('data.name'));

        // Second request with same key - should return cached response
        $response2 = $this->putJson("/api/flowers/{$flower->id}", [
            'name' => 'Different Name',
            'name_en' => 'Different Name',
            'category' => 'romantic',
            'price' => 200,
            'original_price' => 250,
            'image' => 'different.jpg',
            'description' => 'Different description',
            'meaning' => 'Different meaning',
            'care' => 'Different care',
            'stock' => 100,
            'featured' => false,
            'holiday' => 'none',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);

        // Verify flower was NOT updated with second request's data
        $flower->refresh();
        $this->assertEquals('Updated Rose', $flower->name);
        $this->assertEquals(110, $flower->price);
    }

    /**
     * Test site settings update with idempotency and distributed lock
     */
    public function test_site_setting_update_with_idempotency(): void
    {
        Sanctum::actingAs($this->adminUser);

        $idempotencyKey = 'setting-key-' . uniqid();

        // First request - update setting
        $response1 = $this->putJson('/api/site-settings', [
            'key' => 'site_name',
            'value' => 'Flower Shop',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response1->assertStatus(200);

        // Second request with same key - should return cached response
        $response2 = $this->putJson('/api/site-settings', [
            'key' => 'site_name',
            'value' => 'Different Name',
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);

        // Verify the setting was NOT changed to 'Different Name'
        $this->assertEquals('Flower Shop', \App\Models\SiteSetting::getValue('site_name'));
    }

    /**
     * Test batch settings update with idempotency and distributed lock
     */
    public function test_batch_settings_update_with_idempotency(): void
    {
        Sanctum::actingAs($this->adminUser);

        $idempotencyKey = 'batch-setting-key-' . uniqid();

        // First request - batch update
        $response1 = $this->putJson('/api/site-settings/batch', [
            'settings' => [
                'site_name' => 'My Flower Shop',
                'contact_email' => 'test@example.com',
            ],
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response1->assertStatus(200);

        // Second request with same key - should return cached response
        $response2 = $this->putJson('/api/site-settings/batch', [
            'settings' => [
                'site_name' => 'Different Shop',
                'contact_email' => 'other@example.com',
            ],
        ], ['X-Idempotency-Key' => $idempotencyKey]);

        $response2->assertStatus(200);
        $response2->assertJson(['idempotent' => true]);

        // Verify settings were NOT changed
        $this->assertEquals('My Flower Shop', \App\Models\SiteSetting::getValue('site_name'));
        $this->assertEquals('test@example.com', \App\Models\SiteSetting::getValue('contact_email'));
    }
}