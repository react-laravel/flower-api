<?php

namespace Tests\Unit\Models;

use App\Models\SiteSetting;
use PHPUnit\Framework\TestCase;

class SiteSettingTest extends TestCase
{
    public function test_it_can_be_instantiated(): void
    {
        $setting = new SiteSetting();
        $this->assertInstanceOf(SiteSetting::class, $setting);
    }
    public function test_fillable_attributes_include_key_and_value(): void
    {
        $setting = new SiteSetting();
        $fillable = $setting->getFillable();

        $this->assertContains('key', $fillable);
        $this->assertContains('value', $fillable);
    }
    public function test_get_method_returns_value_when_setting_exists(): void
    {
        // TODO: Requires database test - implement as Feature test with RefreshDatabase
        $this->markTestSkipped('Requires database - implement as Feature test');
    }
    public function test_get_method_returns_default_when_setting_not_found(): void
    {
        // TODO: Requires database test - implement as Feature test with RefreshDatabase
        $this->markTestSkipped('Requires database - implement as Feature test');
    }
    public function test_set_method_creates_or_updates_setting(): void
    {
        // TODO: Requires database test - implement as Feature test with RefreshDatabase
        $this->markTestSkipped('Requires database - implement as Feature test');
    }
}
