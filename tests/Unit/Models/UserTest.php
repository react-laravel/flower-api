<?php

namespace Tests\Unit\Models;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_it_can_be_instantiated(): void
    {
        $user = new User();
        $this->assertInstanceOf(User::class, $user);
    }
    public function test_fillable_attributes_are_defined(): void
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('is_admin', $fillable);
    }
    public function test_hidden_attributes_exclude_password_and_remember_token(): void
    {
        $user = new User();
        $hidden = $user->getHidden();

        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
    }
    public function test_casts_are_defined(): void
    {
        $user = new User();
        $casts = $user->getCasts();

        $this->assertArrayHasKey('email_verified_at', $casts);
        $this->assertEquals('datetime', $casts['email_verified_at']);
        $this->assertArrayHasKey('password', $casts);
        $this->assertEquals('hashed', $casts['password']);
        $this->assertArrayHasKey('is_admin', $casts);
        $this->assertEquals('boolean', $casts['is_admin']);
    }
}
