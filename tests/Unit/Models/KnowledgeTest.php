<?php

namespace Tests\Unit\Models;

use App\Models\Knowledge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_create_knowledge(): void
    {
        // Arrange
        $data = [
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水中保存',
            'category' => 'care',
            'user_id' => null,
        ];

        // Act
        $knowledge = Knowledge::create($data);

        // Assert
        $this->assertDatabaseHas('knowledge', [
            'question' => '玫瑰如何保鲜？',
            'answer' => '放入清水中保存',
            'category' => 'care',
        ]);
    }

    #[Test]
    public function question_is_fillable(): void
    {
        // Arrange & Act
        $knowledge = Knowledge::create([
            'question' => '测试问题？',
            'answer' => '测试答案',
            'category' => 'test',
            'user_id' => null,
        ]);

        // Assert
        $this->assertEquals('测试问题？', $knowledge->question);
    }

    #[Test]
    public function answer_is_fillable(): void
    {
        // Arrange & Act
        $knowledge = Knowledge::create([
            'question' => '问题',
            'answer' => '这是答案',
            'category' => 'test',
            'user_id' => null,
        ]);

        // Assert
        $this->assertEquals('这是答案', $knowledge->answer);
    }

    #[Test]
    public function category_is_fillable(): void
    {
        // Arrange & Act
        $knowledge = Knowledge::create([
            'question' => '问题',
            'answer' => '答案',
            'category' => 'shipping',
            'user_id' => null,
        ]);

        // Assert
        $this->assertEquals('shipping', $knowledge->category);
    }

    #[Test]
    public function user_id_is_fillable(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $knowledge = Knowledge::create([
            'question' => '问题',
            'answer' => '答案',
            'category' => 'test',
            'user_id' => $user->id,
        ]);

        // Assert
        $this->assertEquals($user->id, $knowledge->user_id);
    }

    #[Test]
    public function belongs_to_user(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $knowledge = Knowledge::create([
            'question' => '问题',
            'answer' => '答案',
            'category' => 'test',
            'user_id' => $user->id,
        ]);

        // Assert
        $this->assertInstanceOf(User::class, $knowledge->user);
        $this->assertEquals($user->id, $knowledge->user->id);
    }

    #[Test]
    public function user_relation_returns_null_when_no_user(): void
    {
        // Arrange & Act
        $knowledge = Knowledge::create([
            'question' => '问题',
            'answer' => '答案',
            'category' => 'test',
            'user_id' => null,
        ]);

        // Assert
        $this->assertNull($knowledge->user);
    }
}
