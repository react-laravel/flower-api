<?php

namespace Tests\Unit\Models;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SiteSettingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_create_setting(): void
    {
        // Arrange & Act
        $setting = SiteSetting::create(['key' => 'site_name', 'value' => 'Flower Shop']);

        // Assert
        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => 'Flower Shop']);
    }

    #[Test]
    public function key_is_fillable(): void
    {
        // Arrange & Act
        $setting = SiteSetting::create(['key' => 'contact_email', 'value' => 'test@example.com']);

        // Assert
        $this->assertEquals('contact_email', $setting->key);
    }

    #[Test]
    public function value_is_fillable(): void
    {
        // Arrange & Act
        $setting = SiteSetting::create(['key' => 'tagline', 'value' => 'Beautiful Flowers']);

        // Assert
        $this->assertEquals('Beautiful Flowers', $setting->value);
    }

    #[Test]
    public function get_value_returns_value_when_exists(): void
    {
        // Arrange
        SiteSetting::create(['key' => 'site_name', 'value' => 'Flower Store']);

        // Act
        $result = SiteSetting::getValue('site_name');

        // Assert
        $this->assertEquals('Flower Store', $result);
    }

    #[Test]
    public function get_value_returns_default_when_not_exists(): void
    {
        // Act & Assert
        $this->assertNull(SiteSetting::getValue('nonexistent'));
        $this->assertEquals('default', SiteSetting::getValue('nonexistent', 'default'));
    }

    #[Test]
    public function get_value_caches_result(): void
    {
        // Arrange
        $key = 'cached_key';
        SiteSetting::create(['key' => $key, 'value' => 'first_value']);

        // Act
        $first = SiteSetting::getValue($key);

        // Assert — cache should be populated
        $this->assertEquals('first_value', $first);
    }

    #[Test]
    public function set_value_creates_new_setting(): void
    {
        // Arrange & Act
        SiteSetting::setValue('new_key', 'new_value');

        // Assert
        $this->assertDatabaseHas('site_settings', ['key' => 'new_key', 'value' => 'new_value']);
    }

    #[Test]
    public function set_value_updates_existing_setting(): void
    {
        // Arrange
        SiteSetting::create(['key' => 'site_name', 'value' => 'Old Name']);

        // Act
        SiteSetting::setValue('site_name', 'New Name');

        // Assert
        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => 'New Name']);
        $this->assertEquals(1, SiteSetting::where('key', 'site_name')->count());
    }

    #[Test]
    public function set_value_returns_model_instance(): void
    {
        // Arrange & Act
        $result = SiteSetting::setValue('site_name', 'Test Name');

        // Assert
        $this->assertInstanceOf(SiteSetting::class, $result);
    }

    #[Test]
    public function set_value_invalidates_cache(): void
    {
        // Arrange
        $key = 'flush_key';
        SiteSetting::setValue($key, 'original');

        // Act
        SiteSetting::setValue($key, 'updated');

        // Assert — cache should be refreshed with new value
        $this->assertEquals('updated', SiteSetting::getValue($key));
    }
}
