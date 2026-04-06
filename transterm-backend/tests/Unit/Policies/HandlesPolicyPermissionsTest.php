<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\Concerns\HandlesPolicyPermissions;
use Illuminate\Support\Collection;
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

            public function decide(User $user, string $resource, string $ability): ?bool
            {
                return $this->allowByRoleOrPermission($user, $resource, $ability);
            }
        };

        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->once()->with('admin.access')->andReturn(true);
        $user->shouldNotReceive('getAllPermissions');

        $this->assertTrue($policy->decide($user, 'glossary', 'update'));
    }

    public function test_kebab_case_permission_is_resolved(): void
    {
        $policy = new class
        {
            use HandlesPolicyPermissions;

            public function decide(User $user, string $resource, string $ability): ?bool
            {
                return $this->allowByRoleOrPermission($user, $resource, $ability);
            }
        };

        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->once()->with('admin.access')->andReturn(false);
        $user->shouldReceive('getAllPermissions')
            ->once()
            ->andReturn(new Collection([(object) ['name' => 'term.view-any']]));

        $this->assertTrue($policy->decide($user, 'term', 'viewAny'));
    }

    public function test_returns_null_when_no_permission_matches(): void
    {
        $policy = new class
        {
            use HandlesPolicyPermissions;

            public function decide(User $user, string $resource, string $ability): ?bool
            {
                return $this->allowByRoleOrPermission($user, $resource, $ability);
            }
        };

        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->once()->with('admin.access')->andReturn(false);
        $user->shouldReceive('getAllPermissions')
            ->once()
            ->andReturn(new Collection([(object) ['name' => 'field.view']]));

        $this->assertNull($policy->decide($user, 'glossary', 'delete'));
    }
}
