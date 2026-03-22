<?php

namespace Tests\Feature;

use App\Models\Knowledge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KnowledgeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_returns_all_knowledge_items(): void
    {
        Knowledge::factory()->count(3)->create();

        $response = $this->getJson('/api/knowledge');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    public function test_index_orders_by_category(): void
    {
        Knowledge::factory()->create(['category' => '花语']);
        Knowledge::factory()->create(['category' => '养护']);

        $response = $this->getJson('/api/knowledge');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('养护', $data[0]['category']);
    }

    public function test_store_creates_knowledge(): void
    {
        Sanctum::actingAs($this->admin);

        $payload = [
            'question' => '玫瑰如何保鲜？',
            'answer' => '每天换水并剪根',
            'category' => '养护',
        ];

        $response = $this->postJson('/api/knowledge', $payload);

        $response->assertCreated()
            ->assertJson(['success' => true]);
        $this->assertDatabaseHas('knowledge', ['question' => '玫瑰如何保鲜？']);
    }

    public function test_store_requires_all_fields(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/knowledge', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question', 'answer', 'category']);
    }

    public function test_show_returns_knowledge_item(): void
    {
        $item = Knowledge::factory()->create();

        $response = $this->getJson("/api/knowledge/{$item->id}");

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $item->id]]);
    }

    public function test_show_returns_404_for_missing(): void
    {
        $response = $this->getJson('/api/knowledge/99999');

        $response->assertNotFound();
    }

    public function test_update_modifies_knowledge(): void
    {
        Sanctum::actingAs($this->admin);
        $item = Knowledge::factory()->create(['question' => 'Old Question']);

        $response = $this->putJson("/api/knowledge/{$item->id}", ['question' => 'New Question']);

        $response->assertOk()
            ->assertJson(['success' => true]);
        $this->assertDatabaseHas('knowledge', ['id' => $item->id, 'question' => 'New Question']);
    }

    public function test_destroy_deletes_knowledge(): void
    {
        Sanctum::actingAs($this->admin);
        $item = Knowledge::factory()->create();

        $response = $this->deleteJson("/api/knowledge/{$item->id}");

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '删除成功']);
        $this->assertDatabaseMissing('knowledge', ['id' => $item->id]);
    }
}
