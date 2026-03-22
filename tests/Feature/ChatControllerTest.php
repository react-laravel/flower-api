<?php

namespace Tests\Feature;

use App\Models\Knowledge;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    public function test_chat_requires_message(): void
    {
        $response = $this->postJson('/api/chat', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_chat_returns_exact_match_reply(): void
    {
        Knowledge::factory()->create([
            'question' => '玫瑰花语是什么？',
            'answer' => '玫瑰代表爱情',
            'category' => '花语',
        ]);

        $response = $this->postJson('/api/chat', ['message' => '玫瑰花语是什么？']);

        $response->assertOk()
            ->assertJson(['success' => true]);
        $this->assertStringContainsString('玫瑰代表爱情', $response->json('data.reply'));
    }

    public function test_chat_returns_contains_match_reply(): void
    {
        Knowledge::factory()->create([
            'question' => '玫瑰如何养护？',
            'answer' => '每天换水',
            'category' => '养护',
        ]);

        $response = $this->postJson('/api/chat', ['message' => '玫瑰如何养护']);

        $response->assertOk();
        $this->assertStringContainsString('每天换水', $response->json('data.reply'));
    }

    public function test_chat_returns_default_reply_when_no_match(): void
    {
        Knowledge::factory()->create([
            'question' => '郁金香怎么养？',
            'answer' => '避免高温',
            'category' => '养护',
        ]);

        $response = $this->postJson('/api/chat', ['message' => '完全不相关的问题 xyz']);

        $response->assertOk();
        $reply = $response->json('data.reply');
        $this->assertIsString($reply);
        $this->assertNotEquals('每天换水', $reply);
    }

    public function test_chat_returns_default_reply_suggestions(): void
    {
        $response = $this->postJson('/api/chat', ['message' => 'hello world test']);

        $response->assertOk();
        $reply = $response->json('data.reply');
        $this->assertIsString($reply);
    }

    public function test_knowledge_returns_all_items(): void
    {
        Knowledge::factory()->count(3)->create();

        $response = $this->getJson('/api/chat/knowledge');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    public function test_knowledge_orders_by_category(): void
    {
        Knowledge::factory()->create(['category' => '花语']);
        Knowledge::factory()->create(['category' => '养护']);

        $response = $this->getJson('/api/chat/knowledge');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('养护', $data[0]['category']);
    }
}
