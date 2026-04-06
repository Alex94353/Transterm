<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    public function test_view_allows_same_user(): void
    {
        $policy = new UserPolicy();
        $user = new User(['id' => 15]);
        $target = new User(['id' => 15]);

        $this->assertTrue($policy->view($user, $target));
    }

    public function test_view_denies_different_user(): void
    {
        $policy = new UserPolicy();
        $user = new User(['id' => 15]);
        $target = new User(['id' => 16]);

        $this->assertFalse($policy->view($user, $target));
    }

    public function test_update_follows_same_rule_as_view(): void
    {
        $policy = new UserPolicy();
        $user = new User(['id' => 21]);
        $ownProfile = new User(['id' => 21]);
        $foreignProfile = new User(['id' => 22]);

        $this->assertTrue($policy->update($user, $ownProfile));
        $this->assertFalse($policy->update($user, $foreignProfile));
    }
}
