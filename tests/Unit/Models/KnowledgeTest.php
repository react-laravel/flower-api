<?php

namespace Tests\Unit\Models;

use App\Models\Knowledge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_a_knowledge_item(): void
    {
        $knowledge = Knowledge::create([
            'question' => '玫瑰的花语是什么？',
            'answer' => '玫瑰象征着爱情和浪漫',
            'category' => '花语',
        ]);

        $this->assertDatabaseHas('knowledge', [
            'question' => '玫瑰的花语是什么？',
            'category' => '花语',
        ]);

        $this->assertEquals('玫瑰的花语是什么？', $knowledge->question);
        $this->assertEquals('玫瑰象征着爱情和浪漫', $knowledge->answer);
        $this->assertEquals('花语', $knowledge->category);
    }

    /**
     * @test
     */
    public function it_has_fillable_attributes(): void
    {
        $fillable = ['question', 'answer', 'category'];

        $this->assertEquals($fillable, (new Knowledge)->getFillable());
    }

    /**
     * @test
     */
    public function it_can_order_knowledge_by_category(): void
    {
        Knowledge::create(['question' => 'Q1', 'answer' => 'A1', 'category' => '养护']);
        Knowledge::create(['question' => 'Q2', 'answer' => 'A2', 'category' => '花语']);
        Knowledge::create(['question' => 'Q3', 'answer' => 'A3', 'category' => '配送']);

        $items = Knowledge::orderBy('category')->get();

        $this->assertEquals('养护', $items->first()->category);
    }

    /**
     * @test
     */
    public function it_can_update_knowledge_item(): void
    {
        $knowledge = Knowledge::create([
            'question' => '玫瑰的花语是什么？',
            'answer' => '玫瑰象征着爱情',
            'category' => '花语',
        ]);

        $knowledge->update(['answer' => '更新后的答案']);

        $this->assertEquals('更新后的答案', $knowledge->fresh()->answer);
    }

    /**
     * @test
     */
    public function it_can_delete_knowledge_item(): void
    {
        $knowledge = Knowledge::create([
            'question' => '玫瑰的花语是什么？',
            'answer' => '玫瑰象征着爱情',
            'category' => '花语',
        ]);

        $id = $knowledge->id;
        $knowledge->delete();

        $this->assertNull(Knowledge::find($id));
    }

    /**
     * @test
     */
    public function it_can_filter_by_category(): void
    {
        Knowledge::create(['question' => 'Q1', 'answer' => 'A1', 'category' => '花语']);
        Knowledge::create(['question' => 'Q2', 'answer' => 'A2', 'category' => '花语']);
        Knowledge::create(['question' => 'Q3', 'answer' => 'A3', 'category' => '养护']);

        $items = Knowledge::where('category', '花语')->get();

        $this->assertCount(2, $items);
    }
}
