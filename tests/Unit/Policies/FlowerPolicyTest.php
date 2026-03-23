<?php

namespace Tests\Unit\Policies;

use App\Models\Flower;
use App\Models\User;
use App\Policies\FlowerPolicy;
use Tests\TestCase;

class FlowerPolicyTest extends TestCase
{
    private FlowerPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new FlowerPolicy();
    }

    public function test_view_any_returns_true_for_any_user(): void
    {
        $user = new User();
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_view_returns_true_for_any_user(): void
    {
        $user = new User();
        $flower = new Flower();
        $this->assertTrue($this->policy->view($user, $flower));
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
        $flower = new Flower();
        $this->assertTrue($this->policy->update($admin, $flower));
    }

    public function test_update_returns_false_for_non_admin_user(): void
    {
        $user = new User(['is_admin' => false]);
        $flower = new Flower();
        $this->assertFalse($this->policy->update($user, $flower));
    }

    public function test_delete_returns_true_for_admin_user(): void
    {
        $admin = new User(['is_admin' => true]);
        $flower = new Flower();
        $this->assertTrue($this->policy->delete($admin, $flower));
    }

    public function test_delete_returns_false_for_non_admin_user(): void
    {
        $user = new User(['is_admin' => false]);
        $flower = new Flower();
        $this->assertFalse($this->policy->delete($user, $flower));
    }
}
