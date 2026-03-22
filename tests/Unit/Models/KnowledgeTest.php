<?php

namespace Tests\Unit\Models;

use App\Models\Knowledge;
use PHPUnit\Framework\TestCase;

class KnowledgeTest extends TestCase
{
    public function test_it_can_be_instantiated(): void
    {
        $knowledge = new Knowledge();
        $this->assertInstanceOf(Knowledge::class, $knowledge);
    }
    public function test_fillable_attributes_are_defined(): void
    {
        $knowledge = new Knowledge();
        $fillable = $knowledge->getFillable();

        $this->assertContains('question', $fillable);
        $this->assertContains('answer', $fillable);
        $this->assertContains('category', $fillable);
    }
}
