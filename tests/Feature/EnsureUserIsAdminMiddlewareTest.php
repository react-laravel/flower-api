<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureUserIsAdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_admin_user(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/settings');

        // Should pass (not 403)
        $this->assertNotEquals(403, $response->status());
    }

    public function test_blocks_non_admin_user(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/settings', ['key' => 'test', 'value' => 'test']);

        $response->assertStatus(403)
            ->assertJson(['success' => false, 'message' => '需要管理员权限']);
    }

    public function test_blocks_unauthenticated_user(): void
    {
        $response = $this->putJson('/api/settings', ['key' => 'test', 'value' => 'test']);
        $response->assertStatus(401);
    }

    public function test_admin_user_can_access_protected_route(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/settings/batch', ['settings' => ['key' => 'value']]);

        $this->assertNotSame(403, $response->status());
    }

    public function test_regular_user_cannot_access_admin_route(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/settings/batch', ['settings' => ['key' => 'value']]);

        $response->assertStatus(403);
    }
}
