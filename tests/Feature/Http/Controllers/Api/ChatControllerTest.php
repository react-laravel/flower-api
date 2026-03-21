<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Knowledge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;
    public function test_chat_returns_exact_match_response(): void
    {
        Knowledge::factory()->create([
            'question' => 'How to care for roses?',
            'answer' => 'Keep roses in fresh water.',
            'category' => 'care',
        ]);

        $response = $this->postJson('/api/chat', [
            'message' => 'How to care for roses?',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => ['reply'],
            ]);

        $this->assertStringContainsString('Keep roses in fresh water', $response->json('data.reply'));
    }
    public function test_chat_returns_partial_match_response(): void
    {
        Knowledge::factory()->create([
            'question' => 'What is the meaning of red roses?',
            'answer' => 'Red roses mean love.',
            'category' => 'meaning',
        ]);

        $response = $this->postJson('/api/chat', [
            'message' => 'meaning of red roses',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }
    public function test_chat_returns_default_response_when_no_match(): void
    {
        // No knowledge entries

        $response = $this->postJson('/api/chat', [
            'message' => 'What is the weather?',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['reply'],
            ]);
    }
    public function test_chat_requires_message(): void
    {
        $response = $this->postJson('/api/chat', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }
    public function test_knowledge_returns_all_entries(): void
    {
        Knowledge::factory()->count(2)->create();

        $response = $this->getJson('/api/chat/knowledge');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }
}
