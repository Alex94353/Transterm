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

        $this->assertNull($policy->before($user, 'update'));
    }

    public function test_create_requires_comment_create_permission(): void
    {
        $policy = new CommentPolicy();
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('comment.create')->andReturn(true);

        $this->assertTrue($policy->create($user));
    }

    public function test_update_and_delete_allow_only_comment_author_with_permission(): void
    {
        $policy = new CommentPolicy();
        $owner = Mockery::mock(User::class)->makePartial();
        $owner->id = 10;
        $owner->shouldReceive('can')->with('comment.update')->andReturn(true);
        $owner->shouldReceive('can')->with('comment.delete')->andReturn(true);

        $otherUser = Mockery::mock(User::class)->makePartial();
        $otherUser->id = 11;
        $otherUser->shouldReceive('can')->with('comment.update')->andReturn(true);
        $otherUser->shouldReceive('can')->with('comment.delete')->andReturn(true);

        $comment = new Comment(['user_id' => 10]);

        $this->assertTrue($policy->update($owner, $comment));
        $this->assertFalse($policy->update($otherUser, $comment));

        $this->assertTrue($policy->delete($owner, $comment));
        $this->assertFalse($policy->delete($otherUser, $comment));
    }
}
