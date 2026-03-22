<?php

namespace Tests\Feature;

use App\Models\Knowledge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_can_list_knowledge_base(): void
    {
        Knowledge::factory()->count(3)->create();

        $response = $this->getJson('/api/knowledge');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'question', 'answer', 'category']
                ]
            ])
            ->assertJson(['success' => true]);
    }

    public function test_can_show_single_knowledge(): void
    {
        $knowledge = Knowledge::factory()->create();

        $response = $this->getJson("/api/knowledge/{$knowledge->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'question', 'answer', 'category']
            ])
            ->assertJson(['success' => true, 'data' => ['id' => $knowledge->id]]);
    }

    public function test_show_returns_404_for_nonexistent_knowledge(): void
    {
        $response = $this->getJson('/api/knowledge/99999');

        $response->assertStatus(404);
    }

    public function test_can_create_knowledge_with_valid_data(): void
    {
        $user = \App\Models\User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test-token')->plainTextToken;

        $knowledgeData = [
            'question' => 'What is the meaning of red roses?',
            'answer' => 'Red roses symbolize love and passion.',
            'category' => 'flower_meaning',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/knowledge', $knowledgeData);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['question' => 'What is the meaning of red roses?']);

        $this->assertDatabaseHas('knowledge', ['question' => 'What is the meaning of red roses?']);
    }

    public function test_create_knowledge_requires_authentication(): void
    {
        $knowledgeData = [
            'question' => 'What is the meaning of red roses?',
            'answer' => 'Red roses symbolize love and passion.',
            'category' => 'flower_meaning',
        ];

        $response = $this->postJson('/api/knowledge', $knowledgeData);

        $response->assertStatus(401);
    }

    public function test_can_update_knowledge(): void
    {
        $user = \App\Models\User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test-token')->plainTextToken;
        $knowledge = Knowledge::factory()->create(['question' => 'Old Question']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->putJson("/api/knowledge/{$knowledge->id}", [
            'question' => 'New Question',
            'answer' => 'New Answer',
            'category' => 'care_tips',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['question' => 'New Question']);

        $this->assertDatabaseHas('knowledge', ['id' => $knowledge->id, 'question' => 'New Question']);
    }

    public function test_can_delete_knowledge(): void
    {
        $user = \App\Models\User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test-token')->plainTextToken;
        $knowledge = Knowledge::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->deleteJson("/api/knowledge/{$knowledge->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('knowledge', ['id' => $knowledge->id]);
    }
}
