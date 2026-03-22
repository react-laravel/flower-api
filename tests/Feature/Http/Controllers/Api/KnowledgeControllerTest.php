<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Knowledge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): array
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('admin')->plainTextToken;
        return ['admin' => $admin, 'token' => $token];
    }

    private function actingAsUser(): array
    {
        $user = User::factory()->create(['is_admin' => false]);
        $token = $user->createToken('user')->plainTextToken;
        return ['user' => $user, 'token' => $token];
    }
    public function test_index_returns_all_knowledge_ordered_by_category(): void
    {
        Knowledge::factory()->count(3)->create();

        $response = $this->getJson('/api/knowledge');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }
    public function test_show_returns_knowledge_by_id(): void
    {
        $knowledge = Knowledge::factory()->create();

        $response = $this->getJson("/api/knowledge/{$knowledge->id}");

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $knowledge->id]]);
    }
    public function test_show_returns_404_for_missing_knowledge(): void
    {
        $response = $this->getJson('/api/knowledge/99999');

        $response->assertNotFound();
    }
    public function test_store_creates_knowledge_as_admin(): void
    {
        $auth = $this->actingAsAdmin();

        $data = [
            'question' => 'How to care for roses?',
            'answer' => 'Keep them in water and change water daily.',
            'category' => 'care',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/knowledge', $data);

        $response->assertCreated()
            ->assertJson(['success' => true, 'data' => ['question' => 'How to care for roses?']]);

        $this->assertDatabaseHas('knowledge', ['question' => 'How to care for roses?']);
    }
    public function test_store_validates_required_fields(): void
    {
        $auth = $this->actingAsAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/knowledge', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question', 'answer', 'category']);
    }
    public function test_update_modifies_knowledge_as_admin(): void
    {
        $auth = $this->actingAsAdmin();
        $knowledge = Knowledge::factory()->create(['question' => 'Old question']);

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->putJson("/api/knowledge/{$knowledge->id}", [
                'question' => 'New question',
                'answer' => 'New answer',
                'category' => 'care',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['question' => 'New question']]);
    }
    public function test_destroy_removes_knowledge_as_admin(): void
    {
        $auth = $this->actingAsAdmin();
        $knowledge = Knowledge::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->deleteJson("/api/knowledge/{$knowledge->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('knowledge', ['id' => $knowledge->id]);
    }
    public function test_store_rejects_non_admin(): void
    {
        $auth = $this->actingAsUser();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/knowledge', [
                'question' => 'Test?',
                'answer' => 'Test answer',
                'category' => 'test',
            ]);

        $response->assertForbidden();
    }
}
