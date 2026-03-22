<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EnsureUserIsAdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_authenticated_user_returns_403(): void
    {
        $middleware = new EnsureUserIsAdmin();
        $request = Request::create('/api/admin', 'GET');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('需要管理员权限', $data['message']);
    }

    public function test_non_admin_user_returns_403(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $middleware = new EnsureUserIsAdmin();
        $request = Request::create('/api/admin', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function test_admin_user_passes_through(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $middleware = new EnsureUserIsAdmin();
        $request = Request::create('/api/admin', 'GET');
        $request->setUserResolver(fn () => $admin);

        $nextCalled = false;
        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return response()->json(['ok' => true]);
        });

        $this->assertTrue($nextCalled);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
