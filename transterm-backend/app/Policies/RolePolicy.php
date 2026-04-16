<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Policies\Concerns\HandlesPolicyPermissions;

class RolePolicy
{
    use HandlesPolicyPermissions;

    public function before(User $user, string $ability): ?bool
    {
        return $this->allowAdminBypass($user);
    }

    public function viewAny(?User $user): bool
    {
        return $user !== null && $this->hasPermission($user, 'role.view-any');
    }

    public function view(?User $user, Role $role): bool
    {
        return $user !== null && $this->hasPermission($user, 'role.view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'role.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $this->hasPermission($user, 'role.update');
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->hasPermission($user, 'role.delete');
    }
}
