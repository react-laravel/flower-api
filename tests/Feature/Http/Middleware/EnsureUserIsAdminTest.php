<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class EnsureUserIsAdminTest extends TestCase
{
    private EnsureUserIsAdmin $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new EnsureUserIsAdmin();
    }

    public function test_it_allows_admin_user_to_pass(): void
    {
        $user = new User();
        $user->is_admin = true;

        $request = Request::create('/api/admin', 'GET');
        $request->setUserResolver(fn() => $user);

        $response = $this->middleware->handle($request, fn() => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_it_blocks_non_admin_user_with_403(): void
    {
        $user = new User();
        $user->is_admin = false;

        $request = Request::create('/api/admin', 'GET');
        $request->setUserResolver(fn() => $user);

        $response = $this->middleware->handle($request, fn() => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('需要管理员权限', $content['message']);
    }

    public function test_it_blocks_when_no_user(): void
    {
        $request = Request::create('/api/admin', 'GET');
        $request->setUserResolver(fn() => null);

        $response = $this->middleware->handle($request, fn() => response()->json(['ok' => true]));

        $this->assertEquals(403, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('需要管理员权限', $content['message']);
    }
}
