<?php

namespace Tests\Unit\Services;

use App\Models\Knowledge;
use App\Services\ChatService;
use App\Services\KnowledgeSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChatService(new KnowledgeSearchService());
    }

    public function test_process_message_returns_matched_answer(): void
    {
        Knowledge::create([
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水中',
            'category' => 'care',
            'user_id' => null,
        ]);

        $result = $this->service->processMessage('玫瑰如何保鲜？');

        $this->assertEquals('放入清水中', $result['reply']);
    }

    public function test_process_message_returns_fallback_when_no_match(): void
    {
        Knowledge::create([
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水中',
            'category' => 'care',
            'user_id' => null,
        ]);

        $result = $this->service->processMessage('完全不相关的问题');

        $this->assertStringContainsString('感谢您的咨询', $result['reply']);
    }

    public function test_get_knowledge_for_client_returns_array(): void
    {
        Knowledge::create(['question' => '问题1', 'answer' => '答案1', 'category' => 'care', 'user_id' => null]);
        Knowledge::create(['question' => '问题2', 'answer' => '答案2', 'category' => 'shipping', 'user_id' => null]);

        $result = $this->service->getKnowledgeForClient();

        $this->assertCount(2, $result);
    }

    public function test_get_knowledge_for_client_caches_results(): void
    {
        Knowledge::create(['question' => '测试', 'answer' => '答案', 'category' => 'test', 'user_id' => null]);

        $this->service->getKnowledgeForClient();

        $this->assertTrue(Cache::has('knowledge_list'));
    }
}
