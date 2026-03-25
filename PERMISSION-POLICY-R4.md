# AI-03b 权限策略检查（flower 第三轮 / 第二轮复检）— 综合报告

**时间：** 2026-03-25 02:30 (Asia/Shanghai)
**仓库：** react-laravel/flower（前端）+ react-laravel/flower-api（后端）
**检查范围：** policies、permissions、authorization 逻辑复检

---

## 一、Round 1-2 问题修复确认

### 后端（flower-api）

| 问题 | 状态 |
|------|------|
| `is_admin` 在 `$fillable` 中 | ✅ 已移除（`Flower::class` 使用 `#[Fillable(['name', ...])]`） |
| `/auth/login` 加 `throttle:5,1` | ✅ 已确认（`routes/api.php:13`） |
| `/auth/register` 加 `throttle:10,1` | ✅ 已确认（`routes/api.php:14`） |
| 5个 Policy 类实现 | ✅ 已确认（Flower/Category/Knowledge/SiteSetting/Upload） |
| 控制器 `$this->authorize()` 调用 | ✅ 已确认（所有 CRUD 控制器） |
| `SiteSetting` 敏感 key 过滤 | ✅ 已确认（`SENSITIVE_PATTERNS` 在 `SiteSettingController`） |
| `batchUpdate` 无数组大小限制 | ⚠️ **仍未修复** — `$request->validate(['settings' => 'required|array'])` |
| `batchUpdate` 无逐项 key/value 校验 | ⚠️ **仍未修复** — 直接传入 `SiteSettingService::batchSet()` |
| FormRequest `authorize()` 全返回 `true` | ⚠️ **仍未修复** — 所有 FormRequest 均 `return true;` |

### 前端（flower）

| 问题 | 状态 |
|------|------|
| Token key 错乱 | ✅ 已统一为 `flower-auth-token` |
| 两套 client | ✅ 已统一 — 所有页面均使用 `@/lib/api`（基于 `client.ts`） |
| 无 Next.js middleware.ts | ⚠️ **仍未修复** — `src/middleware.ts` 不存在 |
| `api.ts` (axios) 废弃 | ✅ 已废弃 — 仅被未使用的 `useApi` hook 引用（死代码） |

---

## 二、本轮新发现 / 遗留问题详解

### 🔴 Critical：文件上传未发送 Authorization 头

**文件：**
- `src/components/admin/FlowerFormDialog.tsx` — `handleImageUpload()`
- `src/lib/api/upload.ts` — `uploadApi.upload()`

**问题链：**

```typescript
// FlowerFormDialog.tsx:46
const res = await uploadApi.upload(file);  // ← 未传 token

// upload.ts
async upload(file: File, options?: { token?: string }) {
    const headers: Record<string, string> = {};
    if (options?.token) {  // ← token 为 undefined，不设 Authorization
        headers['Authorization'] = `Bearer ${options.token}`;
    }
    const response = await fetch(url, { method: 'POST', headers, body: formData });
    // ↑ 实际请求：无 Authorization 头
}
```

**为何仍能工作（架构依赖）：**

后端 `routes/api.php` 路由：
```php
Route::post('/upload', [UploadController::class, 'upload'])
    ->middleware(['auth:sanctum', 'admin']);
```

`auth:sanctum` 中间件使用 `guard: ['web']` + `stateful` 配置。当前端 SPA（localhost:3000）请求 API（localhost:8000）时，Sanctum 跨域发送**会话 Cookie**（HttpOnly）完成认证。

因此当前上传依赖 Cookie 会话而非 Bearer Token，这是**脆弱的隐式依赖**：
- Cookie + Token 两套机制并存，调试困难
- 若 CORS 配置变更或 Cookie 被清除，上传会静默失败
- 难以区分"Cookie 失效"和"真正无权限"

**修复方案（两种）：**

**方案 A（推荐）：** 前端显式传 token：
```typescript
// FlowerFormDialog.tsx
import useAuthStore from "@/lib/store/auth-store";
const { token } = useAuthStore();
const res = await uploadApi.upload(file, { token });  // 显式传 token
```

**方案 B：** 后端在 Cookie 认证失败时降级到 Bearer Token（需要修改 Sanctum 配置）。

---

### 🟡 Medium 1：`batchUpdate` 仍无数组大小限制

**文件：** `app/Http/Controllers/Api/SiteSettingController.php:77`

Round 1 已识别，Round 2 仍未修复：

```php
$settings = $request->validate([
    'settings' => 'required|array',  // ← 无 max 限制
]);
foreach ($settings['settings'] as $key => $value) {
    SiteSetting::setValue($key, $value);  // 每次都是独立 DB 写入
}
```

恶意认证管理员可发送包含数千条记录的数组，造成大量 DB 写入。

**修复：**
```php
'settings' => 'required|array|max:100',
```

---

### 🟡 Medium 2：`batchUpdate` 无逐项 key/value 校验

**文件：** `SiteSettingService::batchSet()` — `app/Services/SiteSettingService.php`

```php
public function batchSet(array $settings): void {
    foreach ($settings as $key => $value) {
        $this->set($key, $value);  // ← 无类型/长度校验
    }
}
```

超长 key（>255 字节）或超长 value（>10KB）可能导致 DB 异常或前端渲染问题。

**修复：** 在 `SiteSettingController::batchUpdate()` 的验证规则中加入：
```php
'settings' => 'required|array|max:100',
'settings.*.key' => 'required|string|max:255',
'settings.*.value' => 'nullable|string|max:10000',
```

---

### 🟢 Low 1：所有 FormRequest `authorize()` 返回 `true`

**文件：** `StoreFlowerRequest`, `UpdateFlowerRequest`, `StoreCategoryRequest`, `UpdateCategoryRequest`, `StoreKnowledgeRequest`, `UpdateKnowledgeRequest`

所有 FormRequest：
```php
public function authorize(): bool {
    return true;  // 依赖路由中间件，非防御性
}
```

**缓解：** 路由层 `auth:sanctum + admin` 已到位；但 FormRequest 的 `authorize()` 本意是防御性检查（可在请求层阻止无权限操作）。

**修复建议：**
```php
public function authorize(): bool {
    return Gate::allows('create', Flower::class);
}
```

---

### 🟢 Low 2：无 Next.js middleware.ts（前端路由无服务端守卫）

**文件：** `src/middleware.ts` / `middleware.ts` — 均不存在

未认证用户可直接 `curl http://localhost:3000/api/admin/flowers` 访问 Next.js API 路由（如果存在），或直接访问 `/admin/flowers` 页面看到短暂闪屏。

**缓解：** 后端 API 有完整认证保护；前端 `admin/layout.tsx` 在客户端做路由守卫。

**建议：** 如需完整 SPA 保护，添加 `src/middleware.ts`（已在 Round 1-2 报告中说明，未修复）。

---

## 三、前端权限模型评估

| 维度 | 现状 | 评级 |
|------|------|------|
| Token 存储 | localStorage + `flower-auth-token` key ✅ | 良好 |
| 路由守卫 | `admin/layout.tsx` 客户端守卫 ⚠️ | 可接受（有后端兜底） |
| 权限模型 | 二元 `isAdmin: boolean` ⚠️ | 基础可用 |
| 审计日志 | 无前后端审计日志 | 待建设 |
| 会话管理 | Token-based（API）+ Cookie（Sanctum SPA）⚠️ | 需统一 |

---

## 四、架构健康度总览

```
后端：
  路由层：auth:sanctum + admin 中间件链     ✅
  控制层：Gate::allows + $this->authorize() ✅
  模型层：is_admin 不在 $fillable           ✅
  限速：auth 5/1 + 10/1，chat 30/min        ✅
  敏感数据：SiteSetting key 过滤             ✅
  缺陷：batchUpdate 无校验，FormRequest authorize=true

前端：
  Token key：flower-auth-token 统一         ✅
  API client：统一为 client.ts              ✅
  缺陷：upload 无 Authorization 头（Cookie 隐式依赖）
  缺陷：无 Next.js middleware.ts
  缺陷：二元权限模型
```

---

## 五、问题汇总

| # | 严重度 | 问题 | 位置 | 状态 |
|---|--------|------|------|------|
| 1 | 🔴 Critical | 文件上传 `uploadApi.upload()` 未发 Authorization 头（依赖隐式 Cookie 认证） | flower 前端 | 新发现 |
| 2 | 🟡 Medium | `batchUpdate` 无数组大小限制 | SiteSettingController | 遗留（Round 1 已报） |
| 3 | 🟡 Medium | `batchUpdate` 无逐项 key/value 校验 | SiteSettingService | 遗留（Round 1 已报） |
| 4 | 🟢 Low | FormRequest `authorize()` 全返回 `true` | 所有 FormRequest | 遗留（Round 1 已报） |
| 5 | 🟢 Low | 无 Next.js middleware.ts | flower 前端 | 遗留（Round 1-2 已报） |
| 6 | 🟢 Low | 二元权限模型，无细粒度角色 | 全栈 | 架构性问题 |

---

## 六、修复优先级建议

1. **立即修复：** 问题 #1（文件上传 Authorization）— 显式传 token，消除对隐式 Cookie 认证的依赖
2. **高优先级：** 问题 #2（batchUpdate 数组限制）— 防止 DoS
3. **中优先级：** 问题 #3（batchUpdate 逐项校验）— 数据完整性
4. **低优先级：** 问题 #4、#5、#6 — 防御性增强和架构演进
