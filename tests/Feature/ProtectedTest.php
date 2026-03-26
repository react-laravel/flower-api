<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtectedTest extends TestCase
{
    use RefreshDatabase;

    // ─── Auth routes that require sanctum ───────────────────────────────────

    public function test_auth_user_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/user');
        $response->assertStatus(401);
    }

    public function test_auth_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');
        $response->assertStatus(401);
    }

    public function test_auth_is_admin_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/is-admin');
        $response->assertStatus(401);
    }

    // ─── Admin routes that require auth:sanctum + admin middleware ───────────

    public function test_create_flower_requires_authentication(): void
    {
        $response = $this->postJson('/api/flowers', [
            'name' => 'Test Flower',
            'name_en' => 'Test Flower EN',
            'category' => 'rose',
            'price' => 100,
            'image' => 'http://example.com/flower.jpg',
            'description' => 'A test flower',
            'meaning' => 'Love',
            'care' => 'Water daily',
            'stock' => 10,
        ]);

        $response->assertStatus(401);
    }

    public function test_update_flower_requires_authentication(): void
    {
        $response = $this->putJson('/api/flowers/1', [
            'name' => 'Updated Flower',
        ]);

        $response->assertStatus(401);
    }

    public function test_delete_flower_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/flowers/1');

        $response->assertStatus(401);
    }

    public function test_create_category_requires_authentication(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'icon' => '🌺',
            'description' => 'A test category',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_category_requires_authentication(): void
    {
        $response = $this->putJson('/api/categories/1', [
            'name' => 'Updated Category',
        ]);

        $response->assertStatus(401);
    }

    public function test_delete_category_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/categories/1');

        $response->assertStatus(401);
    }

    public function test_create_knowledge_requires_authentication(): void
    {
        $response = $this->postJson('/api/knowledge', [
            'question' => 'Test question?',
            'answer' => 'Test answer',
            'category' => 'test',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_knowledge_requires_authentication(): void
    {
        $response = $this->putJson('/api/knowledge/1', [
            'question' => 'Updated question?',
        ]);

        $response->assertStatus(401);
    }

    public function test_delete_knowledge_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/knowledge/1');

        $response->assertStatus(401);
    }

    public function test_update_settings_requires_authentication(): void
    {
        $response = $this->putJson('/api/settings', [
            'key' => 'hero_title',
            'value' => 'New Title',
        ]);

        $response->assertStatus(401);
    }

    public function test_batch_update_settings_requires_authentication(): void
    {
        $response = $this->postJson('/api/settings/batch', [
            'settings' => ['hero_title' => 'New Title'],
        ]);

        $response->assertStatus(401);
    }

    public function test_upload_requires_authentication(): void
    {
        $response = $this->postJson('/api/upload', []);

        $response->assertStatus(401);
    }

    public function test_delete_upload_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/upload', ['path' => 'uploads/test.jpg']);

        $response->assertStatus(401);
    }

    // ─── Regular user accessing admin routes gets 403 ──────────────────────

    public function test_regular_user_cannot_create_flower(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/flowers', [
                'name' => 'Test Flower',
                'name_en' => 'Test Flower EN',
                'category' => 'rose',
                'price' => 100,
                'image' => 'http://example.com/flower.jpg',
                'description' => 'A test flower',
                'meaning' => 'Love',
                'care' => 'Water daily',
                'stock' => 10,
            ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_create_category(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/categories', [
                'name' => 'Test Category',
                'slug' => 'test-category',
                'icon' => '🌺',
                'description' => 'A test category',
            ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_create_knowledge(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/knowledge', [
                'question' => 'Test question?',
                'answer' => 'Test answer',
                'category' => 'test',
            ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_update_settings(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/settings', [
                'key' => 'hero_title',
                'value' => 'New Title',
            ]);

        $response->assertStatus(403);
    }
}
