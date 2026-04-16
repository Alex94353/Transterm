<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\HandlesPolicyPermissions;

class UserPolicy
{
    use HandlesPolicyPermissions;

    public function before(User $user, string $ability): ?bool
    {
        return $this->allowAdminBypass($user);
    }

    public function view(User $user, User $target): bool
    {
        return $this->hasPermission($user, 'user.view')
            && (int) $user->id === (int) $target->id;
    }

    public function update(User $user, User $target): bool
    {
        return $this->hasPermission($user, 'user.update')
            && (int) $user->id === (int) $target->id;
    }
}
