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

        $this->assertNull($policy->before($user, 'viewAny'));
    }

    public function test_view_methods_require_permissions(): void
    {
        $policy = new PermissionPolicy();
        $permission = new Permission();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('permission.view-any')->andReturn(true);
        $user->shouldReceive('can')->with('permission.view')->andReturn(true);

        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->viewAny(null));

        $this->assertTrue($policy->view($user, $permission));
        $this->assertFalse($policy->view(null, $permission));
    }

    public function test_mutating_methods_require_permissions(): void
    {
        $policy = new PermissionPolicy();
        $permission = new Permission();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('permission.create')->andReturn(true);
        $user->shouldReceive('can')->with('permission.update')->andReturn(true);
        $user->shouldReceive('can')->with('permission.delete')->andReturn(true);

        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, $permission));
        $this->assertTrue($policy->delete($user, $permission));
    }
}
