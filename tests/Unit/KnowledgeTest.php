<?php

namespace Tests\Unit;

use App\Models\Knowledge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeTest extends TestCase
{
    use RefreshDatabase;

    // ─── Mass assignment ────────────────────────────────────────────────────

    public function test_can_create_knowledge_with_all_fields(): void
    {
        $knowledge = Knowledge::create([
            'question' => '鲜花如何保鲜？',
            'answer' => '每天换水，保持水质清洁',
            'category' => 'care',
        ]);

        $this->assertDatabaseHas('knowledge', ['question' => '鲜花如何保鲜？', 'category' => 'care']);
        $this->assertEquals('每天换水，保持水质清洁', $knowledge->answer);
    }

    public function test_can_create_knowledge_without_category(): void
    {
        $knowledge = Knowledge::create([
            'question' => 'Test question?',
            'answer' => 'Test answer',
        ]);

        $this->assertDatabaseHas('knowledge', ['question' => 'Test question?']);
        $this->assertNull($knowledge->category);
    }

    // ─── Fillable protection ─────────────────────────────────────────────────

    public function test_non_fillable_fields_are_ignored(): void
    {
        $knowledge = Knowledge::create([
            'question' => 'Test?',
            'answer' => 'Test',
            'category' => 'test',
            'unknown_field' => 'should be ignored',
        ]);

        $this->assertArrayNotHasKey('unknown_field', $knowledge->getAttributes());
    }

    // ─── CRUD ───────────────────────────────────────────────────────────────

    public function test_can_update_knowledge(): void
    {
        $knowledge = Knowledge::create([
            'question' => 'Original question?',
            'answer' => 'Original answer',
            'category' => 'care',
        ]);

        $knowledge->update(['question' => 'Updated question?', 'answer' => 'Updated answer']);

        $this->assertDatabaseHas('knowledge', ['id' => $knowledge->id, 'question' => 'Updated question?']);
    }

    public function test_can_delete_knowledge(): void
    {
        $knowledge = Knowledge::create([
            'question' => 'To delete?',
            'answer' => 'Will be deleted',
            'category' => 'care',
        ]);

        $id = $knowledge->id;
        $knowledge->delete();

        $this->assertDatabaseMissing('knowledge', ['id' => $id]);
    }

    // ─── Querying ───────────────────────────────────────────────────────────

    public function test_can_filter_by_category(): void
    {
        Knowledge::create(['question' => 'Q1?', 'answer' => 'A1', 'category' => 'care']);
        Knowledge::create(['question' => 'Q2?', 'answer' => 'A2', 'category' => 'meaning']);
        Knowledge::create(['question' => 'Q3?', 'answer' => 'A3', 'category' => 'care']);

        $careItems = Knowledge::where('category', 'care')->get();

        $this->assertCount(2, $careItems);
    }

    public function test_can_order_by_category(): void
    {
        Knowledge::create(['question' => 'Q1?', 'answer' => 'A1', 'category' => 'meaning']);
        Knowledge::create(['question' => 'Q2?', 'answer' => 'A2', 'category' => 'care']);
        Knowledge::create(['question' => 'Q3?', 'answer' => 'A3', 'category' => 'order']);

        $ordered = Knowledge::orderBy('category')->get();

        $this->assertEquals('care', $ordered[0]->category);
        $this->assertEquals('meaning', $ordered[1]->category);
        $this->assertEquals('order', $ordered[2]->category);
    }

    public function test_can_search_by_question(): void
    {
        Knowledge::create(['question' => '玫瑰的花语是什么？', 'answer' => '红玫瑰代表热恋', 'category' => 'meaning']);
        Knowledge::create(['question' => '如何浇水？', 'answer' => '每天浇', 'category' => 'care']);

        $results = Knowledge::where('question', 'like', '%玫瑰%')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('玫瑰的花语是什么？', $results->first()->question);
    }
}
