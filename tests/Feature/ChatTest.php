<?php

namespace Tests\Feature;

use App\Models\Knowledge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedKnowledge();
    }

    protected function seedKnowledge(): void
    {
        Knowledge::create([
            'question' => '鲜花如何保鲜？',
            'answer' => '每天换水，保持水质清洁。斜剪花茎帮助吸水。',
            'category' => 'care',
        ]);
        Knowledge::create([
            'question' => '玫瑰的花语是什么？',
            'answer' => '红玫瑰代表热恋和永恒的爱。',
            'category' => 'meaning',
        ]);
        Knowledge::create([
            'question' => '如何订花？',
            'answer' => '您可以通过网站、微信、电话订花。',
            'category' => 'order',
        ]);
    }

    // ─── Chat endpoint ──────────────────────────────────────────────────────

    public function test_chat_with_exact_match_returns_answer(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => '鲜花如何保鲜？',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['reply']]);

        $reply = $response->json('data.reply');
        $this->assertStringContainsString('每天换水', $reply);
    }

    public function test_chat_with_partial_match_returns_answer(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => '如何保鲜？',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['reply']]);

        $reply = $response->json('data.reply');
        $this->assertStringContainsString('每天换水', $reply);
    }

    public function test_chat_with_keyword_match_returns_answer(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => '玫瑰',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['reply']]);

        $reply = $response->json('data.reply');
        // Should match rose meaning question
        $this->assertStringContainsString('玫瑰', $reply);
    }

    public function test_chat_with_no_match_returns_default_reply(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => '宇宙飞船如何制作？',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['reply']]);

        $reply = $response->json('data.reply');
        $this->assertStringContainsString('感谢您的咨询', $reply);
    }

    public function test_chat_requires_message_field(): void
    {
        $response = $this->postJson('/api/chat', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_chat_is_case_insensitive(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => '玫瑰的花语是什么？',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $reply = $response->json('data.reply');
        $this->assertStringContainsString('红玫瑰', $reply);
    }

    public function test_chat_is_public_no_auth_required(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => '鲜花如何保鲜？',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // ─── Chat Knowledge listing ─────────────────────────────────────────────

    public function test_chat_knowledge_returns_all_knowledge_items(): void
    {
        $response = $this->getJson('/api/chat/knowledge');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    public function test_chat_knowledge_ordered_by_category(): void
    {
        $response = $this->getJson('/api/chat/knowledge');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('care', $data[0]['category']);
        $this->assertEquals('meaning', $data[1]['category']);
        $this->assertEquals('order', $data[2]['category']);
    }

    public function test_chat_knowledge_is_public_no_auth_required(): void
    {
        $response = $this->getJson('/api/chat/knowledge');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
