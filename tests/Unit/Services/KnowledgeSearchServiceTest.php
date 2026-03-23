<?php

namespace Tests\Unit\Services;

use App\Models\Knowledge;
use App\Services\KnowledgeSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private KnowledgeSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KnowledgeSearchService();
        Cache::flush();
    }

    #[Test]
    public function test_search_returns_exact_match_answer(): void
    {
        Knowledge::create([
            'question' => fake()->sentence(3) . '?',
            'answer' => fake()->sentence(),
            'category' => fake()->word(),
            'user_id' => null,
        ]);

        $item = Knowledge::first();
        $result = $this->service->search($item->question);

        $this->assertEquals($item->answer, $result);
    }

    #[Test]
    public function test_search_returns_contains_match_answer(): void
    {
        $question = fake()->sentence(4) . '?';
        $answer = fake()->sentence();
        Knowledge::create([
            'question' => $question,
            'answer' => $answer,
            'category' => fake()->word(),
            'user_id' => null,
        ]);

        $result = $this->service->search(mb_substr($question, 0, 6));

        $this->assertEquals($answer, $result);
    }

    #[Test]
    public function test_search_returns_keyword_match_answer(): void
    {
        $keyword = fake()->word();
        $answer = fake()->sentence();
        Knowledge::create([
            'question' => "{$keyword} " . fake()->sentence() . '?',
            'answer' => $answer,
            'category' => fake()->word(),
            'user_id' => null,
        ]);

        $result = $this->service->search($keyword);

        $this->assertEquals($answer, $result);
    }

    #[Test]
    public function test_search_returns_null_when_no_match(): void
    {
        // Use Chinese text to avoid UUID characters accidentally matching English sentences
        Knowledge::create([
            'question' => '玫瑰花如何保鲜？',
            'answer' => '放入清水中',
            'category' => fake()->word(),
            'user_id' => null,
        ]);

        $result = $this->service->search('与花无关的随机搜索词XYZ123');

        $this->assertNull($result);
    }

    #[Test]
    public function test_search_is_case_insensitive(): void
    {
        $question = 'Test Question?';
        $answer = fake()->sentence();
        Knowledge::create([
            'question' => $question,
            'answer' => $answer,
            'category' => fake()->word(),
            'user_id' => null,
        ]);

        $result = $this->service->search(strtolower($question));

        $this->assertEquals($answer, $result);
    }

    #[Test]
    public function test_search_trims_whitespace(): void
    {
        $question = 'Trim Test?';
        $answer = fake()->sentence();
        Knowledge::create([
            'question' => $question,
            'answer' => $answer,
            'category' => fake()->word(),
            'user_id' => null,
        ]);

        $result = $this->service->search("  {$question}  ");

        $this->assertEquals($answer, $result);
    }

    #[Test]
    public function test_find_best_match_returns_item_and_score(): void
    {
        $item = Knowledge::create([
            'question' => fake()->sentence(3) . '?',
            'answer' => fake()->sentence(),
            'category' => fake()->word(),
            'user_id' => null,
        ]);

        $result = $this->service->findBestMatch($item->question);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('item', $result);
        $this->assertArrayHasKey('score', $result);
        $this->assertEquals($item->id, $result['item']->id);
        $this->assertIsInt($result['score']);
        $this->assertGreaterThan(0, $result['score']);
    }

    #[Test]
    public function test_find_best_match_returns_null_when_no_match(): void
    {
        Knowledge::create([
            'question' => fake()->sentence(3) . '?',
            'answer' => fake()->sentence(),
            'category' => fake()->word(),
            'user_id' => null,
        ]);

        $result = $this->service->findBestMatch(fake()->uuid());

        $this->assertNull($result);
    }

    #[Test]
    public function test_get_all_sorted_by_category(): void
    {
        Knowledge::create(['question' => 'ZZZ', 'answer' => fake()->sentence(), 'category' => 'zzz', 'user_id' => null]);
        Knowledge::create(['question' => 'AAA', 'answer' => fake()->sentence(), 'category' => 'aaa', 'user_id' => null]);

        $result = $this->service->getAllSortedByCategory();

        $this->assertEquals('aaa', $result->first()->category);
    }

    #[Test]
    public function test_search_caches_knowledge_items(): void
    {
        Knowledge::create(['question' => fake()->sentence(3) . '?', 'answer' => fake()->sentence(), 'category' => fake()->word(), 'user_id' => null]);

        $this->service->search(Knowledge::first()->question);

        $this->assertTrue(Cache::has('knowledge_all'));
    }
}
