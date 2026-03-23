<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function test_authenticate_with_valid_credentials_returns_user(): void
    {
        $password = fake()->password(12);
        $user = User::factory()->create([
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make($password),
        ]);

        $result = $this->service->authenticate($user->email, $password);

        $this->assertEquals($user->id, $result->id);
    }

    #[Test]
    public function test_authenticate_with_invalid_password_throws_exception(): void
    {
        $user = User::factory()->create([
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('correct-password'),
        ]);

        $this->expectException(ValidationException::class);
        $this->service->authenticate($user->email, 'wrong-password');
    }

    #[Test]
    public function test_authenticate_with_nonexistent_user_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->authenticate('nonexistent@example.com', 'any-password');
    }

    #[Test]
    public function test_register_creates_new_user(): void
    {
        $name = fake()->name();
        $email = fake()->unique()->safeEmail();
        $password = fake()->password(12);

        $user = $this->service->register($name, $email, $password);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => $name,
        ]);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    #[Test]
    public function test_create_token_returns_string(): void
    {
        $user = User::factory()->create();

        $token = $this->service->createToken($user);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    #[Test]
    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('auth-token')->plainTextToken;
        $persistedToken = $user->tokens()->first();
        $user->withAccessToken($persistedToken);

        $this->service->logout($user);

        $this->assertEquals(0, $user->tokens()->where('id', $persistedToken->id)->count());
    }

    #[Test]
    public function test_is_admin_returns_true_for_admin_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->service->isAdmin($admin));
    }

    #[Test]
    public function test_is_admin_returns_false_for_regular_user(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->assertFalse($this->service->isAdmin($user));
    }

    #[Test]
    public function test_get_authenticated_user_returns_user_when_provided(): void
    {
        $user = User::factory()->create();

        $result = $this->service->getAuthenticatedUser($user);

        $this->assertEquals($user->id, $result->id);
    }

    #[Test]
    public function test_get_authenticated_user_returns_null_when_null(): void
    {
        $result = $this->service->getAuthenticatedUser(null);

        $this->assertNull($result);
    }
}
