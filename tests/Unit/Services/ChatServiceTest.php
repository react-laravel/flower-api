<?php

namespace Tests\Unit\Services;

use App\Models\Knowledge;
use App\Services\ChatService;
use App\Services\KnowledgeSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = new ChatService(new KnowledgeSearchService());
    }

    #[Test]
    public function test_process_message_returns_matched_answer(): void
    {
        $question = fake()->sentence(3) . '?';
        $answer = fake()->sentence();
        Knowledge::create([
            'question' => $question,
            'answer' => $answer,
            'category' => fake()->word(),
            'user_id' => null,
        ]);

        $result = $this->service->processMessage($question);

        $this->assertEquals($answer, $result['reply']);
    }

    #[Test]
    public function test_process_message_returns_fallback_when_no_match(): void
    {
        Knowledge::create([
            'question' => fake()->sentence(3) . '?',
            'answer' => fake()->sentence(),
            'category' => fake()->word(),
            'user_id' => null,
        ]);

        $result = $this->service->processMessage(fake()->uuid());

        $this->assertStringContainsString('感谢您的咨询', $result['reply']);
    }

    #[Test]
    public function test_get_knowledge_for_client_returns_array(): void
    {
        Knowledge::create(['question' => fake()->sentence(3) . '?', 'answer' => fake()->sentence(), 'category' => 'care', 'user_id' => null]);
        Knowledge::create(['question' => fake()->sentence(3) . '?', 'answer' => fake()->sentence(), 'category' => 'shipping', 'user_id' => null]);

        $result = $this->service->getKnowledgeForClient();

        $this->assertIsArray($result);
    }

    #[Test]
    public function test_get_knowledge_for_client_caches_results(): void
    {
        Knowledge::create(['question' => fake()->sentence(3) . '?', 'answer' => fake()->sentence(), 'category' => fake()->word(), 'user_id' => null]);

        $this->service->getKnowledgeForClient();

        $this->assertTrue(Cache::has('knowledge_list'));
    }
}
