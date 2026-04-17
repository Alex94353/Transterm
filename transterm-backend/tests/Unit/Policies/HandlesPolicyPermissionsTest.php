<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\Concerns\HandlesPolicyPermissions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class HandlesPolicyPermissionsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_admin_access_always_bypasses_other_checks(): void
    {
        $policy = new class
        {
            use HandlesPolicyPermissions;

            public function decide(User $user): ?bool
            {
                return $this->allowAdminBypass($user);
            }
        };

        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->once()->with('admin.access')->andReturn(true);

        $this->assertTrue($policy->decide($user));
    }

    public function test_non_admin_user_does_not_get_bypass(): void
    {
        $policy = new class
        {
            use HandlesPolicyPermissions;

            public function decide(User $user): ?bool
            {
                return $this->allowAdminBypass($user);
            }
        };

        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->once()->with('admin.access')->andReturn(false);

        $this->assertNull($policy->decide($user));
    }

    public function test_has_permission_proxies_to_user_can(): void
    {
        $policy = new class
        {
            use HandlesPolicyPermissions;

            public function decide(User $user, string $permission): bool
            {
                return $this->hasPermission($user, $permission);
            }
        };

        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->once()->with('glossary.update')->andReturn(true);

        $this->assertTrue($policy->decide($user, 'glossary.update'));
    }
}
