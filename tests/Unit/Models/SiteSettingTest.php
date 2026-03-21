<?php

namespace Tests\Unit\Models;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_a_site_setting(): void
    {
        $setting = SiteSetting::create([
            'key' => 'site_name',
            'value' => 'Flower API',
        ]);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'site_name',
            'value' => 'Flower API',
        ]);

        $this->assertEquals('site_name', $setting->key);
        $this->assertEquals('Flower API', $setting->value);
    }

    /**
     * @test
     */
    public function it_can_get_setting_value(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Flower API']);
        SiteSetting::create(['key' => 'contact_email', 'value' => 'test@example.com']);

        $this->assertEquals('Flower API', SiteSetting::get('site_name'));
        $this->assertEquals('test@example.com', SiteSetting::get('contact_email'));
    }

    /**
     * @test
     */
    public function it_returns_default_when_setting_not_found(): void
    {
        $this->assertNull(SiteSetting::get('nonexistent'));
        $this->assertEquals('default', SiteSetting::get('nonexistent', 'default'));
    }

    /**
     * @test
     */
    public function it_can_set_setting_with_update_or_create(): void
    {
        SiteSetting::set('site_name', 'Flower API');

        $this->assertDatabaseHas('site_settings', [
            'key' => 'site_name',
            'value' => 'Flower API',
        ]);
    }

    /**
     * @test
     */
    public function it_can_update_existing_setting(): void
    {
        SiteSetting::set('site_name', 'Old Name');

        SiteSetting::set('site_name', 'New Name');

        $this->assertEquals('New Name', SiteSetting::get('site_name'));
        $this->assertEquals(1, SiteSetting::where('key', 'site_name')->count());
    }

    /**
     * @test
     */
    public function it_has_fillable_attributes(): void
    {
        $fillable = ['key', 'value'];

        $this->assertEquals($fillable, (new SiteSetting)->getFillable());
    }

    /**
     * @test
     */
    public function it_can_delete_setting(): void
    {
        $setting = SiteSetting::create(['key' => 'site_name', 'value' => 'Flower API']);

        $setting->delete();

        $this->assertNull(SiteSetting::get('site_name'));
    }

    /**
     * @test
     */
    public function it_can_set_null_value(): void
    {
        SiteSetting::set('optional_setting', null);

        $this->assertNull(SiteSetting::get('optional_setting'));
    }
}
