<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\StoreKnowledgeRequest;
use PHPUnit\Framework\TestCase;

class StoreKnowledgeRequestTest extends TestCase
{
    public function test_it_authorizes_any_request(): void
    {
        $request = new StoreKnowledgeRequest();
        $this->assertTrue($request->authorize());
    }
    public function test_rules_require_question_answer_and_category(): void
    {
        $request = new StoreKnowledgeRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('question', $rules);
        $this->assertArrayHasKey('answer', $rules);
        $this->assertArrayHasKey('category', $rules);

        $this->assertStringContainsString('required', $rules['question']);
        $this->assertStringContainsString('required', $rules['answer']);
        $this->assertStringContainsString('required', $rules['category']);
    }
}
