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

    public function test_index_rejects_sensitive_keys_in_query_parameter(): void
    {
        SiteSetting::create(['key' => 'smtp_password', 'value' => 'secret123']);

        $response = $this->getJson('/api/settings?key=smtp_password');

        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => '无效的设置键']);
    }

    public function test_index_rejects_aws_sensitive_keys(): void
    {
        SiteSetting::create(['key' => 'aws_access_key', 'value' => 'AKIAIOSFODNN7EXAMPLE']);

        $response = $this->getJson('/api/settings?key=aws_access_key');

        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => '无效的设置键']);
    }

    public function test_index_rejects_password_in_key_name(): void
    {
        SiteSetting::create(['key' => 'database_password', 'value' => 'mysecretpass']);

        $response = $this->getJson('/api/settings?key=database_password');

        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => '无效的设置键']);
    }

    public function test_index_rejects_secret_key(): void
    {
        SiteSetting::create(['key' => 'api_secret', 'value' => 'secretvalue']);

        $response = $this->getJson('/api/settings?key=api_secret');

        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => '无效的设置键']);
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

    /**
     * @dataProvider sensitiveKeyPatternsProvider
     */
    public function test_index_rejects_expanded_sensitive_key_patterns_in_query(string $sensitiveKey): void
    {
        SiteSetting::create(['key' => $sensitiveKey, 'value' => 'sensitive-value']);

        $response = $this->getJson("/api/settings?key={$sensitiveKey}");

        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => '无效的设置键']);
    }

    /**
     * @dataProvider sensitiveKeyPatternsProvider
     */
    public function test_index_excludes_expanded_sensitive_patterns_from_bulk_response(string $sensitiveKey): void
    {
        SiteSetting::create(['key' => $sensitiveKey, 'value' => 'sensitive-value']);
        SiteSetting::create(['key' => 'site_name', 'value' => 'Flower Shop']);

        $response = $this->getJson('/api/settings');

        $response->assertOk();
        $this->assertArrayNotHasKey($sensitiveKey, $response->json('data'));
        $this->assertEquals('Flower Shop', $response->json('data.site_name'));
    }

    public static function sensitiveKeyPatternsProvider(): array
    {
        return [
            'stripe_api_key' => ['stripe_api_key'],
            'sendgrid_api_key' => ['sendgrid_api_key'],
            'twilio_auth_token' => ['twilio_auth_token'],
            'mailchimp_api_key' => ['mailchimp_api_key'],
            'github_token' => ['github_token'],
            'openai_api_key' => ['openai_api_key'],
            'slack_webhook_url' => ['slack_webhook_url'],
            'google_recaptcha_secret' => ['google_recaptcha_secret'],
            'jwt_secret' => ['jwt_secret'],
            'private_key' => ['private_key'],
            'encryption_key' => ['encryption_key'],
            'paypal_client_secret' => ['paypal_client_secret'],
            'facebook_access_token' => ['facebook_access_token'],
        ];
    }
}
