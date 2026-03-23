<?php

namespace Tests\Unit\Services;

use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SiteSettingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SiteSettingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SiteSettingService();
        Cache::flush();
    }

    public function test_get_returns_cached_value(): void
    {
        SiteSetting::factory()->create(['key' => 'site_name', 'value' => 'Flower Shop']);

        $result = $this->service->get('site_name');

        $this->assertEquals('Flower Shop', $result);
    }

    public function test_get_returns_default_for_missing_key(): void
    {
        $result = $this->service->get('nonexistent_key', 'default_value');

        $this->assertEquals('default_value', $result);
    }

    public function test_get_returns_null_when_no_default(): void
    {
        $result = $this->service->get('nonexistent_key');

        $this->assertNull($result);
    }

    public function test_set_creates_new_setting(): void
    {
        $result = $this->service->set('site_name', 'New Flower Shop');

        $this->assertInstanceOf(SiteSetting::class, $result);
        $this->assertEquals('site_name', $result->key);
        $this->assertEquals('New Flower Shop', $result->value);
    }

    public function test_set_updates_existing_setting(): void
    {
        SiteSetting::factory()->create(['key' => 'site_name', 'value' => 'Old Name']);

        $result = $this->service->set('site_name', 'Updated Name');

        $this->assertEquals('Updated Name', $result->value);
        $this->assertEquals(1, SiteSetting::count());
    }

    public function test_set_invalidates_cache(): void
    {
        SiteSetting::factory()->create(['key' => 'site_name', 'value' => 'Original']);
        $this->service->get('site_name'); // warm up cache

        $this->service->set('site_name', 'Updated');

        // Cache should be invalidated; next get returns new value
        $this->assertEquals('Updated', $this->service->get('site_name'));
    }

    public function test_all_returns_key_value_pairs(): void
    {
        SiteSetting::factory()->create(['key' => 'site_name', 'value' => 'Flower Shop']);
        SiteSetting::factory()->create(['key' => 'site_email', 'value' => 'test@example.com']);

        $result = $this->service->all();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertEquals('Flower Shop', $result['site_name']);
        $this->assertEquals('test@example.com', $result['site_email']);
    }

    public function test_get_many_returns_values_for_multiple_keys(): void
    {
        SiteSetting::factory()->create(['key' => 'key1', 'value' => 'value1']);
        SiteSetting::factory()->create(['key' => 'key2', 'value' => 'value2']);

        $result = $this->service->getMany(['key1', 'key2']);

        $this->assertEquals('value1', $result['key1']);
        $this->assertEquals('value2', $result['key2']);
    }

    public function test_get_many_returns_null_for_missing_keys(): void
    {
        SiteSetting::factory()->create(['key' => 'existing_key', 'value' => 'exists']);

        $result = $this->service->getMany(['existing_key', 'missing_key']);

        $this->assertEquals('exists', $result['existing_key']);
        $this->assertNull($result['missing_key']);
    }

    public function test_get_many_preserves_key_order(): void
    {
        SiteSetting::factory()->create(['key' => 'a_key', 'value' => 'a']);
        SiteSetting::factory()->create(['key' => 'b_key', 'value' => 'b']);

        $result = $this->service->getMany(['b_key', 'a_key']);

        $this->assertEquals(['b_key', 'a_key'], array_keys($result));
    }

    public function test_get_many_returns_empty_array_for_empty_input(): void
    {
        $result = $this->service->getMany([]);

        $this->assertEquals([], $result);
    }

    public function test_batch_set_creates_or_updates_multiple_settings(): void
    {
        SiteSetting::factory()->create(['key' => 'existing', 'value' => 'old']);

        $this->service->batchSet([
            'existing' => 'updated',
            'new_key' => 'new_value',
        ]);

        $this->assertEquals(2, SiteSetting::count());
        $this->assertEquals('updated', SiteSetting::where('key', 'existing')->first()->value);
        $this->assertEquals('new_value', SiteSetting::where('key', 'new_key')->first()->value);
    }

    public function test_batch_set_invalidates_cache_for_each_key(): void
    {
        SiteSetting::factory()->create(['key' => 'k1', 'value' => 'v1']);
        SiteSetting::factory()->create(['key' => 'k2', 'value' => 'v2']);

        $this->service->batchSet(['k1' => 'v1_updated', 'k2' => 'v2_updated']);

        $this->assertEquals('v1_updated', $this->service->get('k1'));
        $this->assertEquals('v2_updated', $this->service->get('k2'));
    }

    public function test_has_returns_true_for_existing_key(): void
    {
        SiteSetting::factory()->create(['key' => 'site_name', 'value' => 'Flower Shop']);

        $this->assertTrue($this->service->has('site_name'));
    }

    public function test_has_returns_false_for_missing_key(): void
    {
        $this->assertFalse($this->service->has('nonexistent'));
    }

    public function test_delete_removes_setting(): void
    {
        SiteSetting::factory()->create(['key' => 'to_delete', 'value' => 'delete me']);

        $result = $this->service->delete('to_delete');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('site_settings', ['key' => 'to_delete']);
    }

    public function test_delete_returns_false_for_missing_key(): void
    {
        $result = $this->service->delete('nonexistent');

        $this->assertFalse($result);
    }

    public function test_delete_invalidates_cache(): void
    {
        SiteSetting::factory()->create(['key' => 'to_delete', 'value' => 'delete me']);
        $this->service->get('to_delete'); // warm up cache

        $this->service->delete('to_delete');

        $this->assertFalse($this->service->has('to_delete'));
    }
}
