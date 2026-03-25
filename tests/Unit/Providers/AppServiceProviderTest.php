<?php

namespace Tests\Unit\Providers;

use App\Models\Category;
use App\Models\Flower;
use App\Models\Knowledge;
use App\Models\SiteSetting;
use App\Policies\CategoryPolicy;
use App\Policies\FlowerPolicy;
use App\Policies\KnowledgePolicy;
use App\Policies\SiteSettingPolicy;
use App\Policies\UploadPolicy;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Test for AppServiceProvider.
 *
 * This provider registers policies and bootstraps application services.
 */
class AppServiceProviderTest extends TestCase
{
    private AppServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new AppServiceProvider($this->app);
    }

    // ============================================================
    // Policy Mappings
    // ============================================================

    public function test_policies_array_maps_flower_to_flower_policy(): void
    {
        $reflection = new \ReflectionClass($this->provider);
        $property = $reflection->getProperty('policies');
        $property->setAccessible(true);
        $policies = $property->getValue($this->provider);

        $this->assertArrayHasKey(Flower::class, $policies);
        $this->assertEquals(FlowerPolicy::class, $policies[Flower::class]);
    }

    public function test_policies_array_maps_category_to_category_policy(): void
    {
        $reflection = new \ReflectionClass($this->provider);
        $property = $reflection->getProperty('policies');
        $property->setAccessible(true);
        $policies = $property->getValue($this->provider);

        $this->assertArrayHasKey(Category::class, $policies);
        $this->assertEquals(CategoryPolicy::class, $policies[Category::class]);
    }

    public function test_policies_array_maps_knowledge_to_knowledge_policy(): void
    {
        $reflection = new \ReflectionClass($this->provider);
        $property = $reflection->getProperty('policies');
        $property->setAccessible(true);
        $policies = $property->getValue($this->provider);

        $this->assertArrayHasKey(Knowledge::class, $policies);
        $this->assertEquals(KnowledgePolicy::class, $policies[Knowledge::class]);
    }

    public function test_policies_array_maps_site_setting_to_site_setting_policy(): void
    {
        $reflection = new \ReflectionClass($this->provider);
        $property = $reflection->getProperty('policies');
        $property->setAccessible(true);
        $policies = $property->getValue($this->provider);

        $this->assertArrayHasKey(SiteSetting::class, $policies);
        $this->assertEquals(SiteSettingPolicy::class, $policies[SiteSetting::class]);
    }

    // ============================================================
    // register()
    // ============================================================

    public function test_register_does_not_throw(): void
    {
        $this->provider->register();
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    // ============================================================
    // boot()
    // ============================================================

    public function test_boot_does_not_throw(): void
    {
        $this->provider->boot();
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function test_boot_defines_upload_gate(): void
    {
        $this->provider->boot();

        // Check that the 'upload' gate is defined
        $this->assertTrue(Gate::has('upload'));
    }

    public function test_boot_defines_upload_delete_gate(): void
    {
        $this->provider->boot();

        // Check that the 'upload.delete' gate is defined
        $this->assertTrue(Gate::has('upload.delete'));
    }

    public function test_policies_are_registered_after_boot(): void
    {
        $this->provider->boot();

        // Check that FlowerPolicy is registered for Flower model
        $this->assertTrue(Gate::has('viewAny', FlowerPolicy::class));
    }
}
