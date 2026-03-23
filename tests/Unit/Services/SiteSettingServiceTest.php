<?php

namespace Tests\Unit\Services;

use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function test_get_returns_value_when_setting_exists(): void
    {
        $key = fake()->word();
        SiteSetting::create(['key' => $key, 'value' => fake()->sentence()]);

        $result = $this->service->get($key);

        $this->assertEquals(SiteSetting::where('key', $key)->first()->value, $result);
    }

    #[Test]
    public function test_get_returns_default_when_setting_not_found(): void
    {
        $default = fake()->sentence();

        $result = $this->service->get(fake()->word(), $default);

        $this->assertEquals($default, $result);
    }

    #[Test]
    public function test_set_creates_new_setting(): void
    {
        $key = fake()->word();
        $value = fake()->sentence();

        $setting = $this->service->set($key, $value);

        $this->assertDatabaseHas('site_settings', [
            'key' => $key,
            'value' => $value,
        ]);
    }

    #[Test]
    public function test_set_updates_existing_setting(): void
    {
        $key = fake()->word();
        SiteSetting::create(['key' => $key, 'value' => fake()->sentence()]);
        $newValue = fake()->sentence();

        $this->service->set($key, $newValue);

        $this->assertDatabaseHas('site_settings', [
            'key' => $key,
            'value' => $newValue,
        ]);
        $this->assertEquals(1, SiteSetting::where('key', $key)->count());
    }

    #[Test]
    public function test_all_returns_collection_of_settings(): void
    {
        $key1 = fake()->word();
        $key2 = fake()->word();
        $val1 = fake()->sentence();
        $val2 = fake()->sentence();
        SiteSetting::create(['key' => $key1, 'value' => $val1]);
        SiteSetting::create(['key' => $key2, 'value' => $val2]);

        $result = $this->service->all();

        $this->assertEquals($val1, $result[$key1]);
        $this->assertEquals($val2, $result[$key2]);
    }

    #[Test]
    public function test_get_many_returns_values_for_specified_keys(): void
    {
        $key1 = fake()->word();
        $key2 = fake()->word();
        $key3 = fake()->word();
        $val1 = fake()->sentence();
        $val2 = fake()->sentence();
        $val3 = fake()->sentence();
        SiteSetting::create(['key' => $key1, 'value' => $val1]);
        SiteSetting::create(['key' => $key2, 'value' => $val2]);
        SiteSetting::create(['key' => $key3, 'value' => $val3]);

        $result = $this->service->getMany([$key1, $key3]);

        $this->assertEquals([$key1 => $val1, $key3 => $val3], $result);
    }

    #[Test]
    public function test_get_many_returns_null_for_missing_keys(): void
    {
        $key1 = fake()->word();
        $missingKey = fake()->word();
        $val1 = fake()->sentence();
        SiteSetting::create(['key' => $key1, 'value' => $val1]);

        $result = $this->service->getMany([$key1, $missingKey]);

        $this->assertEquals([$key1 => $val1, $missingKey => null], $result);
    }

    #[Test]
    public function test_batch_set_updates_multiple_settings(): void
    {
        $settings = [
            fake()->word() => fake()->sentence(),
            fake()->word() => fake()->sentence(),
        ];

        $this->service->batchSet($settings);

        foreach ($settings as $key => $value) {
            $this->assertDatabaseHas('site_settings', ['key' => $key, 'value' => $value]);
        }
    }

    #[Test]
    public function test_has_returns_true_when_setting_exists(): void
    {
        $key = fake()->word();
        SiteSetting::create(['key' => $key, 'value' => fake()->sentence()]);

        $this->assertTrue($this->service->has($key));
    }

    #[Test]
    public function test_has_returns_false_when_setting_not_exists(): void
    {
        $this->assertFalse($this->service->has(fake()->word()));
    }

    #[Test]
    public function test_delete_removes_setting(): void
    {
        $key = fake()->word();
        SiteSetting::create(['key' => $key, 'value' => fake()->sentence()]);

        $result = $this->service->delete($key);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('site_settings', ['key' => $key]);
    }

    #[Test]
    public function test_delete_returns_false_when_setting_not_found(): void
    {
        $result = $this->service->delete(fake()->word());

        $this->assertFalse($result);
    }
}
