<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_settings(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Flower Shop']);
        SiteSetting::create(['key' => 'tagline', 'value' => 'Beautiful']);

        $response = $this->getJson('/api/settings');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.site_name', 'Flower Shop')
            ->assertJsonPath('data.tagline', 'Beautiful');
    }

    public function test_index_returns_empty_object_when_no_settings(): void
    {
        $response = $this->getJson('/api/settings');

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => []]);
    }

    public function test_index_returns_specific_setting_by_key(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Flower Store']);

        $response = $this->getJson('/api/settings?key=site_name');

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => 'Flower Store']);
    }

    public function test_index_returns_null_for_nonexistent_key(): void
    {
        $response = $this->getJson('/api/settings?key=nonexistent');

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => null]);
    }

    public function test_update_creates_or_updates_setting(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/settings', [
                'key' => 'site_name',
                'value' => 'New Flower Shop',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '设置已更新']);
        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => 'New Flower Shop']);
    }

    public function test_update_requires_authentication(): void
    {
        $response = $this->putJson('/api/settings', ['key' => 'site_name', 'value' => 'Test']);
        $response->assertStatus(401);
    }

    public function test_update_requires_key(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/settings', ['value' => 'Test']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    public function test_update_accepts_null_value(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/settings', ['key' => 'nullable_key', 'value' => null]);

        $response->assertOk();
    }

    public function test_batch_update_updates_multiple_settings(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/settings/batch', [
                'settings' => [
                    'site_name' => 'Shop A',
                    'tagline' => 'Fresh Flowers',
                ],
            ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '设置已批量更新']);
        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => 'Shop A']);
        $this->assertDatabaseHas('site_settings', ['key' => 'tagline', 'value' => 'Fresh Flowers']);
    }

    public function test_batch_update_requires_authentication(): void
    {
        $response = $this->postJson('/api/settings/batch', [
            'settings' => ['key' => 'value'],
        ]);
        $response->assertStatus(401);
    }

    public function test_batch_update_requires_settings_array(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/settings/batch', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['settings']);
    }
}
