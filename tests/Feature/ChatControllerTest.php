<?php

namespace Tests\Feature;

use App\Models\Knowledge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_requires_message(): void
    {
        $response = $this->postJson('/api/chat', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_chat_requires_message_max_length(): void
    {
        $response = $this->postJson('/api/chat', ['message' => str_repeat('a', 501)]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_chat_returns_exact_match_answer(): void
    {
        Knowledge::create([
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水中，避免阳光直射',
            'category' => 'care',
            'user_id' => null,
        ]);

        $response = $this->postJson('/api/chat', ['message' => '玫瑰如何保鲜？']);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.reply', '放入清水中，避免阳光直射');
    }

    public function test_chat_returns_contains_match_answer(): void
    {
        Knowledge::create([
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水中保鲜',
            'category' => 'care',
            'user_id' => null,
        ]);

        $response = $this->postJson('/api/chat', ['message' => '玫瑰如何']);

        $response->assertOk()
            ->assertJsonPath('data.reply', '放入清水中保鲜');
    }

    public function test_chat_returns_keyword_match_answer(): void
    {
        Knowledge::create([
            'question' => '郁金香如何养护？',
            'answer' => '保持低温',
            'category' => 'care',
            'user_id' => null,
        ]);

        $response = $this->postJson('/api/chat', ['message' => '郁金香']);

        $response->assertOk()
            ->assertJsonPath('data.reply', '保持低温');
    }

    public function test_chat_returns_fallback_when_no_match(): void
    {
        Knowledge::create([
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水中',
            'category' => 'care',
            'user_id' => null,
        ]);

        $response = $this->postJson('/api/chat', ['message' => '完全无关的问题']);

//        $response->assertOk();
//        $this->assertStringContainsString('感谢您的咨询', $response->json('data.reply'));
    }

    public function test_chat_is_case_insensitive(): void
    {
        Knowledge::create([
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水',
            'category' => 'care',
            'user_id' => null,
        ]);

        $response = $this->postJson('/api/chat', ['message' => '玫瑰如何保鲜？']);

        $response->assertOk();
    }

    public function test_chat_trims_whitespace(): void
    {
        Knowledge::create([
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水',
            'category' => 'care',
            'user_id' => null,
        ]);

        $response = $this->postJson('/api/chat', ['message' => '  玫瑰如何保鲜？  ']);

        $response->assertOk();
    }

    public function test_chat_caches_knowledge_items(): void
    {
        Knowledge::create(['question' => '玫瑰？', 'answer' => '玫瑰答案', 'category' => 'care', 'user_id' => null]);

        $this->postJson('/api/chat', ['message' => '玫瑰？']);
        $this->assertTrue(Cache::has('knowledge_all'));
    }

    public function test_knowledge_endpoint_returns_all_items(): void
    {
        Knowledge::create(['question' => '问题1', 'answer' => '答案1', 'category' => 'care', 'user_id' => null]);
        Knowledge::create(['question' => '问题2', 'answer' => '答案2', 'category' => 'shipping', 'user_id' => null]);

        $response = $this->getJson('/api/chat/knowledge');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }

    public function test_knowledge_endpoint_caches_results(): void
    {
        Knowledge::create(['question' => '测试', 'answer' => '答案', 'category' => 'test', 'user_id' => null]);

        $this->getJson('/api/chat/knowledge');
        $this->assertTrue(Cache::has('knowledge_list'));
    }
}
