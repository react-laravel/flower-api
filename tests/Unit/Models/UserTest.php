<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_a_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    /**
     * @test
     */
    public function it_has_fillable_attributes(): void
    {
        $fillable = ['name', 'email', 'password', 'is_admin'];

        $this->assertEquals($fillable, (new User)->getFillable());
    }

    /**
     * @test
     */
    public function it_hides_password_and_remember_token(): void
    {
        $hidden = ['password', 'remember_token'];

        $this->assertEquals($hidden, (new User)->getHidden());
    }

    /**
     * @test
     */
    public function it_casts_is_admin_to_boolean(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'is_admin' => true,
        ]);

        $this->assertTrue($user->is_admin);
    }

    /**
     * @test
     */
    public function it_can_create_api_token(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $this->assertNotEmpty($token);
        $this->assertStringContainsString('|', $token);
    }

    /**
     * @test
     */
    public function it_can_find_user_by_email(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->name);
    }

    /**
     * @test
     */
    public function it_can_update_user(): void
    {
        $user = User::create([
            'name' => 'Old Name',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $user->update(['name' => 'New Name']);

        $this->assertEquals('New Name', $user->fresh()->name);
    }

    /**
     * @test
     */
    public function it_can_delete_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $id = $user->id;
        $user->delete();

        $this->assertNull(User::find($id));
    }
}
