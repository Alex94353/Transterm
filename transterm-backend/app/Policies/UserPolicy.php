<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\HandlesPolicyPermissions;

class UserPolicy
{
    use HandlesPolicyPermissions;

    public function before(User $user, string $ability): ?bool
    {
        return $this->allowByRoleOrPermission($user, 'user', $ability);
    }

    public function view(User $user, User $target): bool
    {
        return (int) $user->id === (int) $target->id;
    }

    public function update(User $user, User $target): bool
    {
        return (int) $user->id === (int) $target->id;
    }
}
