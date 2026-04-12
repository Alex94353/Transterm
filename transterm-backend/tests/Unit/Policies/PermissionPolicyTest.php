<?php

namespace Tests\Unit\Policies;

use App\Models\Permission;
use App\Models\User;
use App\Policies\PermissionPolicy;
use Mockery;
use Tests\TestCase;

class PermissionPolicyTest extends TestCase
{
    public function test_before_returns_null_when_user_has_no_matching_permission(): void
    {
        $policy = new PermissionPolicy();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('admin.access')->andReturn(false);
        $user->shouldReceive('getAllPermissions')->andReturn(collect());

        $this->assertNull($policy->before($user, 'viewAny'));
    }

    public function test_view_methods_require_authenticated_user(): void
    {
        $policy = new PermissionPolicy();
        $permission = new Permission();
        $user = new User(['id' => 12]);

        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->viewAny(null));

        $this->assertTrue($policy->view($user, $permission));
        $this->assertFalse($policy->view(null, $permission));
    }

    public function test_mutating_methods_are_denied_by_default(): void
    {
        $policy = new PermissionPolicy();
        $permission = new Permission();
        $user = new User(['id' => 12]);

        $this->assertFalse($policy->create($user));
        $this->assertFalse($policy->update($user, $permission));
        $this->assertFalse($policy->delete($user, $permission));
    }
}
