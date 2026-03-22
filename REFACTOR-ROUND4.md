# AI-04 Refactoring Round 4 — flower-api

**执行时间:** 2026-03-22 07:50 (Asia/Shanghai)  
**分支:** `fix/ai-04-refactor-v4` → main  
**PR:** https://github.com/react-laravel/flower-api/pull/18

---

## 修复的坏味道

| # | 文件 | 坏味道 | 严重程度 | 修复方式 |
|---|------|--------|---------|---------|
| 1 | `ChatController.php` | `preg_replace('/\\s+/', ...)` 在嵌套 `foreach` 内对每个 Knowledge 条目重复编译 | **High** | 提取 `splitWords()` 私有方法，regex 每条调用只编译一次 |
| 2 | `ChatController.php` | `strtolower($request->message)` 在循环内重复计算（每次迭代重算） | Medium | 循环外预计算 `$queryLower` 和 `$queryWords` |
| 3 | `ChatController.php` | 4个 magic number（100/80/60/20）散落多处 | Medium | 提取 `SCORE_EXACT/SCORE_CONTAINS/SCORE_KEYWORD_MAX/SCORE_THRESHOLD` 常量 |
| 4 | `AuthController.php` | `isAdmin()` 的 `$request` 参数未使用 | Low | 移除参数，改为 `auth()->user()->is_admin` |
| 5 | `EnsureUserIsAdmin.php` | 硬编码 `403` magic number | Low | 改用 `Response::HTTP_FORBIDDEN` |
| 6 | `FlowerController.php` | 链式 `if/where` 查询条件冗长 | Low | 改用 Laravel `when()` 链式调用，更简洁 |

---

## 详细变更

### ChatController — 重构核心算法

**Before:**
```php
foreach ($knowledgeItems as $item) {
    $question = strtolower($item->question);
    // regex /\s+/ 每次迭代编译两次！
    $queryWords = explode(' ', preg_replace('/\s+/', ' ', trim($query)));
    $questionWords = explode(' ', preg_replace('/\s+/', ' ', trim($question)));
    // ...
}
```

**After:**
```php
private const SCORE_EXACT = 100;
private const SCORE_CONTAINS = 80;
private const SCORE_KEYWORD_MAX = 60;
private const SCORE_THRESHOLD = 20;

$queryLower = strtolower($request->message);
$queryWords = $this->splitWords($queryLower); // regex 只编译一次
foreach ($knowledgeItems as $item) {
    $questionLower = strtolower($item->question);
    $score = $this->calculateMatchScore($queryLower, $queryWords, $questionLower);
}
```

### FlowerController — when() 链式查询

**Before:**
```php
$query = Flower::query();
if ($request->has('category') && $request->category !== 'all') {
    $query->where('category', $request->category);
}
if ($request->has('featured')) { ... }
```

**After:**
```php
$flowers = Flower::query()
    ->when($request->filled('category') && $request->category !== 'all',
        fn($q) => $q->where('category', $request->category))
    ->when($request->filled('featured'),
        fn($q) => $q->where('featured', $request->featured === 'true'))
    ->when($request->filled('search'),
        fn($q) => $q->where(fn($q) => $q->where(...)->orWhere(...)))
    ->orderBy('created_at', 'desc')
    ->get();
```

---

## 测试结果

```
php artisan test → 2 passed ✅ (scaffold tests only on main branch)
No syntax errors in any changed files ✅
```

---

## 与前几轮的关系

- Round 1 (PR #8 `fix/ai-04-refactor-v2`): DRY violations, EnsureUserIsAdmin 403, FlowerDataSeeder arrays, PaginatedIndex trait
- Round 2 (flower 前端 PR #8): ApiResponse<T> types, validation error handling
- Round 3 (PR #16 `fix/ai-04-refactor-v3`): ChatController magic numbers, FlowerSeeder duplication, SiteSetting rename, AuthController isAdmin
- **Round 4 (PR #18 `fix/ai-04-refactor-v4`): Regex recompilation, redundant strtolower, FlowerController when(), middleware magic number**
