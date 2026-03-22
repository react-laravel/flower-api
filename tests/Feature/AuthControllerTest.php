<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['user', 'token']]);
    }

    public function test_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_requires_valid_email_format(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_creates_user(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['user', 'token']]);
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_minimum_password_length(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/auth/user');

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $user->id]]);
    }

    public function test_user_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertUnauthorized();
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/auth/logout');

        $response->assertOk()
            ->assertJson(['success' => true, 'message' => '已退出登录']);
    }

    public function test_is_admin_returns_admin_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $regular = User::factory()->create(['is_admin' => false]);

        $adminToken = $admin->createToken('admin-token')->plainTextToken;
        $userToken = $regular->createToken('user-token')->plainTextToken;

        $adminResponse = $this->withHeader('Authorization', "Bearer $adminToken")
            ->getJson('/api/auth/is-admin');
        $adminResponse->assertOk()
            ->assertJson(['success' => true, 'data' => ['is_admin' => true]]);

        $userResponse = $this->withHeader('Authorization', "Bearer $userToken")
            ->getJson('/api/auth/is-admin');
        $userResponse->assertOk()
            ->assertJson(['success' => true, 'data' => ['is_admin' => false]]);
    }
}
