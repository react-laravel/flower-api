<?php

namespace Tests\Feature\Controllers;

use App\Models\Knowledge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'is_admin' => true,
        ]);
    }

    /**
     * @test
     */
    public function it_can_list_all_knowledge_items(): void
    {
        Knowledge::create(['question' => '玫瑰花语？', 'answer' => '爱情', 'category' => '花语']);
        Knowledge::create(['question' => '如何养护？', 'answer' => '换水', 'category' => '养护']);

        $response = $this->getJson('/api/knowledge-items');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * @test
     */
    public function it_orders_knowledge_by_category(): void
    {
        Knowledge::create(['question' => 'Q1', 'answer' => 'A1', 'category' => '养护']);
        Knowledge::create(['question' => 'Q2', 'answer' => 'A2', 'category' => '花语']);

        $response = $this->getJson('/api/knowledge-items');

        $response->assertStatus(200);
        $this->assertEquals('养护', $response->json('data.0.category'));
    }

    /**
     * @test
     */
    public function it_can_create_a_knowledge_item(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/knowledge-items', [
                'question' => '玫瑰花语是什么？',
                'answer' => '玫瑰象征爱情',
                'category' => '花语',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('knowledge', ['question' => '玫瑰花语是什么？']);
    }

    /**
     * @test
     */
    public function it_validates_required_fields_on_create(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/knowledge-items', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question', 'answer', 'category']);
    }

    /**
     * @test
     */
    public function it_can_show_a_knowledge_item(): void
    {
        $item = Knowledge::create([
            'question' => '玫瑰花语？',
            'answer' => '爱情',
            'category' => '花语',
        ]);

        $response = $this->getJson("/api/knowledge-items/{$item->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['question' => '玫瑰花语？'],
            ]);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_knowledge_item(): void
    {
        $response = $this->getJson('/api/knowledge-items/99999');

        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function it_can_update_a_knowledge_item(): void
    {
        $item = Knowledge::create([
            'question' => '玫瑰花语？',
            'answer' => '爱情',
            'category' => '花语',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/knowledge-items/{$item->id}", [
                'question' => '玫瑰代表什么？',
                'answer' => '热情',
                'category' => '花语',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('玫瑰代表什么？', $item->fresh()->question);
        $this->assertEquals('热情', $item->fresh()->answer);
    }

    /**
     * @test
     */
    public function it_can_delete_a_knowledge_item(): void
    {
        $item = Knowledge::create([
            'question' => '玫瑰花语？',
            'answer' => '爱情',
            'category' => '花语',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/knowledge-items/{$item->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => '删除成功']);

        $this->assertNull(Knowledge::find($item->id));
    }
}
