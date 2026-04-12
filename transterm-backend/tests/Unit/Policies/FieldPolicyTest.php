<?php

namespace Tests\Unit\Policies;

use App\Models\Field;
use App\Models\User;
use App\Policies\FieldPolicy;
use Mockery;
use Tests\TestCase;

class FieldPolicyTest extends TestCase
{
    public function test_before_returns_null_when_user_has_no_matching_permission(): void
    {
        $policy = new FieldPolicy();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('admin.access')->andReturn(false);
        $user->shouldReceive('getAllPermissions')->andReturn(collect());

        $this->assertNull($policy->before($user, 'viewAny'));
    }

    public function test_view_methods_require_authenticated_user(): void
    {
        $policy = new FieldPolicy();
        $field = new Field();
        $user = new User(['id' => 1]);

        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->viewAny(null));

        $this->assertTrue($policy->view($user, $field));
        $this->assertFalse($policy->view(null, $field));
    }

    public function test_mutating_methods_are_denied_by_default(): void
    {
        $policy = new FieldPolicy();
        $field = new Field();
        $user = new User(['id' => 1]);

        $this->assertFalse($policy->create($user));
        $this->assertFalse($policy->update($user, $field));
        $this->assertFalse($policy->delete($user, $field));
    }
}
