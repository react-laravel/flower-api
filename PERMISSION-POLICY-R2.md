# AI-03 Permission Policy Check - Round 2 (flower)

**检查时间：** 2026-03-22 05:32 (Asia/Shanghai)
**仓库：** react-laravel/flower-api (后端)

---

## 检查范围

- `policies/` - Laravel 授权策略类
- `permissions` - 权限定义
- `authorization` - 授权逻辑（Middleware, Gate, Controller 级别）
- 前后端权限一致性

---

## 发现的问题（6个）

| # | 问题 | 严重程度 | 描述 | 修复方式 |
|---|------|----------|------|---------|
| 1 | 缺少 Laravel Policy 类 | Medium | 没有 FlowerPolicy、CategoryPolicy、KnowledgePolicy 等授权策略类 | 新增 5 个 Policy 类 |
| 2 | 控制器无显式授权检查 | Medium | CRUD 操作仅依赖路由中间件，控制器层面无 Policy 授权 | 在 Flower/Category/KnowledgeController 中添加 `$this->authorize()` 调用 |
| 3 | SiteSetting 无授权策略 | Low | SiteSettingController 的 update/batchUpdate 仅依赖中间件 | 新增 SiteSettingPolicy 并在控制器中使用 |
| 4 | Upload 无模型级授权 | Low | UploadController 的授权逻辑散落在代码中 | 新增 UploadPolicy，使用 Gate::allows() 授权 |
| 5 | AppServiceProvider 未注册策略 | Medium | Policy 类存在但未在 Gate 中注册 | 更新 AppServiceProvider::boot() 注册所有策略 |
| 6 | 前端无权限状态管理 | Medium | 前端无 role/permission 状态，无 admin 指示器 | 在 api.ts 添加 authApi.isAdmin() 调用，添加类型定义 |

---

## 修复内容

### 后端 (flower-api)

#### 新增 Policy 类 (5个)

- `app/Policies/FlowerPolicy.php` - 花卉资源授权
- `app/Policies/CategoryPolicy.php` - 分类资源授权
- `app/Policies/KnowledgePolicy.php` - 知识库资源授权
- `app/Policies/SiteSettingPolicy.php` - 站点设置授权
- `app/Policies/UploadPolicy.php` - 文件上传授权

#### 更新控制器

- `FlowerController.php` - 添加 create/view/update/delete 授权检查
- `CategoryController.php` - 添加 create/view/update/delete 授权检查
- `KnowledgeController.php` - 添加 create/view/update/delete 授权检查
- `SiteSettingController.php` - 添加 update 授权检查
- `UploadController.php` - 使用 Gate::allows() 授权

#### 更新 AppServiceProvider

- 注册所有 Policy 到 Gate
- 定义 upload/upload.delete Gate 权限

### 前端 (flower)

#### 更新 api.ts

- 导出 `authApi.isAdmin()` 返回类型 `IsAdminResponse`
- 添加前端权限状态类型定义

---

## 架构说明：双重授权保护

```
路由层 → auth:sanctum (认证) + admin 中间件 (角色检查)
    ↓
控制器层 → $this->authorize() (Policy 授权检查)
    ↓
Policy 层 → is_admin 布尔判断
```

这种设计确保：
1. 即使路由中间件被绕过，控制器层仍有授权保护
2. 授权逻辑可独立测试
3. 未来可扩展资源所有权检查

---

## 安全边界

| 路由类型 | 中间件 | Policy 检查 | 公开访问 |
|---------|--------|-------------|---------|
| GET /flowers | 无 | viewAny, view | ✅ |
| POST /flowers | auth:sanctum + admin | create | ❌ |
| PUT/DELETE /flowers/{id} | auth:sanctum + admin | update/delete | ❌ |

---

## 待办（未实现，仅记录）

- [ ] 资源所有权检查（当前所有 admin 平等，未来可扩展）
- [ ] 角色分级（super-admin vs admin）
- [ ] 操作审计日志
- [ ] 权限变更通知

---

**Commit:** `53464ef` (AI-04 Round 2 已修复 magic number 403)
