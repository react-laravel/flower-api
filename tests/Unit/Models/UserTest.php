<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_be_instantiated(): void
    {
        // Arrange & Act
        $user = new User();

        // Assert
        $this->assertInstanceOf(User::class, $user);
    }

    #[Test]
    public function name_is_fillable(): void
    {
        // Arrange
        $user = new User();

        // Act
        $user->fill(['name' => '张三']);

        // Assert
        $this->assertEquals('张三', $user->name);
    }

    #[Test]
    public function email_is_fillable(): void
    {
        // Arrange
        $user = new User();

        // Act
        $user->fill(['email' => 'test@example.com']);

        // Assert
        $this->assertEquals('test@example.com', $user->email);
    }

    #[Test]
    public function password_can_be_assigned_and_is_hashed(): void
    {
        // Arrange
        $user = new User();

        // Act
        $user->fill(['password' => 'secret123']);

        // Assert — password accessor returns hashed value due to 'hashed' cast
        $this->assertNotEquals('secret123', $user->password);
        $this->assertTrue(\Hash::check('secret123', $user->password));
    }

    #[Test]
    public function is_admin_is_fillable(): void
    {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);

        // Act & Assert
        $this->assertTrue($user->is_admin);
    }

    #[Test]
    public function fillable_attributes_are_defined(): void
    {
        // Arrange
        $user = new User();
        $fillable = $user->getFillable();

        // Assert
        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        // is_admin is NOT mass-assignable (not in $fillable)
        $this->assertNotContains('is_admin', $fillable);
    }

    #[Test]
    public function hidden_attributes_exclude_password_and_remember_token(): void
    {
        // Arrange
        $user = new User();
        $hidden = $user->getHidden();

        // Assert
        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
    }

    #[Test]
    public function casts_are_defined(): void
    {
        // Arrange
        $user = new User();
        $casts = $user->getCasts();

        // Assert
        $this->assertArrayHasKey('email_verified_at', $casts);
        $this->assertEquals('datetime', $casts['email_verified_at']);
        $this->assertArrayHasKey('password', $casts);
        $this->assertEquals('hashed', $casts['password']);
        $this->assertArrayHasKey('is_admin', $casts);
        $this->assertEquals('boolean', $casts['is_admin']);
    }

    #[Test]
    public function email_verified_at_cast_is_datetime(): void
    {
        // Arrange & Act
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Assert
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
    }

    #[Test]
    public function is_admin_cast_is_boolean(): void
    {
        // Arrange
        $userTrue = User::factory()->create(['is_admin' => true]);
        $userFalse = User::factory()->create(['is_admin' => false]);

        // Assert
        $this->assertTrue((bool) $userTrue->is_admin);
        $this->assertFalse((bool) $userFalse->is_admin);
    }

    #[Test]
    public function password_is_hashed_on_assignment(): void
    {
        // Arrange
        $plainPassword = 'plain-password-123';

        // Act
        $user = User::factory()->create(['password' => $plainPassword]);

        // Assert — password should be hashed, not stored as plain text
        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(\Hash::check($plainPassword, $user->password));
    }
}
