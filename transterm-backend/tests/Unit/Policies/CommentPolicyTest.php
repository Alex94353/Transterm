<?php

namespace Tests\Unit\Policies;

use App\Models\Comment;
use App\Models\User;
use App\Policies\CommentPolicy;
use Mockery;
use Tests\TestCase;

class CommentPolicyTest extends TestCase
{
    public function test_before_returns_null_when_user_has_no_matching_permission(): void
    {
        $policy = new CommentPolicy();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('admin.access')->andReturn(false);
        $user->shouldReceive('getAllPermissions')->andReturn(collect());

        $this->assertNull($policy->before($user, 'update'));
    }

    public function test_update_and_delete_allow_only_comment_author(): void
    {
        $policy = new CommentPolicy();
        $owner = new User(['id' => 10]);
        $otherUser = new User(['id' => 11]);
        $comment = new Comment(['user_id' => 10]);

        $this->assertTrue($policy->update($owner, $comment));
        $this->assertFalse($policy->update($otherUser, $comment));

        $this->assertTrue($policy->delete($owner, $comment));
        $this->assertFalse($policy->delete($otherUser, $comment));
    }
}
