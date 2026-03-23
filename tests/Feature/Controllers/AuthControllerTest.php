<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'token'],
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_rejects_login_with_invalid_credentials(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    /**
     * @test
     */
    public function it_rejects_login_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    /**
     * @test
     */
    public function it_validates_required_fields_on_login(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * @test
     */
    public function it_can_register_a_new_user(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'token'],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    /**
     * @test
     */
    public function it_rejects_registration_with_duplicate_email(): void
    {
        User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * @test
     */
    public function it_validates_required_fields_on_register(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * @test
     */
    public function it_validates_password_confirmation_on_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_get_authenticated_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['name' => 'Test User', 'email' => 'test@example.com'],
            ]);
    }

    /**
     * @test
     */
    public function it_rejects_unauthenticated_user_from_user_endpoint(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_can_logout(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '已退出登录',
            ]);
    }

    /**
     * @test
     */
    public function it_can_check_if_user_is_admin(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/is-admin');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['is_admin' => true],
            ]);
    }

    /**
     * @test
     */
    public function it_returns_403_for_regular_user_on_is_admin_endpoint(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/is-admin');

        // Only admins may check admin status — non-admins get 403 (prevents privilege enumeration)
        $response->assertForbidden();
    }
}
