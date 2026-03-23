<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ]
            ])
            ->assertJson(['success' => true]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ]
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_register_fails_with_existing_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_fails_without_required_fields(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_get_their_info(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'email', 'is_admin']
            ])
            ->assertJson(['success' => true, 'data' => ['id' => $user->id]]);
    }

    public function test_unauthenticated_user_cannot_get_user_info(): void
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => '已退出登录']);
    }

    public function test_user_can_check_if_admin(): void
    {
        $adminUser = User::factory()->create(['is_admin' => true]);
        $adminToken = $adminUser->createToken('test-token')->plainTextToken;

        $adminResponse = $this->withHeaders([
            'Authorization' => "Bearer $adminToken",
        ])->getJson('/api/auth/is-admin');

        $adminResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertTrue($adminResponse->json('data.is_admin'));
    }

    public function test_regular_user_gets_403_on_is_admin_check(): void
    {
        $regularUser = User::factory()->create(['is_admin' => false]);
        $regularToken = $regularUser->createToken('test-token')->plainTextToken;

        $regularResponse = $this->withHeaders([
            'Authorization' => "Bearer $regularToken",
        ])->getJson('/api/auth/is-admin');

        // Only admins may check admin status — non-admins get 403
        $regularResponse->assertForbidden();
    }
}
