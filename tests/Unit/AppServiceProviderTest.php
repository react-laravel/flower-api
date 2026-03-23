<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Flower;
use App\Models\Knowledge;
use App\Models\SiteSetting;
use App\Policies\CategoryPolicy;
use App\Policies\FlowerPolicy;
use App\Policies\KnowledgePolicy;
use App\Policies\SiteSettingPolicy;
use App\Policies\UploadPolicy;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    /**
     * Test that FlowerPolicy is registered for Flower model
     */
    public function test_flower_policy_is_registered(): void
    {
        $policy = Gate::getPolicy(Flower::class);

        $this->assertInstanceOf(FlowerPolicy::class, $policy);
    }

    /**
     * Test that CategoryPolicy is registered for Category model
     */
    public function test_category_policy_is_registered(): void
    {
        $policy = Gate::getPolicy(Category::class);

        $this->assertInstanceOf(CategoryPolicy::class, $policy);
    }

    /**
     * Test that KnowledgePolicy is registered for Knowledge model
     */
    public function test_knowledge_policy_is_registered(): void
    {
        $policy = Gate::getPolicy(Knowledge::class);

        $this->assertInstanceOf(KnowledgePolicy::class, $policy);
    }

    /**
     * Test that SiteSettingPolicy is registered for SiteSetting model
     */
    public function test_site_setting_policy_is_registered(): void
    {
        $policy = Gate::getPolicy(SiteSetting::class);

        $this->assertInstanceOf(SiteSettingPolicy::class, $policy);
    }

    /**
     * Test that upload gate is defined
     */
    public function test_upload_gate_is_defined(): void
    {
        $this->assertTrue(Gate::has('upload'));
    }

    /**
     * Test that upload.delete gate is defined
     */
    public function test_upload_delete_gate_is_defined(): void
    {
        $this->assertTrue(Gate::has('upload.delete'));
    }
}
