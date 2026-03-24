<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\Idempotency;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse, Idempotency;

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
     * Protected by idempotency to prevent duplicate registrations on retry.
     */
    public function register(Request $request): JsonResponse
    {
        return $this->handleIdempotentRequest($request, function () use ($request) {
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

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ],
                'message' => '注册成功',
            ], 201);
        });
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
