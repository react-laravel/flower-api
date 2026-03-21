<?php

namespace Tests\Unit\Services;

use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SiteSettingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SiteSettingService();
    }

    public function test_get_returns_value_when_setting_exists(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Flower Shop']);

        $result = $this->service->get('site_name');

        $this->assertEquals('Flower Shop', $result);
    }

    public function test_get_returns_default_when_setting_not_found(): void
    {
        $result = $this->service->get('nonexistent_key', 'default_value');

        $this->assertEquals('default_value', $result);
    }

    public function test_set_creates_new_setting(): void
    {
        $setting = $this->service->set('new_key', 'new_value');

        $this->assertDatabaseHas('site_settings', [
            'key' => 'new_key',
            'value' => 'new_value',
        ]);
    }

    public function test_set_updates_existing_setting(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Old Name']);

        $this->service->set('site_name', 'New Name');

        $this->assertDatabaseHas('site_settings', [
            'key' => 'site_name',
            'value' => 'New Name',
        ]);
        $this->assertEquals(1, SiteSetting::where('key', 'site_name')->count());
    }

    public function test_all_returns_collection_of_settings(): void
    {
        SiteSetting::create(['key' => 'key1', 'value' => 'value1']);
        SiteSetting::create(['key' => 'key2', 'value' => 'value2']);

        $result = $this->service->all();

        $this->assertEquals('value1', $result['key1']);
        $this->assertEquals('value2', $result['key2']);
    }

    public function test_get_many_returns_values_for_specified_keys(): void
    {
        SiteSetting::create(['key' => 'key1', 'value' => 'value1']);
        SiteSetting::create(['key' => 'key2', 'value' => 'value2']);
        SiteSetting::create(['key' => 'key3', 'value' => 'value3']);

        $result = $this->service->getMany(['key1', 'key3']);

        $this->assertEquals(['key1' => 'value1', 'key3' => 'value3'], $result);
    }

    public function test_get_many_returns_null_for_missing_keys(): void
    {
        SiteSetting::create(['key' => 'key1', 'value' => 'value1']);

        $result = $this->service->getMany(['key1', 'nonexistent']);

        $this->assertEquals(['key1' => 'value1', 'nonexistent' => null], $result);
    }

    public function test_batch_set_updates_multiple_settings(): void
    {
        $this->service->batchSet([
            'setting1' => 'value1',
            'setting2' => 'value2',
        ]);

        $this->assertDatabaseHas('site_settings', ['key' => 'setting1', 'value' => 'value1']);
        $this->assertDatabaseHas('site_settings', ['key' => 'setting2', 'value' => 'value2']);
    }

    public function test_has_returns_true_when_setting_exists(): void
    {
        SiteSetting::create(['key' => 'test_key', 'value' => 'test_value']);

        $this->assertTrue($this->service->has('test_key'));
    }

    public function test_has_returns_false_when_setting_not_exists(): void
    {
        $this->assertFalse($this->service->has('nonexistent_key'));
    }

    public function test_delete_removes_setting(): void
    {
        SiteSetting::create(['key' => 'to_delete', 'value' => 'value']);

        $result = $this->service->delete('to_delete');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('site_settings', ['key' => 'to_delete']);
    }

    public function test_delete_returns_false_when_setting_not_found(): void
    {
        $result = $this->service->delete('nonexistent_key');

        $this->assertFalse($result);
    }
}
