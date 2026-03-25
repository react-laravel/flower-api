<?php

namespace Tests\Unit\Policies\Traits;

use App\Models\User;
use App\Policies\Traits\AdminAccessControl;
use Tests\TestCase;

/**
 * Test for AdminAccessControl trait.
 *
 * This trait provides shared admin-only access control for policies.
 */
class AdminAccessControlTest extends TestCase
{
    use AdminAccessControl;

    private User $adminUser;
    private User $regularUser;
    private User $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = new User(['is_admin' => true]);
        $this->regularUser = new User(['is_admin' => false]);
        $this->model = new User();
    }

    // ============================================================
    // viewAny()
    // ============================================================

    public function test_view_any_returns_true_for_any_user(): void
    {
        $this->assertTrue($this->viewAny($this->adminUser));
        $this->assertTrue($this->viewAny($this->regularUser));
    }

    // ============================================================
    // view()
    // ============================================================

    public function test_view_returns_true_for_any_user(): void
    {
        $this->assertTrue($this->view($this->adminUser, $this->model));
        $this->assertTrue($this->view($this->regularUser, $this->model));
    }

    // ============================================================
    // create()
    // ============================================================

    public function test_create_returns_true_for_admin_user(): void
    {
        $this->assertTrue($this->create($this->adminUser));
    }

    public function test_create_returns_false_for_non_admin_user(): void
    {
        $this->assertFalse($this->create($this->regularUser));
    }

    // ============================================================
    // update()
    // ============================================================

    public function test_update_returns_true_for_admin_user(): void
    {
        $this->assertTrue($this->update($this->adminUser, $this->model));
    }

    public function test_update_returns_false_for_non_admin_user(): void
    {
        $this->assertFalse($this->update($this->regularUser, $this->model));
    }

    // ============================================================
    // delete()
    // ============================================================

    public function test_delete_returns_true_for_admin_user(): void
    {
        $this->assertTrue($this->delete($this->adminUser, $this->model));
    }

    public function test_delete_returns_false_for_non_admin_user(): void
    {
        $this->assertFalse($this->delete($this->regularUser, $this->model));
    }
}
