<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
use App\Policies\Concerns\HandlesPolicyPermissions;

class PermissionPolicy
{
    use HandlesPolicyPermissions;

    public function before(User $user, string $ability): ?bool
    {
        return $this->allowAdminBypass($user);
    }

    public function viewAny(?User $user): bool
    {
        return $user !== null && $this->hasPermission($user, 'permission.view-any');
    }

    public function view(?User $user, Permission $permission): bool
    {
        return $user !== null && $this->hasPermission($user, 'permission.view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'permission.create');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $this->hasPermission($user, 'permission.update');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $this->hasPermission($user, 'permission.delete');
    }
}
