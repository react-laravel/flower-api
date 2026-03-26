<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->regularUser = User::factory()->create(['is_admin' => false]);
        $this->seedSettings();
    }

    protected function seedSettings(): void
    {
        SiteSetting::create(['key' => 'hero_title', 'value' => '花好月圆']);
        SiteSetting::create(['key' => 'hero_subtitle', 'value' => '用花讲述爱的故事']);
        SiteSetting::create(['key' => 'contact_phone', 'value' => '010-12345678']);
    }

    // ─── Public read ─────────────────────────────────────────────────────────

    public function test_settings_index_returns_all_settings(): void
    {
        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertEquals('花好月圆', $data['hero_title']);
        $this->assertEquals('用花讲述爱的故事', $data['hero_subtitle']);
        $this->assertEquals('010-12345678', $data['contact_phone']);
    }

    public function test_settings_index_with_key_returns_specific_setting(): void
    {
        $response = $this->getJson('/api/settings?key=hero_title');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('花好月圆', $response->json('data'));
    }

    public function test_settings_index_with_nonexistent_key_returns_null(): void
    {
        $response = $this->getJson('/api/settings?key=nonexistent_key');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNull($response->json('data'));
    }

    public function test_settings_is_public_no_auth_required(): void
    {
        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // ─── Admin write ────────────────────────────────────────────────────────

    public function test_admin_can_update_setting(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/settings', [
                'key' => 'hero_title',
                'value' => '繁花似锦',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '设置已更新',
            ]);

        $this->assertEquals('繁花似锦', SiteSetting::get('hero_title'));
    }

    public function test_admin_can_create_new_setting(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/settings', [
                'key' => 'new_setting_key',
                'value' => 'New Setting Value',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '设置已更新',
            ]);

        $this->assertEquals('New Setting Value', SiteSetting::get('new_setting_key'));
    }

    public function test_admin_can_batch_update_settings(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/settings/batch', [
                'settings' => [
                    'hero_title' => 'Batch Title',
                    'hero_subtitle' => 'Batch Subtitle',
                    'contact_email' => 'test@example.com',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '设置已批量更新',
            ]);

        $this->assertEquals('Batch Title', SiteSetting::get('hero_title'));
        $this->assertEquals('Batch Subtitle', SiteSetting::get('hero_subtitle'));
        $this->assertEquals('test@example.com', SiteSetting::get('contact_email'));
    }

    public function test_update_setting_requires_key_field(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/settings', [
                'value' => 'Some Value',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    public function test_batch_update_requires_settings_array(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/settings/batch', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['settings']);
    }

    // ─── Regular user cannot modify settings ───────────────────────────────

    public function test_regular_user_cannot_update_setting(): void
    {
        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->putJson('/api/settings', [
                'key' => 'hero_title',
                'value' => 'Hacked Title',
            ]);

        $response->assertStatus(403);

        $this->assertEquals('花好月圆', SiteSetting::get('hero_title'));
    }

    public function test_regular_user_cannot_batch_update_settings(): void
    {
        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->postJson('/api/settings/batch', [
                'settings' => ['hero_title' => 'Hacked'],
            ]);

        $response->assertStatus(403);

        $this->assertEquals('花好月圆', SiteSetting::get('hero_title'));
    }

    public function test_unauthenticated_cannot_update_setting(): void
    {
        $response = $this->putJson('/api/settings', [
            'key' => 'hero_title',
            'value' => 'Hacked',
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_cannot_batch_update_settings(): void
    {
        $response = $this->postJson('/api/settings/batch', [
            'settings' => ['hero_title' => 'Hacked'],
        ]);

        $response->assertStatus(401);
    }
}
