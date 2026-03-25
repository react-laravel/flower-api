<?php

namespace Tests\Unit\Policies\Traits;

use App\Models\User;
use App\Policies\Traits\AdminAccessControl;
use Tests\TestCase;

/**
 * Test for AdminAccessControl trait.
 *
 * This trait provides shared admin-only access control for policies.
 * Note: view, viewAny, and delete are tested in individual policy tests
 * because they had to be moved out of the trait due to method name
 * conflicts with Illuminate\Foundation\Testing\TestCase methods.
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
        $this->adminUser = new User();
        $this->adminUser->is_admin = true;
        $this->regularUser = new User();
        $this->regularUser->is_admin = false;
        $this->model = new User();
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
}
