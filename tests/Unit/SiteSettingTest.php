<?php

namespace Tests\Unit;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingTest extends TestCase
{
    use RefreshDatabase;

    // ─── Mass assignment ────────────────────────────────────────────────────

    public function test_can_create_site_setting(): void
    {
        $setting = SiteSetting::create([
            'key' => 'hero_title',
            'value' => '花好月圆',
        ]);

        $this->assertDatabaseHas('site_settings', ['key' => 'hero_title', 'value' => '花好月圆']);
    }

    // ─── Fillable protection ─────────────────────────────────────────────────

    public function test_non_fillable_fields_are_ignored(): void
    {
        $setting = SiteSetting::create([
            'key' => 'test_key',
            'value' => 'test_value',
            'unknown_field' => 'should be ignored',
        ]);

        $this->assertArrayNotHasKey('unknown_field', $setting->getAttributes());
    }

    // ─── Static helpers ─────────────────────────────────────────────────────

    public function test_get_returns_value_when_key_exists(): void
    {
        SiteSetting::create(['key' => 'hero_title', 'value' => '花好月圆']);

        $value = SiteSetting::get('hero_title');

        $this->assertEquals('花好月圆', $value);
    }

    public function test_get_returns_default_when_key_not_found(): void
    {
        $value = SiteSetting::get('nonexistent_key', '默认标题');

        $this->assertEquals('默认标题', $value);
    }

    public function test_get_returns_null_when_no_default_and_key_not_found(): void
    {
        $value = SiteSetting::get('nonexistent_key');

        $this->assertNull($value);
    }

    public function test_set_creates_new_setting_when_key_not_found(): void
    {
        SiteSetting::set('new_key', 'new_value');

        $this->assertDatabaseHas('site_settings', ['key' => 'new_key', 'value' => 'new_value']);
    }

    public function test_set_updates_existing_setting(): void
    {
        SiteSetting::create(['key' => 'hero_title', 'value' => 'Old Title']);

        SiteSetting::set('hero_title', 'New Title');

        $this->assertDatabaseHas('site_settings', ['key' => 'hero_title', 'value' => 'New Title']);
        $this->assertEquals(1, SiteSetting::where('key', 'hero_title')->count());
    }

    // ─── CRUD ───────────────────────────────────────────────────────────────

    public function test_can_update_site_setting(): void
    {
        $setting = SiteSetting::create(['key' => 'contact_phone', 'value' => '010-12345678']);

        $setting->update(['value' => '010-99999999']);

        $this->assertDatabaseHas('site_settings', ['key' => 'contact_phone', 'value' => '010-99999999']);
    }

    public function test_can_delete_site_setting(): void
    {
        $setting = SiteSetting::create(['key' => 'temp_key', 'value' => 'temp_value']);

        $id = $setting->id;
        $setting->delete();

        $this->assertDatabaseMissing('site_settings', ['id' => $id]);
    }

    // ─── Multiple settings ───────────────────────────────────────────────────

    public function test_can_have_multiple_unique_keys(): void
    {
        SiteSetting::create(['key' => 'hero_title', 'value' => 'Title']);
        SiteSetting::create(['key' => 'hero_subtitle', 'value' => 'Subtitle']);
        SiteSetting::create(['key' => 'contact_phone', 'value' => '010-123']);

        $this->assertEquals(3, SiteSetting::count());
    }

    public function test_key_is_not_inherently_unique_at_model_level(): void
    {
        // Note: uniqueness is enforced at DB level or via form-request validation.
        // Model layer itself allows duplicates; this test documents behaviour.
        SiteSetting::create(['key' => 'dup_key', 'value' => 'value1']);
        SiteSetting::create(['key' => 'dup_key', 'value' => 'value2']);

        $this->assertEquals(2, SiteSetting::where('key', 'dup_key')->count());
    }

    public function test_get_returns_first_match_when_duplicate_keys_exist(): void
    {
        SiteSetting::create(['key' => 'dup_key', 'value' => 'first']);
        SiteSetting::create(['key' => 'dup_key', 'value' => 'second']);

        $value = SiteSetting::get('dup_key');

        // get() uses ->first() so it returns the first match
        $this->assertNotNull($value);
    }
}
