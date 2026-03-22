<?php

namespace Tests\Unit;

use App\Models\Knowledge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_knowledge_can_be_created(): void
    {
        $knowledge = Knowledge::create([
            'question' => '玫瑰花语是什么？',
            'answer' => '玫瑰代表爱情和浪漫',
            'category' => '花语',
        ]);

        $this->assertDatabaseHas('knowledge', ['question' => '玫瑰花语是什么？']);
        $this->assertEquals('玫瑰花语是什么？', $knowledge->question);
        $this->assertEquals('玫瑰代表爱情和浪漫', $knowledge->answer);
        $this->assertEquals('花语', $knowledge->category);
    }

    public function test_knowledge_fillable_attributes(): void
    {
        $fillable = (new Knowledge())->getFillable();

        $this->assertContains('question', $fillable);
        $this->assertContains('answer', $fillable);
        $this->assertContains('category', $fillable);
    }
}
