<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    // ─── Factory ───────────────────────────────────────────────────────────

    public function test_factory_creates_valid_user(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', ['email' => $user->email]);
        $this->assertNotEmpty($user->name);
        $this->assertNotEmpty($user->email);
        $this->assertNotNull($user->password);
    }

    public function test_factory_creates_admin_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($admin->is_admin);
        $this->assertEquals(1, $admin->is_admin); // boolean cast
    }

    public function test_factory_creates_regular_user(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->assertFalse($user->is_admin);
    }

    // ─── Mass assignment ────────────────────────────────────────────────────

    public function test_can_create_user_with_all_fillable_fields(): void
    {
        $user = User::create([
            'name' => '张三',
            'email' => 'zhangsan@example.com',
            'password' => 'password123',
            'is_admin' => true,
        ]);

        $this->assertDatabaseHas('users', ['name' => '张三', 'email' => 'zhangsan@example.com', 'is_admin' => 1]);
        $this->assertTrue($user->is_admin);
    }

    // ─── Hidden attributes ──────────────────────────────────────────────────

    public function test_password_is_hidden_from_serialization(): void
    {
        $user = User::factory()->create();

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    public function test_password_is_hidden_in_json(): void
    {
        $user = User::factory()->create();

        $json = $user->toJson();

        $this->assertStringNotContainsString('password', $json);
    }

    // ─── Type casting ───────────────────────────────────────────────────────

    public function test_is_admin_is_cast_to_boolean(): void
    {
        $admin = User::factory()->create(['is_admin' => 1]);
        $user = User::factory()->create(['is_admin' => 0]);

        $this->assertTrue($admin->is_admin);
        $this->assertIsBool($admin->is_admin);
        $this->assertFalse($user->is_admin);
        $this->assertIsBool($user->is_admin);
    }

    public function test_email_verified_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->email_verified_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
    }

    public function test_password_is_hashed(): void
    {
        $plainPassword = 'secret123';
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $plainPassword,
        ]);

        // Password should be hashed, not stored as plain text
        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($plainPassword, $user->password));
    }

    // ─── Fillable protection ─────────────────────────────────────────────────

    public function test_non_fillable_fields_are_ignored(): void
    {
        $user = User::factory()->create();

        $originalEmail = $user->email;
        $user->update(['unknown_field' => 'injected']);

        $this->assertArrayNotHasKey('unknown_field', $user->getAttributes());
        $this->assertEquals($originalEmail, $user->email);
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function test_user_can_have_api_token(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        $this->assertNotEmpty($token);
        $this->assertStringContainsString('|', $token);
    }

    public function test_user_can_have_multiple_tokens(): void
    {
        $user = User::factory()->create();

        $user->createToken('token1');
        $user->createToken('token2');

        $this->assertEquals(2, $user->tokens()->count());
    }

    public function test_user_can_delete_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('to-delete');

        $user->tokens()->where('id', $token->accessToken->id)->delete();

        $this->assertEquals(0, $user->tokens()->count());
    }

    // ─── CRUD ───────────────────────────────────────────────────────────────

    public function test_can_update_user(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $user->update(['name' => 'New Name']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_can_delete_user(): void
    {
        $user = User::factory()->create();

        $id = $user->id;
        $user->delete();

        $this->assertDatabaseMissing('users', ['id' => $id]);
    }
}
