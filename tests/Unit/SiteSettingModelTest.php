<?php

namespace Tests\Unit;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_setting_can_be_created(): void
    {
        $setting = SiteSetting::create([
            'key' => 'site_name',
            'value' => 'Flower Shop',
        ]);

        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => 'Flower Shop']);
        $this->assertEquals('Flower Shop', $setting->value);
    }

    public function test_site_setting_get_value_returns_value(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'My Flower Shop']);

        $value = SiteSetting::getValue('site_name');

        $this->assertEquals('My Flower Shop', $value);
    }

    public function test_site_setting_get_value_returns_default_for_missing_key(): void
    {
        $value = SiteSetting::getValue('nonexistent', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    public function test_site_setting_set_value_creates_new_setting(): void
    {
        SiteSetting::setValue('new_key', 'new_value');

        $this->assertDatabaseHas('site_settings', ['key' => 'new_key', 'value' => 'new_value']);
    }

    public function test_site_setting_set_value_updates_existing_setting(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Old Name']);

        SiteSetting::setValue('site_name', 'Updated Name');

        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => 'Updated Name']);
        $this->assertEquals(1, SiteSetting::where('key', 'site_name')->count());
    }

    public function test_site_setting_fillable_attributes(): void
    {
        $fillable = (new SiteSetting())->getFillable();

        $this->assertContains('key', $fillable);
        $this->assertContains('value', $fillable);
    }
}
