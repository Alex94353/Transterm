<?php

namespace Tests\Unit\Policies;

use App\Models\Reference;
use App\Models\User;
use App\Policies\ReferencePolicy;
use Mockery;
use Tests\TestCase;

class ReferencePolicyTest extends TestCase
{
    public function test_before_returns_null_when_user_has_no_matching_permission(): void
    {
        $policy = new ReferencePolicy();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('admin.access')->andReturn(false);

        $this->assertNull($policy->before($user, 'update'));
    }

    public function test_public_capabilities_are_open(): void
    {
        $policy = new ReferencePolicy();
        $reference = new Reference();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('reference.create')->andReturn(true);

        $this->assertTrue($policy->viewAny(null));
        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view(null, $reference));
        $this->assertTrue($policy->view($user, $reference));
        $this->assertTrue($policy->create($user));
    }

    public function test_update_and_delete_allow_only_reference_owner_with_permission(): void
    {
        $policy = new ReferencePolicy();
        $owner = Mockery::mock(User::class)->makePartial();
        $owner->id = 7;
        $owner->shouldReceive('can')->with('reference.update')->andReturn(true);
        $owner->shouldReceive('can')->with('reference.delete')->andReturn(true);

        $otherUser = Mockery::mock(User::class)->makePartial();
        $otherUser->id = 8;
        $otherUser->shouldReceive('can')->with('reference.update')->andReturn(true);
        $otherUser->shouldReceive('can')->with('reference.delete')->andReturn(true);

        $reference = new Reference(['user_id' => 7]);

        $this->assertTrue($policy->update($owner, $reference));
        $this->assertFalse($policy->update($otherUser, $reference));

        $this->assertTrue($policy->delete($owner, $reference));
        $this->assertFalse($policy->delete($otherUser, $reference));
    }
}
