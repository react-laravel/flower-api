# AI-03b Permission Policy Check — Round 2 (flower)
## 执行时间
2026-03-25 08:21 (Asia/Shanghai)

**仓库：**
- 前端：`react-laravel/flower` (Next.js)
- 后端：`react-laravel/flower-api` (Laravel)

**检查范围：** policies / permissions / authorization 逻辑（第二轮复检）
**前次检查：** PERMISSION-POLICY-R2.md（AI-03 Round 2，2026-03-22）

---

## 一、后端架构总览（现状确认）

```
路由层  →  auth:sanctum (认证) + admin 中间件 (is_admin 角色检查)
    ↓
控制器层  →  $this->authorize() (Policy 授权检查)
    ↓
Policy 层  →  AdminAccessControl trait (is_admin 布尔判断)
```

✅ 基础架构正确，R2 修复已落实：
- 5个 Policy 类已建立（Flower / Category / Knowledge / SiteSetting / Upload）
- CrudOperations trait 统一调用 `$this->authorize()`
- AppServiceProvider 已注册所有 Policy
- Admin 中间件正确返回 403

---

## 二、发现的问题

### 🔴 Critical（严重）

#### 问题 1：`AuthService::isAdmin()` 方法缺失

| 项目 | 内容 |
|------|------|
| 文件 | `app/Services/AuthService.php` |
| 影响 | `AuthController::isAdmin()` 调用 `$this->authService->isAdmin($request->user())` → `BadMethodCallException` |
| 触发路径 | `GET /api/auth/is-admin` |
| 缓解因素 | 路由有 `['auth:sanctum', 'admin']` 中间件，admin 用户才能到达此端点 |
| 风险 | 正常请求时无影响（admin 中间件先拦截非 admin），但代码逻辑不完整，且路由设计存在矛盾（见问题 2） |

**修复建议：**
在 `AuthService` 添加：
```php
public function isAdmin(?Authenticatable $user): bool
{
    return $user && $user->is_admin;
}
```

---

#### 问题 2：`/auth/is-admin` 路由设计矛盾

| 项目 | 内容 |
|------|------|
| 路由 | `Route::get('/auth/is-admin', [AuthController::class, 'isAdmin'])->middleware(['auth:sanctum', 'admin']);` |
| 问题 | 路由同时要求 `admin` 中间件 → 只有 admin 能访问 → 永远返回 `is_admin: true` → 端点失去意义 |
| 现状 | 测试 `test_is_admin_returns_false_for_regular_user` 用 `actingAs()` 绕过了真实中间件逻辑，测试与生产行为不一致 |
| 目的分析 | 前端 `useAuthStore.checkAdmin()` 需要从后端获取真实的 `is_admin` 状态；但中间件让普通用户永远拿不到 `false` |

**修复建议（二选一）：**
- **方案 A（推荐）：** 移除 `admin` 中间件，让所有已认证用户都能查询自己的 admin 状态：
  ```php
  Route::middleware('auth:sanctum')->get('/auth/is-admin', [AuthController::class, 'isAdmin']);
  ```
- **方案 B：** 保留 `admin` 中间件，但前端 `checkAdmin()` 只在 admin 用户登录后调用（当前实现中 admin layout 的 useEffect 已经有条件保护，可以保持现状但端点意义不大）

---

#### 问题 3：`/api/submissions` 端点 `X-User-Id` 头部从未被设置

| 项目 | 内容 |
|------|------|
| 文件 | `src/app/api/submissions/route.ts` |
| 问题 | `getAuthenticatedUserId()` 从请求头读取 `x-user-id`，但整个代码库中无任何地方设置此头 |
| 影响 | 所有认证用户的 POST/GET 请求均返回 `401 Unauthorized` |
| 证据 | `client.ts` 的 `getApiClient()` 只设置 `Authorization: Bearer` 头；无 Next.js Middleware 设置 `x-user-id` |
| 测试 vs 生产 | 测试文件 `route.test.ts` 的 `createAuthenticatedRequest()` 手动注入 `x-user-id` 头，导致测试通过但生产环境永远失败 |

**修复建议：**
从请求的 `Authorization: Bearer` token 中解码用户 ID，或通过 `getToken()` 获取当前用户：

```typescript
// 在 submissions/route.ts 中替换 getAuthenticatedUserId
import { getToken } from 'next-auth/jwt';

async function getAuthenticatedUserId(request: NextRequest): Promise<string | null> {
  // Next.js App Router: use getToken from next-auth/jwt
  const token = await getToken({ req: request, secret: process.env.NEXTAUTH_SECRET });
  return token?.sub ?? null;
}
```

或者在后端 Laravel 侧添加一个 `GET /auth/submissions-identity` 端点返回 `user_id`，前端通过该端点获取并传给 submissions。

---

### 🟡 Medium（中等问题）

#### 问题 4：`AuthService::logout()` 缺少空指针检查

| 项目 | 内容 |
|------|------|
| 文件 | `app/Services/AuthService.php` |
| 代码 | `$user->currentAccessToken()->delete();` 无 null 检查 |
| 场景 | `currentAccessToken()` 在某些边缘情况（如 Token 被外部撤销、DB 并发）下可能返回 null |
| 影响 | ` logout()` 抛出 `NullPointerException`，用户无法登出 |
| 严重程度 | 低（边缘情况），但属于防御性编程缺陷 |

**修复建议：**
```php
public function logout(Authenticatable $user): void
{
    $token = $user->currentAccessToken();
    if ($token) {
        $token->delete();
    }
}
```

---

#### 问题 5：前端 `isAdmin` 状态依赖 localStorage（可被篡改）

| 项目 | 内容 |
|------|------|
| 文件 | `src/lib/store/auth-store.ts` |
| 问题 | `isAdmin` 存在 Zustand persist localStorage 中，可被浏览器扩展或手动修改 localStorage 篡改 |
| 缓解 | 后端所有 admin 路由都有 `admin` 中间件保护，篡改只能影响 UI 显示，不能绕过后端授权 |
| 风险 | 用户可能看到本不该看到的 admin 界面按钮（UI 欺骗），但实际无法操作 |
| 建议 | 保持现状（因为后端已防御），但可在 admin layout 添加 "加载中" 遮罩避免闪烁 |

---

### 🟢 Low / Info（低风险 / 信息）

#### 问题 6：`CrudOperations::store()` 参数类型不精确

| 项目 | 内容 |
|------|------|
| 文件 | `app/Http/Traits/CrudOperations.php` |
| 代码 | `$this->authorize('create', $modelClass);` 传入 `Model class-string` 而非实例 |
| 影响 | `viewAny()` 和 `create()` 的 Policy 方法签名 `(User $user, $model)` 中 `$model` 类型不精确 |
| 实际 | Policy 中这两个方法不使用 `$model` 参数（`viewAny` 返回 `true`，`create` 检查 `$user->is_admin`），所以无实质影响 |
| 建议 | 添加显式类型或注释说明：`/* @param class-string $modelClass */` |

---

#### 问题 7：`SiteSettingController::update/batchUpdate` 使用 `new SiteSetting()`

| 项目 | 内容 |
|------|------|
| 文件 | `app/Http/Controllers/Api/SiteSettingController.php` |
| 代码 | `$this->authorize('update', new SiteSetting());` 每次创建新的空实例 |
| 影响 | 无实质影响（Policy 的 `update()` 只检查 `$user->is_admin`，不使用模型属性） |
| 建议 | 可接受；若未来需要资源所有权检查，需改为传入真实实体 |

---

## 三、已确认正常的部分

| 检查项 | 状态 | 说明 |
|--------|------|------|
| Policy 类完整注册到 Gate | ✅ | AppServiceProvider::boot() 正确注册 |
| Admin 中间件正确拦截 | ✅ | 403 响应，符合预期 |
| CrudOperations trait 统一授权 | ✅ | store/update/destroy 均有 `$this->authorize()` |
| upload/delete Gate 定义 | ✅ | `Gate::define('upload')` 和 `upload.delete` 正确注册 |
| 前端 `authApi.isAdmin()` 调用 | ✅ | 类型导出正确，接口设计合理 |
| Admin layout 前端路由保护 | ✅ | `useEffect` 中 `checkAdmin()` + `AbortController` 防竞态 |
| 敏感设置 key 过滤 | ✅ | SiteSettingController 正确过滤 smtp_/aws_ 等敏感前缀 |
| Public read 路由（flowers/categories/knowledge） | ✅ | 无需认证，符合业务需求 |

---

## 四、修复优先级建议

| 优先级 | 问题 | 预计工时 |
|--------|------|---------|
| P0 | 问题 3：`X-User-Id` 缺失（ submissions 端点完全失效）| 1h |
| P1 | 问题 1：`AuthService::isAdmin()` 缺失 | 10min |
| P1 | 问题 2：`/auth/is-admin` 路由设计矛盾 | 10min（移除中间件） |
| P2 | 问题 4：`logout()` 空指针检查 | 10min |
| P3 | 问题 5：localStorage isAdmin 可被篡改（低风险，后端已防御）| 已知/不修 |

---

## 五、测试盲区说明

以下测试在 test environment 通过但不能反映生产行为：

1. **`test_is_admin_returns_false_for_regular_user`** — `actingAs()` 绕过了 `admin` 中间件，导致非 admin 用户能访问 `isAdmin()` 端点；生产中该用户会收到 403
2. **`/api/submissions` 全部测试** — 测试手动注入 `x-user-id` 头；生产中该头不存在，所有请求返回 401
3. **`AuthService::logout()` 的 null 场景** — 当前测试不覆盖 `currentAccessToken() === null` 的边缘情况

---

**报告生成：** AI-03b Round 2 | 2026-03-25 08:21 CST
