<?php

namespace Tests\Feature\Controllers;

use App\Models\Knowledge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_returns_exact_match_answer(): void
    {
        Knowledge::create([
            'question' => '玫瑰的花语是什么？',
            'answer' => '玫瑰象征爱情',
            'category' => '花语',
        ]);

        $response = $this->postJson('/api/chat', [
            'message' => '玫瑰的花语是什么？',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['reply']]);

        $this->assertEquals('玫瑰象征爱情', $response->json('data.reply'));
    }

    /**
     * @test
     */
    public function it_returns_contains_match_answer(): void
    {
        Knowledge::create([
            'question' => '如何保养玫瑰花？',
            'answer' => '保持低温、每天换水',
            'category' => '养护',
        ]);

        $response = $this->postJson('/api/chat', [
            'message' => '保养玫瑰花',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('保持低温、每天换水', $response->json('data.reply'));
    }

    /**
     * @test
     */
    public function it_returns_keyword_match_answer(): void
    {
        Knowledge::create([
            'question' => '玫瑰的花语是什么？',
            'answer' => '红玫瑰代表热恋',
            'category' => '花语',
        ]);

        $response = $this->postJson('/api/chat', [
            'message' => '玫瑰代表什么？',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('红玫瑰代表热恋', $response->json('data.reply'));
    }

    /**
     * @test
     */
    public function it_returns_default_answer_when_no_match(): void
    {
        Knowledge::create([
            'question' => '玫瑰的花语是什么？',
            'answer' => '玫瑰象征爱情',
            'category' => '花语',
        ]);

        $response = $this->postJson('/api/chat', [
            'message' => '完全无关的问题 xyz123',
        ]);

        $response->assertStatus(200);
        $reply = $response->json('data.reply');
        $this->assertStringContainsString('鲜花如何保鲜', $reply);
    }

    /**
     * @test
     */
    public function it_validates_required_message_field(): void
    {
        $response = $this->postJson('/api/chat', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /**
     * @test
     */
    public function it_is_case_insensitive(): void
    {
        Knowledge::create([
            'question' => 'Rose meaning',
            'answer' => 'Love and romance',
            'category' => 'meaning',
        ]);

        $response = $this->postJson('/api/chat', [
            'message' => 'ROSE MEANING',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('Love and romance', $response->json('data.reply'));
    }

    /**
     * @test
     */
    public function it_returns_best_match_when_multiple_options(): void
    {
        Knowledge::create([
            'question' => '玫瑰的花语',
            'answer' => '爱情',
            'category' => '花语',
        ]);
        Knowledge::create([
            'question' => '玫瑰的养护',
            'answer' => '低温水养',
            'category' => '养护',
        ]);

        $response = $this->postJson('/api/chat', [
            'message' => '玫瑰花语是什么',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('爱情', $response->json('data.reply'));
    }

    /**
     * @test
     */
    public function it_can_list_all_knowledge_items(): void
    {
        Knowledge::create(['question' => 'Q1', 'answer' => 'A1', 'category' => '花语']);
        Knowledge::create(['question' => 'Q2', 'answer' => 'A2', 'category' => '养护']);

        $response = $this->getJson('/api/knowledge');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * @test
     */
    public function it_orders_knowledge_by_category(): void
    {
        Knowledge::create(['question' => 'Q1', 'answer' => 'A1', 'category' => '养护']);
        Knowledge::create(['question' => 'Q2', 'answer' => 'A2', 'category' => '花语']);
        Knowledge::create(['question' => 'Q3', 'answer' => 'A3', 'category' => '配送']);

        $response = $this->getJson('/api/knowledge');

        $response->assertStatus(200);
        $this->assertEquals('养护', $response->json('data.0.category'));
    }
}
