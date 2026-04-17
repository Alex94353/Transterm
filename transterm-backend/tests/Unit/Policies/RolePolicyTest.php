<?php

namespace Tests\Unit\Policies;

use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use Mockery;
use Tests\TestCase;

class RolePolicyTest extends TestCase
{
    public function test_before_returns_null_when_user_has_no_matching_permission(): void
    {
        $policy = new RolePolicy();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('admin.access')->andReturn(false);

        $this->assertNull($policy->before($user, 'viewAny'));
    }

    public function test_view_methods_require_permissions(): void
    {
        $policy = new RolePolicy();
        $role = new Role();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('role.view-any')->andReturn(true);
        $user->shouldReceive('can')->with('role.view')->andReturn(true);

        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->viewAny(null));

        $this->assertTrue($policy->view($user, $role));
        $this->assertFalse($policy->view(null, $role));
    }

    public function test_mutating_methods_require_permissions(): void
    {
        $policy = new RolePolicy();
        $role = new Role();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('role.create')->andReturn(true);
        $user->shouldReceive('can')->with('role.update')->andReturn(true);
        $user->shouldReceive('can')->with('role.delete')->andReturn(true);

        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, $role));
        $this->assertTrue($policy->delete($user, $role));
    }
}
