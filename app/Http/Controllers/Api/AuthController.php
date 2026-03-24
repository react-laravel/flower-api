<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Auth controller — HTTP concerns only.
 * Business logic delegated to AuthService (fixes SRP and DRY violations).
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private AuthService $authService)
    {
    }

    /**
     * Login user and create token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = $this->authService->authenticate($request->email, $request->password);
        $token = $this->authService->createToken($user);

        return $this->success([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Register new user.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $this->authService->register(
            $request->name,
            $request->email,
            $request->password
        );
        $token = $this->authService->createToken($user);

        return $this->created([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        return $this->success($request->user());
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return $this->success(null, '已退出登录');
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(Request $request): JsonResponse
    {
        return $this->success([
            'is_admin' => $this->authService->isAdmin($request->user()),
        ]);
    }
}
