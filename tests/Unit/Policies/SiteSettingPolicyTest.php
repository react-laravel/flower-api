<?php

namespace Tests\Unit\Policies;

use App\Models\SiteSetting;
use App\Models\User;
use App\Policies\SiteSettingPolicy;
use Tests\TestCase;

class SiteSettingPolicyTest extends TestCase
{
    private SiteSettingPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SiteSettingPolicy();
    }

    public function test_view_any_returns_true_for_any_user(): void
    {
        $user = new User();
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_view_returns_true_for_any_user(): void
    {
        $user = new User();
        $setting = new SiteSetting();
        $this->assertTrue($this->policy->view($user, $setting));
    }

    public function test_update_returns_true_for_admin_user(): void
    {
        $admin = new User(['is_admin' => true]);
        $setting = new SiteSetting();
        $this->assertTrue($this->policy->update($admin, $setting));
    }

    public function test_update_returns_false_for_non_admin_user(): void
    {
        $user = new User(['is_admin' => false]);
        $setting = new SiteSetting();
        $this->assertFalse($this->policy->update($user, $setting));
    }
}
