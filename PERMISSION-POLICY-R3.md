# AI-03b 权限策略检查（flower 第二轮）— 深度审查报告

**时间：** 2026-03-25 01:36 (Asia/Shanghai)
**仓库：** react-laravel/flower（前端）+ react-laravel/flower-api（后端）
**检查范围：** policies、permissions、authorization 逻辑

---

## 一、前置轮次修复确认

| 问题 | 状态 |
|------|------|
| `is_admin` 已从 `$fillable` 移除 | ✅ 确认（`#[Fillable(['name', 'email', 'password'])]`） |
| `/auth/login` 加 `throttle:5,1` | ✅ 确认（`routes/api.php:13`） |
| `/auth/register` 加 `throttle:10,1` | ✅ 确认（`routes/api.php:14`） |
| 5个 Policy 类已实现 | ✅ 确认（Flower/Category/Knowledge/SiteSetting/Upload） |
| 控制器 `$this->authorize()` 调用 | ✅ 确认（Flower/Category/Knowledge/SiteSetting/UploadController） |
| Token key 统一为 `flower-auth-token` | ✅ 确认（`auth-store.ts` + `client.ts` + `api.ts` 均一致） |
| SiteSetting 敏感 key 过滤 | ✅ 确认（smtp_/aws_/password/secret/key/token/credential/auth 过滤） |

**后端整体架构评级：A — 授权架构三层防御到位。**

---

## 二、后端（flower-api）授权架构现状

```
路由层：auth:sanctum + admin 中间件链 ✅
控制层：$this->authorize() + Policy ✅
模型层：is_admin 不在 $fillable ✅
Gate：upload / upload.delete 已定义 ✅
限速：auth 5/1 + 10/1，chat 30/min ✅
敏感数据：SiteSetting 过滤策略已实施 ✅
```

---

## 三、本轮新发现问题

### 🟡 Medium 1：`batchUpdate` 无数组大小限制

**文件：** `SiteSettingController::batchUpdate()` (`app/Http/Controllers/Api/SiteSettingController.php:77`)

**问题：** 验证规则仅 `'settings' => 'required|array'`，无限数组大小限制。

```php
$settings = $request->validate([
    'settings' => 'required|array',  // 无 max() 限制
]);
foreach ($settings['settings'] as $key => $value) {
    SiteSetting::setValue($key, $value);  // 每次写入都是一次 DB 查询
}
```

**风险：** 攻击者用超大数组（数千条）可造成大量 DB 写入或连接耗尽。

**修复建议：**
```php
'settings' => 'required|array|max:100',  // 加数组大小限制
```

**严重度：** Medium（攻击者需先认证为 admin，场景受限）

---

### 🟡 Medium 2：`batchUpdate` 无逐项 key/value 校验

**文件：** 同上

**问题：** `'settings'` 数组内每个元素无类型/长度校验，`$key` 和 `$value` 直接传入 `SiteSettingService::batchSet()` → `updateOrCreate()`。

```php
public function batchSet(array $settings): void
{
    foreach ($settings as $key => $value) {
        $this->set($key, $value);  // 无类型/长度校验
    }
}
```

**风险：** 传入超长 key（255+ bytes）可能触发 DB 异常或意外行为；超长 value 可能超出页面渲染能力。

**修复建议：**
```php
'settings' => 'required|array|max:100',
'settings.*.key' => 'required|string|max:255',
'settings.*.value' => 'nullable|string|max:10000',
```

---

### 🟢 Low 1：FormRequest `authorize()` 全部返回 `true`

**文件：** 所有 FormRequest（`StoreFlowerRequest`、`UpdateFlowerRequest` 等）

**问题：** 所有 FormRequest 的 `authorize()` 硬编码 `return true`，未使用 `Gate::allows()` 做防御性检查。

```php
public function authorize(): bool
{
    return true;  // 完全依赖路由中间件
}
```

**缓解：** 路由层 `auth:sanctum + admin` 中间件已到位，风险可控。

**建议：** 改为 `$this->authorize('create', Flower::class)` 或显式返回 `Gate::allows('create', Flower::class)`。

---

### 🟢 Low 2：两套前端 API client 并存

**文件：** `src/lib/api.ts`（axios）+ `src/lib/api/client.ts`（fetch）

**现状：** 两套 client 均读取 `flower-auth-token` token ✅，但：
- `api.ts` 基于 axios，`client.ts` 基于原生 fetch
- 配置不同（timeout/retry/logic 不一致）
- 部分页面用 `api.ts`，部分用 `client.ts`

**风险：** 行为不一致，维护成本高；部分请求可能携带不了正确的 token。

**建议：** 统一使用 `client.ts`，逐步废弃 `api.ts`。

---

## 四、前端（flower）授权逻辑现状

### ✅ 已确认的正确实现

| 项目 | 说明 |
|------|------|
| Token key 统一 | `flower-auth-token` 在 auth-store + client.ts + api.ts 三处一致 |
| 路由守卫 | `admin/layout.tsx` 使用 `useEffect` 检查 `isAuthenticated` + `isAdmin` |
| 登出逻辑 | 调用 `/auth/logout` + 清理 localStorage |
| 401 处理 | `api.ts` 和 `client.ts` 均处理 401 并触发 `auth:unauthorized` 事件 |

### ⚠️ 未解决问题

**🔴 前端无 Next.js `middleware.ts`**

- **文件：** 缺失 `middleware.ts`（根目录或 `src/`）
- **问题：** `/admin/*` 路由无服务端保护，用户可直接 `curl` 绕过前端守卫访问受保护 API
- **缓解：** 后端 API 有 `auth:sanctum + admin` 中间件保护，API 层面是安全的
- **风险：** 用户体验层面可感知"页面闪一下"再跳转；安全层面后端已保障

**建议：** 如需完整防护，添加 Next.js middleware（但优先级低于 Medium 问题）。

---

## 五、总结

| 严重度 | 问题 | 位置 |
|--------|------|------|
| 🟡 Medium | `batchUpdate` 无数组大小限制 | SiteSettingController |
| 🟡 Medium | `batchUpdate` 无逐项 key/value 校验 | SiteSettingService |
| 🟢 Low | FormRequest `authorize()` 全返回 `true` | 所有 FormRequest |
| 🟢 Low | 两套前端 API client 并存 | flower 前端 |
| 🟢 Low | 无 Next.js middleware.ts | flower 前端 |

**整体评估：**
- 后端授权架构设计合理，三层防御（路由 + 控制器 + Policy）到位，Round 1 问题均已修复
- 前端 token key 错乱已解决，但两套 client 共存和缺失 middleware 仍是遗留问题
- 建议优先修复 2 个 Medium 问题（`batchUpdate` 校验），Low 问题可排入后续迭代

**优先级建议：** Medium → Low（功能安全）
