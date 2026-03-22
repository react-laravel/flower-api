<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): array
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('admin')->plainTextToken;
        return ['admin' => $admin, 'token' => $token];
    }

    public function test_index_returns_all_settings(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Flower Shop']);
        SiteSetting::create(['key' => 'contact_email', 'value' => 'test@example.com']);

        $response = $this->getJson('/api/settings');

        $response->assertOk()
            ->assertJson(['success' => true]);
        $this->assertEquals('Flower Shop', $response->json('data.site_name'));
        $this->assertEquals('test@example.com', $response->json('data.contact_email'));
    }

    public function test_index_returns_specific_setting_by_key(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Flower Shop']);

        $response = $this->getJson('/api/settings?key=site_name');

        $response->assertOk()
            ->assertJson(['success' => true]);
        $this->assertEquals('Flower Shop', $response->json('data'));
    }

    public function test_update_creates_or_updates_setting(): void
    {
        $auth = $this->actingAsAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->putJson('/api/settings', [
                'key' => 'site_name',
                'value' => 'New Flower Shop',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '设置已更新']);

        $setting = SiteSetting::where('key', 'site_name')->first();
        $this->assertNotNull($setting);
        $this->assertEquals('New Flower Shop', $setting->value);
    }

    public function test_update_requires_key(): void
    {
        $auth = $this->actingAsAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->putJson('/api/settings', ['value' => 'test']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    public function test_batch_update_updates_multiple_settings(): void
    {
        $auth = $this->actingAsAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/settings/batch', [
                'settings' => [
                    'site_name' => 'Shop A',
                    'contact_email' => 'a@example.com',
                ],
            ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '设置已批量更新']);

        $siteName = SiteSetting::where('key', 'site_name')->first();
        $this->assertNotNull($siteName);
        $this->assertEquals('Shop A', $siteName->value);

        $email = SiteSetting::where('key', 'contact_email')->first();
        $this->assertNotNull($email);
        $this->assertEquals('a@example.com', $email->value);
    }

    public function test_batch_update_requires_settings_array(): void
    {
        $auth = $this->actingAsAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/settings/batch', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['settings']);
    }
}
