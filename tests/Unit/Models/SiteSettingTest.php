<?php

namespace Tests\Unit\Models;

use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $service = new SiteSettingService();
        SiteSetting::create(['key' => 'site_name', 'value' => 'Flower Store']);

        // Act
        $result = $service->get('site_name');

        // Assert
        $this->assertEquals('Flower Store', $result);
    }

    #[Test]
    public function get_value_returns_default_when_not_exists(): void
    {
        // Arrange
        $service = new SiteSettingService();

        // Act & Assert
        $this->assertNull($service->get('nonexistent'));
        $this->assertEquals('default', $service->get('nonexistent', 'default'));
    }

    #[Test]
    public function set_value_creates_new_setting(): void
    {
        // Arrange
        $service = new SiteSettingService();

        // Act
        $service->set('new_key', 'new_value');

        // Assert
        $this->assertDatabaseHas('site_settings', ['key' => 'new_key', 'value' => 'new_value']);
    }

    #[Test]
    public function set_value_updates_existing_setting(): void
    {
        // Arrange
        $service = new SiteSettingService();
        SiteSetting::create(['key' => 'site_name', 'value' => 'Old Name']);

        // Act
        $service->set('site_name', 'New Name');

        // Assert
        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => 'New Name']);
        $this->assertEquals(1, SiteSetting::where('key', 'site_name')->count());
    }

    #[Test]
    public function set_value_returns_model_instance(): void
    {
        // Arrange
        $service = new SiteSettingService();

        // Act
        $result = $service->set('site_name', 'Test Name');

        // Assert
        $this->assertInstanceOf(SiteSetting::class, $result);
    }

    #[Test]
    public function has_returns_true_when_key_exists(): void
    {
        // Arrange
        $service = new SiteSettingService();
        SiteSetting::create(['key' => 'existing_key', 'value' => 'value']);

        // Act & Assert
        $this->assertTrue($service->has('existing_key'));
        $this->assertFalse($service->has('nonexistent_key'));
    }

    #[Test]
    public function delete_removes_setting(): void
    {
        // Arrange
        $service = new SiteSettingService();
        SiteSetting::create(['key' => 'to_delete', 'value' => 'value']);

        // Act
        $result = $service->delete('to_delete');

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('site_settings', ['key' => 'to_delete']);
    }

    #[Test]
    public function get_many_returns_multiple_values(): void
    {
        // Arrange
        $service = new SiteSettingService();
        SiteSetting::create(['key' => 'key1', 'value' => 'value1']);
        SiteSetting::create(['key' => 'key2', 'value' => 'value2']);

        // Act
        $result = $service->getMany(['key1', 'key2', 'key3']);

        // Assert
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2', 'key3' => null], $result);
    }

    #[Test]
    public function all_returns_collection(): void
    {
        // Arrange
        $service = new SiteSettingService();
        SiteSetting::create(['key' => 'key1', 'value' => 'value1']);
        SiteSetting::create(['key' => 'key2', 'value' => 'value2']);

        // Act
        $result = $service->all();

        // Assert
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $result->toArray());
    }
}
