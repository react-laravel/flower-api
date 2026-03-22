<?php

namespace Tests\Unit\Policies;

use App\Models\Knowledge;
use App\Models\User;
use App\Policies\KnowledgePolicy;
use Tests\TestCase;

class KnowledgePolicyTest extends TestCase
{
    private KnowledgePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new KnowledgePolicy();
    }

    public function test_view_any_returns_true_for_any_user(): void
    {
        $user = new User();
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_view_returns_true_for_any_user(): void
    {
        $user = new User();
        $knowledge = new Knowledge();
        $this->assertTrue($this->policy->view($user, $knowledge));
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
        $knowledge = new Knowledge();
        $this->assertTrue($this->policy->update($admin, $knowledge));
    }

    public function test_update_returns_false_for_non_admin_user(): void
    {
        $user = new User(['is_admin' => false]);
        $knowledge = new Knowledge();
        $this->assertFalse($this->policy->update($user, $knowledge));
    }

    public function test_delete_returns_true_for_admin_user(): void
    {
        $admin = new User(['is_admin' => true]);
        $knowledge = new Knowledge();
        $this->assertTrue($this->policy->delete($admin, $knowledge));
    }

    public function test_delete_returns_false_for_non_admin_user(): void
    {
        $user = new User(['is_admin' => false]);
        $knowledge = new Knowledge();
        $this->assertFalse($this->policy->delete($user, $knowledge));
    }
}
