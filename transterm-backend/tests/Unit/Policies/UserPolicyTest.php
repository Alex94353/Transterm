<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Mockery;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    public function test_view_allows_same_user(): void
    {
        $policy = new UserPolicy();
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 15;
        $user->shouldReceive('can')->with('user.view')->andReturn(true);
        $target = new User(['id' => 15]);

        $this->assertTrue($policy->view($user, $target));
    }

    public function test_view_denies_different_user(): void
    {
        $policy = new UserPolicy();
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 15;
        $user->shouldReceive('can')->with('user.view')->andReturn(true);
        $target = new User(['id' => 16]);

        $this->assertFalse($policy->view($user, $target));
    }

    public function test_update_follows_same_rule_as_view(): void
    {
        $policy = new UserPolicy();
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 21;
        $user->shouldReceive('can')->with('user.update')->andReturn(true);
        $ownProfile = new User(['id' => 21]);
        $foreignProfile = new User(['id' => 22]);

        $this->assertTrue($policy->update($user, $ownProfile));
        $this->assertFalse($policy->update($user, $foreignProfile));
    }
}
