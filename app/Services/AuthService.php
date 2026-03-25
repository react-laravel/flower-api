<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Authentication service handling user authentication business logic.
 * Extracted from AuthController to fix SRP and DIP violations.
 */
class AuthService
{
    private Hasher $hasher;

    public function __construct(?Hasher $hasher = null)
    {
        $this->hasher = $hasher ?? Hash::getFacadeRoot();
    }

    /**
     * Authenticate user with email and password.
     *
     * @throws ValidationException
     */
    public function authenticate(string $email, string $password): User
    {
        $user = User::where('email', $email)->first();

        if (!$user || !$this->hasher->check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['提供的凭证不正确'],
            ]);
        }

        return $user;
    }

    /**
     * Create a new user registration.
     */
    public function register(string $name, string $email, string $password): User
    {
        return DB::transaction(function () use ($name, $email, $password) {
            return User::create([
                'name' => $name,
                'email' => $email,
                'password' => $this->hasher->make($password),
            ]);
        });
    }

    /**
     * Create authentication token for user.
     */
    public function createToken(Authenticatable $user): string
    {
        return $user->createToken('auth-token')->plainTextToken;
    }

    /**
     * Revoke current access token.
     */
    public function logout(Authenticatable $user): void
    {
        $user->currentAccessToken()->delete();
    }

}
