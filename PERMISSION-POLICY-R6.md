# AI-03 权限策略检查（flower 第六轮）— 后端报告

**时间：** 2026-03-26 02:30 (Asia/Shanghai)
**仓库：** react-laravel/flower-api（后端）
**检查范围：** policies、permissions、authorization 逻辑

---

## 一、前置轮次问题状态确认（R5 → R6）

| 问题 | R5 状态 | R6 状态 |
|------|---------|---------|
| `batchUpdate` 无数组大小限制 | 🟡 Medium | ⚠️ **仍遗留** |
| `batchUpdate` 无逐项 key/value 校验 | 🟡 Medium | ⚠️ **仍遗留** |
| FormRequest `authorize()` 返回 `true` | 🟢 Low | ⚠️ **仍遗留** |

---

## 二、确认正确的实现（持续有效）

| 组件 | 状态 |
|------|------|
| 路由层：`auth:sanctum + admin` 中间件链 | ✅ |
| 控制层：`Gate::allows` + `$this->authorize()` | ✅ |
| Policy：`AdminAccessControl` trait（`is_admin` 检查） | ✅ |
| 模型层：`is_admin` 不在 `$fillable` 中 | ✅ |
| 限速：`throttle:5,1` / `throttle:10,1` / `throttle:30,1` | ✅ |
| 敏感数据：`SENSITIVE_PATTERNS` 过滤 | ✅ |
| 文件安全：路径遍历校验 + image mimes 限制 | ✅ |
| Token 撤销：`logout` 删除当前 token | ✅ |
| `CrudOperations`：`authorize('create', $modelClass)` 正确传递类名 | ✅ |
| `SiteSettingPolicy`：公开读取、受限写入 | ✅ |
| `UploadPolicy`：`create/delete` 限制为 admin | ✅ |

---

## 三、仍遗留的 Medium 问题

### 🟡 Medium 1：`batchUpdate` 无数组大小限制（3轮未修复）

**文件：** `app/Http/Controllers/Api/SiteSettingController.php:92`

```php
$settings = $request->validate([
    'settings' => 'required|array',  // ← 无 max 限制
]);
```

认证管理员可发送包含数万条记录的数组，每次 `SiteSetting::updateOrCreate()` 都是独立 DB 写入，造成 DoS。

**修复方案：**
```php
'settings' => 'required|array|max:100',  // 添加上限
```

---

### 🟡 Medium 2：`batchUpdate` 无逐项 key/value 校验（3轮未修复）

**文件：** `app/Http/Controllers/Api/SiteSettingController.php:92`

```php
'settings.*.key'   // 未校验
'settings.*.value' // 未校验
```

超长 key（>255 字节）可触发 DB 异常；任意 key 可被覆盖。

**修复方案：**
```php
'settings' => 'required|array|max:100',
'settings.*.key'   => 'required|string|max:255',
'settings.*.value' => 'nullable|string|max:10000',
```

---

## 四、仍遗留的 Low 问题

### 🟢 Low：FormRequest `authorize()` 全部返回 `true`

**文件：** 所有 FormRequest（`StoreFlowerRequest`、`UpdateFlowerRequest` 等）

```php
public function authorize(): bool
{
    return true;  // 完全依赖路由中间件
}
```

**缓解：** 路由层 `auth:sanctum + admin` 已到位，风险可控。
**建议：** 改为 `$this->authorize('create', Flower::class)` 进行防御性检查。

---

## 五、后端授权架构评级

**整体评级：A（架构）/ 🟡 Medium 缺陷存在**

后端授权架构设计合理，三层防御（路由 + 控制器 + Policy）到位。但 `batchUpdate` 输入校验问题已连续 3 轮未修复，建议优先处理。

**建议执行修复：**
```php
// flower-api/app/Http/Controllers/Api/SiteSettingController.php 第 92 行
$settings = $request->validate([
    'settings' => 'required|array|max:100',
    'settings.*.key'   => 'required|string|max:255',
    'settings.*.value' => 'nullable|string|max:10000',
]);
```
