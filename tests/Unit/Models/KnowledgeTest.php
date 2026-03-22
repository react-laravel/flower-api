<?php

namespace Tests\Unit\Models;

use App\Models\Knowledge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_knowledge(): void
    {
        $knowledge = Knowledge::create([
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水中保存',
            'category' => 'care',
            'user_id' => null,
        ]);

        $this->assertDatabaseHas('knowledge', [
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水中保存',
            'category' => 'care',
        ]);
    }

    public function test_question_is_fillable(): void
    {
        $knowledge = Knowledge::create(['question' => '测试问题？', 'answer' => '测试答案', 'category' => 'test', 'user_id' => null]);
        $this->assertEquals('测试问题？', $knowledge->question);
    }

    public function test_answer_is_fillable(): void
    {
        $knowledge = Knowledge::create(['question' => '问题', 'answer' => '这是答案', 'category' => 'test', 'user_id' => null]);
        $this->assertEquals('这是答案', $knowledge->answer);
    }

    public function test_category_is_fillable(): void
    {
        $knowledge = Knowledge::create(['question' => '问题', 'answer' => '答案', 'category' => 'shipping', 'user_id' => null]);
        $this->assertEquals('shipping', $knowledge->category);
    }

    public function test_user_id_is_fillable(): void
    {
        $user = User::factory()->create();
        $knowledge = Knowledge::create(['question' => '问题', 'answer' => '答案', 'category' => 'test', 'user_id' => $user->id]);
        $this->assertEquals($user->id, $knowledge->user_id);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $knowledge = Knowledge::create(['question' => '问题', 'answer' => '答案', 'category' => 'test', 'user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $knowledge->user);
        $this->assertEquals($user->id, $knowledge->user->id);
    }

    public function test_user_relation_returns_null_when_no_user(): void
    {
        $knowledge = Knowledge::create(['question' => '问题', 'answer' => '答案', 'category' => 'test', 'user_id' => null]);
        $this->assertNull($knowledge->user);
    }
}
