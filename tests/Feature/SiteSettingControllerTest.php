<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SiteSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_returns_all_settings(): void
    {
        SiteSetting::factory()->count(3)->create();

        $response = $this->getJson('/api/settings');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data']);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_index_returns_settings_as_key_value_pairs(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Flower Shop']);
        SiteSetting::create(['key' => 'contact_email', 'value' => 'hello@flower.com']);

        $response = $this->getJson('/api/settings');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('Flower Shop', $data['site_name']);
        $this->assertEquals('hello@flower.com', $data['contact_email']);
    }

    public function test_index_returns_single_setting_by_key(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Flower Shop']);

        $response = $this->getJson('/api/settings?key=site_name');

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => 'Flower Shop']);
    }

    public function test_index_returns_null_for_missing_key(): void
    {
        $response = $this->getJson('/api/settings?key=nonexistent');

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => null]);
    }

    public function test_update_creates_or_updates_setting(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson('/api/settings', [
            'key' => 'site_name',
            'value' => 'My Flower Shop',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '设置已更新']);
        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => 'My Flower Shop']);
    }

    public function test_update_updates_existing_setting(): void
    {
        Sanctum::actingAs($this->admin);
        SiteSetting::create(['key' => 'site_name', 'value' => 'Old Name']);

        $response = $this->putJson('/api/settings', [
            'key' => 'site_name',
            'value' => 'New Name',
        ]);

        $response->assertOk();
        $this->assertEquals(1, SiteSetting::where('key', 'site_name')->where('value', 'New Name')->count());
    }

    public function test_update_requires_key_field(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson('/api/settings', ['value' => 'Test']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    public function test_batch_update_updates_multiple_settings(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson('/api/settings/batch', [
            'settings' => [
                'site_name' => 'Flower Shop',
                'contact_email' => 'hello@flower.com',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '设置已批量更新']);
        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => 'Flower Shop']);
        $this->assertDatabaseHas('site_settings', ['key' => 'contact_email', 'value' => 'hello@flower.com']);
    }

    public function test_batch_update_requires_settings_array(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson('/api/settings/batch', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['settings']);
    }
}
