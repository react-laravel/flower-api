<?php

namespace Tests\Unit\Models;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_setting(): void
    {
        $setting = SiteSetting::create(['key' => 'site_name', 'value' => 'Flower Shop']);
        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => 'Flower Shop']);
    }

    public function test_key_is_fillable(): void
    {
        $setting = SiteSetting::create(['key' => 'contact_email', 'value' => 'test@example.com']);
        $this->assertEquals('contact_email', $setting->key);
    }

    public function test_value_is_fillable(): void
    {
        $setting = SiteSetting::create(['key' => 'tagline', 'value' => 'Beautiful Flowers']);
        $this->assertEquals('Beautiful Flowers', $setting->value);
    }

    public function test_get_returns_value_when_exists(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Flower Store']);
        $this->assertEquals('Flower Store', SiteSetting::get('site_name'));
    }

    public function test_get_returns_default_when_not_exists(): void
    {
        $this->assertNull(SiteSetting::get('nonexistent'));
        $this->assertEquals('default', SiteSetting::get('nonexistent', 'default'));
    }

    public function test_set_creates_new_setting(): void
    {
        SiteSetting::set('new_key', 'new_value');
        $this->assertDatabaseHas('site_settings', ['key' => 'new_key', 'value' => 'new_value']);
    }

    public function test_set_updates_existing_setting(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Old Name']);
        SiteSetting::set('site_name', 'New Name');

        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => 'New Name']);
        $this->assertEquals(1, SiteSetting::where('key', 'site_name')->count());
    }

    public function test_set_returns_model_instance(): void
    {
        $result = SiteSetting::set('site_name', 'Test Name');
        $this->assertInstanceOf(SiteSetting::class, $result);
    }
}
