<?php

namespace Tests\Feature\Controllers;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'is_admin' => true,
        ]);
    }

    /**
     * @test
     */
    public function it_can_list_all_settings(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Flower API']);
        SiteSetting::create(['key' => 'contact_email', 'value' => 'test@example.com']);

        $response = $this->getJson('/api/site-settings');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
        $this->assertEquals('Flower API', $response->json('data.site_name'));
    }

    /**
     * @test
     */
    public function it_can_get_specific_setting(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Flower API']);

        $response = $this->getJson('/api/site-settings?key=site_name');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'data' => 'Flower API']);
    }

    /**
     * @test
     */
    public function it_returns_null_for_nonexistent_setting_key(): void
    {
        $response = $this->getJson('/api/site-settings?key=nonexistent');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'data' => null]);
    }

    /**
     * @test
     */
    public function it_can_update_a_setting(): void
    {
        SiteSetting::create(['key' => 'site_name', 'value' => 'Old Name']);

        $response = $this->actingAs($this->user)
            ->putJson('/api/site-settings', [
                'key' => 'site_name',
                'value' => 'New Flower Shop',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => '设置已更新']);

        $this->assertEquals('New Flower Shop', SiteSetting::getValue('site_name'));
    }

    /**
     * @test
     */
    public function it_can_create_setting_if_not_exists(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/site-settings', [
                'key' => 'new_setting',
                'value' => 'new_value',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('site_settings', ['key' => 'new_setting', 'value' => 'new_value']);
    }

    /**
     * @test
     */
    public function it_validates_required_key_on_update(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/site-settings', [
                'value' => 'some_value',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    /**
     * @test
     */
    public function it_can_batch_update_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/site-settings/batch', [
                'settings' => [
                    'site_name' => 'Flower Shop',
                    'contact_email' => 'contact@flower.com',
                    'phone' => '123456789',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => '设置已批量更新']);

        $this->assertEquals('Flower Shop', SiteSetting::getValue('site_name'));
        $this->assertEquals('contact@flower.com', SiteSetting::getValue('contact_email'));
        $this->assertEquals('123456789', SiteSetting::getValue('phone'));
    }

    /**
     * @test
     */
    public function it_validates_settings_array_on_batch_update(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/site-settings/batch', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['settings']);
    }
}
