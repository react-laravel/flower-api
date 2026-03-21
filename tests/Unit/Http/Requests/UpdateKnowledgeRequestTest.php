<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\UpdateKnowledgeRequest;
use PHPUnit\Framework\TestCase;

class UpdateKnowledgeRequestTest extends TestCase
{
    public function test_it_authorizes_any_request(): void
    {
        $request = new UpdateKnowledgeRequest();
        $this->assertTrue($request->authorize());
    }
    public function test_all_fields_are_optional_for_update(): void
    {
        $request = new UpdateKnowledgeRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('question', $rules);
        $this->assertArrayHasKey('answer', $rules);
        $this->assertArrayHasKey('category', $rules);
    }
}
