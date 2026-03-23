<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UploadPolicy;
use Tests\TestCase;

class UploadPolicyTest extends TestCase
{
    private UploadPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UploadPolicy();
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

    public function test_delete_returns_true_for_admin_user(): void
    {
        $admin = new User(['is_admin' => true]);
        $this->assertTrue($this->policy->delete($admin));
    }

    public function test_delete_returns_false_for_non_admin_user(): void
    {
        $user = new User(['is_admin' => false]);
        $this->assertFalse($this->policy->delete($user));
    }
}
