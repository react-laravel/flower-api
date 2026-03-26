# flower-api 权限/策略/授权逻辑 — R4 深度复查报告

**仓库路径：** `/root/.openclaw/workspace-ceo/flower-api`
**复查轮次：** R4（第二轮深度复查，即 AI-03b 第二轮）
**复查时间：** 2026-03-27
**检查范围：** AuthService isAdmin 方法链、Policy 方法命名一致性、Token 生命周期、中间件完整性

---

## 一、架构总览

```
auth:sanctum 保护层（认证）
       ↓
admin 中间件（is_admin 检查）
       ↓
Policy/Gate 授权（细粒度资源控制）
```

- **认证层**：`auth:sanctum`（Laravel Sanctum）保护所有需认证的路由
- **管理特权层**：`EnsureUserIsAdmin` 中间件，检验 `user.is_admin === true`
- **资源授权层**：5个 Policy 类（Flower/Category/Knowledge/SiteSetting/Upload） + 2个 Gate 定义
- **公共只读层**：index/show 无需认证（按设计）

---

## 二、关键问题：AuthService::isAdmin() 方法缺失（P0 - 新发现）

### 🔴 P0：AuthService::isAdmin() 调用不存在的服务方法

**文件：** `app/Http/Controllers/Api/AuthController.php:90-94`
**严重程度：** 🔴 致命

**问题描述：**

`AuthController::isAdmin()` 调用了 `AuthService::isAdmin()` 方法，但该方法**从未被定义**：

```php
// AuthController.php
public function isAdmin(Request $request): JsonResponse
{
    return $this->success([
        'is_admin' => $this->authService->isAdmin($request->user()),  // ❌ 不存在
    ]);
}
```

`AuthService` 现有方法：
- `authenticate(string $email, string $password): User`
- `register(string $name, string $email, string $password): User`
- `createToken(Authenticatable $user): string`
- `logout(Authenticatable $user): void`
- ❌ **`isAdmin()` 不存在**

**实际影响（致命）：**

1. 当管理员访问 `/api/auth/is-admin` 时：
   - `auth:sanctum` 中间件 → 通过（有效 token）
   - `admin` 中间件 → 通过（`is_admin === true`）
   - `AuthController::isAdmin()` → 调用 `$this->authService->isAdmin()` → **PHP Fatal Error** → HTTP 500

2. 前端 `auth-store.ts:checkAdmin()` 的 catch 块：
   ```ts
   } catch (error) {
     console.error("Check admin failed:", error);
     return false;  // ❌ 吞下 500 错误，返回 false
   }
   ```

3. 后果：
   - 管理员登录后 `isAdmin` 状态为 `true`（来自登录响应）
   - 管理员可短暂访问 admin 页面（admin layout 守卫 `isAdmin=true`）
   - 但 `checkAdmin()` 永远返回 `false`
   - admin layout useEffect 检测到 `isAdmin=false` → `router.push("/")` → **永久重定向到首页**
   - 管理员无法正常使用任何管理功能

**修复建议：**

方案A（推荐）：既然 `admin` 中间件已确认用户是管理员，直接返回 `true`：
```php
public function isAdmin(Request $request): JsonResponse
{
    return $this->success(['is_admin' => true]);
}
```

方案B：使用 `$request->user()` 直接读取：
```php
public function isAdmin(Request $request): JsonResponse
{
    return $this->success(['is_admin' => $request->user()?->is_admin ?? false]);
}
```

方案C：补充 `AuthService::isAdmin()` 方法：
```php
public function isAdmin(?Authenticatable $user): bool
{
    return $user?->is_admin === true;
}
```

---

## 三、Policy 方法命名一致性验证（已确认一致）

| Policy | trait 方法 | 调用处 | 状态 |
|--------|-----------|--------|------|
| FlowerPolicy | `delete()` | `CrudOperations::destroy()` → `authorize('delete')` | ✅ 一致 |
| CategoryPolicy | `delete()` | `CrudOperations::destroy()` → `authorize('delete')` | ✅ 一致 |
| KnowledgePolicy | `delete()` | `CrudOperations::destroy()` → `authorize('delete')` | ✅ 一致 |

**R3 报告错误记录：** R3 报告称 trait 定义了 `deleteItem()` 而非 `delete()`，实为误判。当前代码 `delete()` 与 `authorize('delete')` 完全匹配，无需修改。

---

## 四、AuthService::logout() 空指针修复验证

**文件：** `app/Services/AuthService.php:65-69`
**状态：** ✅ 已修复（commit `8e8e369`）

```php
public function logout(Authenticatable $user): void
{
    $token = $user->currentAccessToken();  // 可能返回 null
    if ($token) {
        $token->delete();
    }
}
```

---

## 五、Token 生命周期（M1 问题 - 未修复）

**状态：** ⚠️ 仍未修复

- `SANCTUM_EXPIRATION` 未在 `.env` 中配置
- `AuthService::logout()` 仅删除当前 Token，窃取的旧 Token 仍可用
- 建议：添加 `SANCTUM_EXPIRATION=1440` 到 `.env`，并考虑实现 `logoutAll()`

---

## 六、Gate 与 Policy 注册（确认完整）

| 资源 | 注册方式 | 状态 |
|------|---------|------|
| Flower | `Gate::policy(Flower::class, FlowerPolicy::class)` | ✅ |
| Category | `Gate::policy(Category::class, CategoryPolicy::class)` | ✅ |
| Knowledge | `Gate::policy(Knowledge::class, KnowledgePolicy::class)` | ✅ |
| SiteSetting | `Gate::policy(SiteSetting::class, SiteSettingPolicy::class)` | ✅ |
| upload | `Gate::define('upload', [UploadPolicy::class, 'create'])` | ✅ |
| upload.delete | `Gate::define('upload.delete', [UploadPolicy::class, 'delete'])` | ✅ |

---

## 七、速率限制（确认完整）

| 端点 | 中间件 | 限制 | 状态 |
|------|--------|------|------|
| POST /auth/login | `throttle:5,1` | 5次/分钟 | ✅ |
| POST /auth/register | `throttle:10,1` | 10次/分钟 | ✅ |
| POST /chat | `throttle:30,1` | 30次/分钟 | ✅ |
| GET /auth/is-admin | **无** | 无 | ⚠️ 仍无限制 |

---

## 八、R4 问题汇总

### 🔴 高危（High）

| # | 问题 | 文件 | 描述 | 修复建议 |
|---|------|------|------|---------|
| P0 | **AuthService::isAdmin() 缺失** | `AuthController.php:93` | 调用不存在的方法，管理员访问 `/auth/is-admin` 即触发 500，导致 admin 页面永久无法正常访问 | 改为直接返回 `true` 或 `$request->user()?->is_admin ?? false` |

### ⚠️ 中危（Medium）— 延续自 R3

| # | 问题 | 状态 | 备注 |
|---|------|------|------|
| M1 | Token 生命周期未配置 | ⚠️ 未修复 | 需配置 `SANCTUM_EXPIRATION` |
| L1 | `/auth/is-admin` 无速率限制 | ⚠️ 未修复 | 建议添加 `throttle:5,1` |

---

## 九、修复优先级

| 优先级 | 问题 | 行动 | 预估工时 |
|--------|------|------|---------|
| P0 | AuthService::isAdmin() | **立即修复**（方案A） | 5 分钟 |
| P1 | SANCTUM_EXPIRATION | 添加到 `.env` | 2 分钟 |
| P2 | is-admin 速率限制 | 添加 `throttle:5,1` 到路由 | 2 分钟 |

---

**Commit 历史检查结果：**
- `53464ef`（R3 报告提及）不在本仓库 git log 中，R3 报告可能基于不同工作区
- 最新相关 commit：`8e8e369`（logout null check 修复）
