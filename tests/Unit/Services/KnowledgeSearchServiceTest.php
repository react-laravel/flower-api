<?php

namespace Tests\Unit\Services;

use App\Models\Knowledge;
use App\Services\KnowledgeSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class KnowledgeSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private KnowledgeSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KnowledgeSearchService();
    }

    public function test_search_returns_exact_match_answer(): void
    {
        Knowledge::create([
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水中',
            'category' => 'care',
            'user_id' => null,
        ]);

        $result = $this->service->search('玫瑰如何保鲜？');

        $this->assertEquals('放入清水中', $result);
    }

    public function test_search_returns_contains_match_answer(): void
    {
        Knowledge::create([
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水中保鲜',
            'category' => 'care',
            'user_id' => null,
        ]);

        $result = $this->service->search('玫瑰如何');

        $this->assertEquals('放入清水中保鲜', $result);
    }

    public function test_search_returns_keyword_match_answer(): void
    {
        Knowledge::create([
            'question' => '郁金香如何养护？',
            'answer' => '保持低温',
            'category' => 'care',
            'user_id' => null,
        ]);

        $result = $this->service->search('郁金香');

        $this->assertEquals('保持低温', $result);
    }

    public function test_search_returns_null_when_no_match(): void
    {
        Knowledge::create([
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水中',
            'category' => 'care',
            'user_id' => null,
        ]);

        $result = $this->service->search('完全不相关的问题');

        $this->assertNull($result);
    }

    public function test_search_is_case_insensitive(): void
    {
        Knowledge::create([
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水',
            'category' => 'care',
            'user_id' => null,
        ]);

        $result = $this->service->search('玫瑰如何保鲜？');

        $this->assertEquals('放入清水', $result);
    }

    public function test_search_trims_whitespace(): void
    {
        Knowledge::create([
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水',
            'category' => 'care',
            'user_id' => null,
        ]);

        $result = $this->service->search('  玫瑰如何保鲜？  ');

        $this->assertEquals('放入清水', $result);
    }

    public function test_get_all_sorted_by_category(): void
    {
        Knowledge::create(['question' => 'ZZZ', 'answer' => '答案', 'category' => 'zzz', 'user_id' => null]);
        Knowledge::create(['question' => 'AAA', 'answer' => '答案', 'category' => 'aaa', 'user_id' => null]);

        $result = $this->service->getAllSortedByCategory();

        $this->assertEquals('aaa', $result->first()->category);
    }

    public function test_search_caches_knowledge_items(): void
    {
        Knowledge::create(['question' => '玫瑰？', 'answer' => '玫瑰答案', 'category' => 'care', 'user_id' => null]);

        $this->service->search('玫瑰？');

        $this->assertTrue(Cache::has('knowledge_all'));
    }
}
