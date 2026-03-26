<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuthService();
    }

    public function test_authenticate_with_valid_credentials_returns_user(): void
    {
        $password = 'test-password-123';
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make($password),
        ]);

        $result = $this->service->authenticate('test@example.com', $password);

        $this->assertEquals($user->id, $result->id);
    }

    public function test_authenticate_with_invalid_password_throws_exception(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->expectException(ValidationException::class);
        $this->service->authenticate('test@example.com', 'wrong-password');
    }

    public function test_authenticate_with_nonexistent_user_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->authenticate('nonexistent@example.com', 'any-password');
    }

    public function test_register_creates_new_user(): void
    {
        $user = $this->service->register('New User', 'new@example.com', 'password123');

        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'name' => 'New User',
        ]);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * Test that register() uses DB::transaction for atomicity.
     */
    public function test_register_uses_database_transaction(): void
    {
        // Spy on DB::transaction to verify it's called
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $user = $this->service->register('Transactional User', 'tx@example.com', 'password123');

        $this->assertEquals('Transactional User', $user->name);
        $this->assertEquals('tx@example.com', $user->email);
    }

    public function test_create_token_returns_string(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $token = $this->service->createToken($user);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function test_is_admin_returns_true_for_admin_user(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $this->assertTrue($this->service->isAdmin($admin));
    }

    public function test_is_admin_returns_false_for_regular_user(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => 'password',
            'is_admin' => false,
        ]);

        $this->assertFalse($this->service->isAdmin($user));
    }

    public function test_get_authenticated_user_returns_user_when_provided(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $result = $this->service->getAuthenticatedUser($user);

        $this->assertEquals($user->id, $result->id);
    }

    public function test_get_authenticated_user_returns_null_when_null(): void
    {
        $result = $this->service->getAuthenticatedUser(null);

        $this->assertNull($result);
    }
}
