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

        $this->assertNull($policy->before($user, 'viewAny'));
    }

    public function test_view_methods_require_permission(): void
    {
        $policy = new FieldPolicy();
        $field = new Field();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('field.view-any')->andReturn(true);
        $user->shouldReceive('can')->with('field.view')->andReturn(true);

        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->viewAny(null));

        $this->assertTrue($policy->view($user, $field));
        $this->assertFalse($policy->view(null, $field));
    }

    public function test_view_methods_allow_editor_access_fallback(): void
    {
        $policy = new FieldPolicy();
        $field = new Field();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('field.view-any')->andReturn(false);
        $user->shouldReceive('can')->with('editor.access')->andReturn(true);
        $user->shouldReceive('can')->with('field.view')->andReturn(false);
        $user->shouldReceive('can')->with('editor.access')->andReturn(true);

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $field));
    }

    public function test_mutating_methods_require_permissions(): void
    {
        $policy = new FieldPolicy();
        $field = new Field();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('field.create')->andReturn(true);
        $user->shouldReceive('can')->with('field.update')->andReturn(true);
        $user->shouldReceive('can')->with('field.delete')->andReturn(true);

        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, $field));
        $this->assertTrue($policy->delete($user, $field));
    }
}
