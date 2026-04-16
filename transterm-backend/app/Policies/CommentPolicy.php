<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use App\Policies\Concerns\HandlesPolicyPermissions;

class CommentPolicy
{
    use HandlesPolicyPermissions;

    public function before(User $user, string $ability): ?bool
    {
        return $this->allowAdminBypass($user);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'comment.create');
    }

    public function update(User $user, Comment $comment): bool
    {
        return $this->hasPermission($user, 'comment.update')
            && (int) $user->id === (int) $comment->user_id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $this->hasPermission($user, 'comment.delete')
            && (int) $user->id === (int) $comment->user_id;
    }
}
