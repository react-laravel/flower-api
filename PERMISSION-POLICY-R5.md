# AI-03b 权限策略检查（flower 第五轮 / 第二轮复检）— 后端报告

**时间：** 2026-03-25 05:37 (Asia/Shanghai)
**仓库：** react-laravel/flower-api（后端）
**检查范围：** policies、permissions、authorization 逻辑复检

---

## 一、前轮状态确认（基于 R4 报告）

| # | 严重度 | 问题 | R4 状态 | 本轮状态 |
|---|--------|------|---------|---------|
| 1 | 🟡 Medium | `batchUpdate` 无数组大小限制 | 遗留 | ⚠️ **仍未修复** |
| 2 | 🟡 Medium | `batchUpdate` 无逐项 key/value 校验 | 遗留 | ⚠️ **仍未修复** |
| 3 | 🟢 Low | FormRequest `authorize()` 全返回 `true` | 遗留 | ⚠️ **仍未修复** |
| 4 | 🟢 Low | Gate 映射 `upload` → `UploadPolicy::create` | 已确认 | ✅ 正确 |

---

## 二、本轮详细审查结果

### 🟡 Medium 1：`batchUpdate` 仍无数组大小限制

**文件：** `app/Http/Controllers/Api/SiteSettingController.php:77`

```php
$settings = $request->validate([
    'settings' => 'required|array',  // ← 无 max 限制
]);
```

认证管理员可发送包含数万条记录的数组，每次 `SiteSetting::updateOrCreate()` 都是独立 DB 写入。N 次 DB 操作 + N 次 Cache::forget()。

**修复：**
```php
'settings' => 'required|array|max:100',  // 添加上限
```

**风险场景：** 恶意管理员通过批量更新耗尽 DB 连接或缓存刷新风暴。

---

### 🟡 Medium 2：`batchUpdate` 无逐项 key/value 校验

**文件：** `app/Services/SiteSettingService.php:batchSet()`

```php
public function batchSet(array $settings): void {
    foreach ($settings as $key => $value) {
        SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY_PREFIX . $key);
    }
}
```

- 无 key 长度校验（DB key 列通常 varchar 255）
- 无 value 类型/长度校验（可能导致前端渲染问题或 DB 字段溢出）
- 无白名单校验（可写入任意 key，覆盖任意配置）

**修复（在 SiteSettingController 验证层）：**
```php
'settings' => 'required|array|max:100',
'settings.*.key' => 'required|string|max:255',
'settings.*.value' => 'nullable|string|max:10000',
```

**额外建议：** 添加白名单 `ALLOWED_KEYS` 数组，仅允许预定义 key。

---

### 🟢 Low：FormRequest `authorize()` 全返回 `true`

**涉及文件：**
- `app/Http/Requests/StoreFlowerRequest.php`
- `app/Http/Requests/UpdateFlowerRequest.php`
- `app/Http/Requests/StoreCategoryRequest.php`
- `app/Http/Requests/UpdateCategoryRequest.php`
- `app/Http/Requests/StoreKnowledgeRequest.php`
- `app/Http/Requests/UpdateKnowledgeRequest.php`

```php
public function authorize(): bool {
    return true;  // 依赖路由中间件，非防御性
}
```

**缓解有效：** 路由层 `auth:sanctum + admin` 正确配置，FormRequest 的 authorize() 作为第二防御层目前无实际作用。

**修复建议：**
```php
public function authorize(): bool {
    return Gate::allows('create', Flower::class);
}
```

---

## 三、后端授权架构验证（✅ 全绿）

### 路由层
```
auth:sanctum + admin 中间件链  ✅
  ├─ auth:sanctum → Session Cookie 认证
  └─ admin (EnsureUserIsAdmin) → $request->user()->is_admin 检查
```

### Gate 映射（`AppServiceProvider::boot()`）
```php
Gate::policy(Flower::class, FlowerPolicy::class);       ✅
Gate::policy(Category::class, CategoryPolicy::class);   ✅
Gate::policy(Knowledge::class, KnowledgePolicy::class);  ✅
Gate::policy(SiteSetting::class, SiteSettingPolicy::class); ✅
Gate::define('upload', [UploadPolicy::class, 'create']);      ✅
Gate::define('upload.delete', [UploadPolicy::class, 'delete']); ✅
```

### Policy 授权逻辑（AdminAccessControl trait）
```php
viewAny() → true        ✅ 公开读取
view()    → true        ✅ 公开读取
create() → $user->is_admin  ✅ 管理原创
update() → $user->is_admin  ✅ 管理原创
delete() → $user->is_admin  ✅ 管理原创
```

### 敏感数据保护
- `SiteSettingController::index()`：使用 `SENSITIVE_PATTERNS` 过滤敏感 key（smtp_, aws_, password, token, secret 等）
- `Flower::fillable`：**不含 `is_admin`**（防止 mass assignment）
- `User::fillable`：**含 `is_admin`**（仅管理员可修改用户权限，这是预期行为）

### 限速保护
```
POST /auth/login  → throttle:5,1   ✅（防暴力破解）
POST /auth/register → throttle:10,1 ✅（防批量注册）
POST /chat      → throttle:30,1   ✅（防聊天滥用）
```

### 文件安全（UploadController）
- `upload()`: `required|image|mimes:jpeg,png,jpg,gif,webp|max:5120` ✅
- `delete()`: 路径校验 `str_starts_with('uploads/')` + `..` 和 `~` 路径遍历检查 ✅

---

## 四、问题汇总（后端）

| # | 严重度 | 问题 | 位置 | 状态 |
|---|--------|------|------|------|
| 1 | 🟡 Medium | `batchUpdate` 无数组大小限制 | SiteSettingController:77 | 遗留（仍未修复） |
| 2 | 🟡 Medium | `batchUpdate` 无逐项 key/value 校验 | SiteSettingService:batchSet | 遗留（仍未修复） |
| 3 | 🟢 Low | FormRequest `authorize()` 全返回 `true` | 所有 FormRequest | 遗留（防御性缺失） |
| 4 | 🟢 Low | `batchSet` 无 key 白名单 | SiteSettingService | 架构性（建议） |

**后端授权架构健康度：良好（路由/Policy/Gate 三层保护到位）**
**后端数据完整性：存在缺陷（batchUpdate 缺输入校验）**

---

## 五、修复优先级

1. **高优先级**：问题 #1（batchUpdate 数组限制）— 防止恶意管理员 DoS
2. **中优先级**：问题 #2（逐项校验）— 数据完整性 + 防止意外数据
3. **低优先级**：问题 #3（FormRequest authorize）— 防御性增强
4. **建议**：问题 #4（key 白名单）— 长期安全加固
