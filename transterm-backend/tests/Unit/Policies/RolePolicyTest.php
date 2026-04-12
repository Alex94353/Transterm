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
        $user->shouldReceive('getAllPermissions')->andReturn(collect());

        $this->assertNull($policy->before($user, 'viewAny'));
    }

    public function test_view_methods_require_authenticated_user(): void
    {
        $policy = new RolePolicy();
        $role = new Role();
        $user = new User(['id' => 14]);

        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->viewAny(null));

        $this->assertTrue($policy->view($user, $role));
        $this->assertFalse($policy->view(null, $role));
    }

    public function test_mutating_methods_are_denied_by_default(): void
    {
        $policy = new RolePolicy();
        $role = new Role();
        $user = new User(['id' => 14]);

        $this->assertFalse($policy->create($user));
        $this->assertFalse($policy->update($user, $role));
        $this->assertFalse($policy->delete($user, $role));
    }
}
