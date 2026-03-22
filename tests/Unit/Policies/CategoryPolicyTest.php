<?php

namespace Tests\Unit\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\CategoryPolicy;
use Tests\TestCase;

class CategoryPolicyTest extends TestCase
{
    private CategoryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CategoryPolicy();
    }

    public function test_view_any_returns_true_for_any_user(): void
    {
        $user = new User();
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_view_returns_true_for_any_user(): void
    {
        $user = new User();
        $category = new Category();
        $this->assertTrue($this->policy->view($user, $category));
    }

    public function test_create_returns_true_for_admin_user(): void
    {
        $admin = new User(['is_admin' => true]);
        $this->assertTrue($this->policy->create($admin));
    }

    public function test_create_returns_false_for_non_admin_user(): void
    {
        $user = new User(['is_admin' => false]);
        $this->assertFalse($this->policy->create($user));
    }

    public function test_update_returns_true_for_admin_user(): void
    {
        $admin = new User(['is_admin' => true]);
        $category = new Category();
        $this->assertTrue($this->policy->update($admin, $category));
    }

    public function test_update_returns_false_for_non_admin_user(): void
    {
        $user = new User(['is_admin' => false]);
        $category = new Category();
        $this->assertFalse($this->policy->update($user, $category));
    }

    public function test_delete_returns_true_for_admin_user(): void
    {
        $admin = new User(['is_admin' => true]);
        $category = new Category();
        $this->assertTrue($this->policy->delete($admin, $category));
    }

    public function test_delete_returns_false_for_non_admin_user(): void
    {
        $user = new User(['is_admin' => false]);
        $category = new Category();
        $this->assertFalse($this->policy->delete($user, $category));
    }
}
