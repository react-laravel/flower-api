<?php

namespace Tests\Unit\Traits;

use App\Http\Traits\ApiResponse;
use App\Http\Traits\CrudOperations;
use App\Http\Traits\Idempotency;
use App\Models\Flower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test for CrudOperations trait.
 *
 * This trait provides shared CRUD operation helpers for resource controllers.
 * Note: Full CRUD methods (store/update/destroy) require controller context
 * with authorization gates and are tested in Feature tests.
 */
class CrudOperationsTest extends TestCase
{
    use ApiResponse, Idempotency, CrudOperations;

    protected static function getModelClass(): string
    {
        return Flower::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->initIdempotency();
    }

    // ============================================================
    // getModelClass()
    // ============================================================

    public function test_get_model_class_returns_configured_class(): void
    {
        $this->assertEquals(Flower::class, static::getModelClass());
    }

    // ============================================================
    // getModelShortName()
    // ============================================================

    public function test_get_model_short_name_returns_flower(): void
    {
        $this->assertEquals('Flower', static::getModelShortName());
    }

    // ============================================================
    // getStoreRequestClass()
    // ============================================================

    public function test_get_store_request_class_returns_store_flower_request(): void
    {
        $this->assertEquals('App\\Http\\Requests\\StoreFlowerRequest', static::getStoreRequestClass());
    }

    // ============================================================
    // getUpdateRequestClass()
    // ============================================================

    public function test_get_update_request_class_returns_update_flower_request(): void
    {
        $this->assertEquals('App\\Http\\Requests\\UpdateFlowerRequest', static::getUpdateRequestClass());
    }

    // ============================================================
    // Static helper methods for different models
    // ============================================================

    public function test_get_model_short_name_works_for_category(): void
    {
        $testClass = new class {
            use CrudOperations;
            public static function getModelClass(): string
            {
                return \App\Models\Category::class;
            }
        };

        $this->assertEquals('Category', $testClass::getModelShortName());
        $this->assertEquals('App\\Http\\Requests\\StoreCategoryRequest', $testClass::getStoreRequestClass());
        $this->assertEquals('App\\Http\\Requests\\UpdateCategoryRequest', $testClass::getUpdateRequestClass());
    }

    public function test_get_model_short_name_works_for_knowledge(): void
    {
        $testClass = new class {
            use CrudOperations;
            public static function getModelClass(): string
            {
                return \App\Models\Knowledge::class;
            }
        };

        $this->assertEquals('Knowledge', $testClass::getModelShortName());
        $this->assertEquals('App\\Http\\Requests\\StoreKnowledgeRequest', $testClass::getStoreRequestClass());
        $this->assertEquals('App\\Http\\Requests\\UpdateKnowledgeRequest', $testClass::getUpdateRequestClass());
    }

    // ============================================================
    // Request class existence
    // ============================================================

    public function test_store_flower_request_class_exists(): void
    {
        $this->assertTrue(class_exists('App\\Http\\Requests\\StoreFlowerRequest'));
    }

    public function test_update_flower_request_class_exists(): void
    {
        $this->assertTrue(class_exists('App\\Http\\Requests\\UpdateFlowerRequest'));
    }

    public function test_store_category_request_class_exists(): void
    {
        $this->assertTrue(class_exists('App\\Http\\Requests\\StoreCategoryRequest'));
    }

    public function test_update_category_request_class_exists(): void
    {
        $this->assertTrue(class_exists('App\\Http\\Requests\\UpdateCategoryRequest'));
    }
}
