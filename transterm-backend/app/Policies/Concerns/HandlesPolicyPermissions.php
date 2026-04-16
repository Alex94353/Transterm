<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait HandlesPolicyPermissions
{
    protected function allowAdminBypass(User $user): ?bool
    {
        return $user->can('admin.access') ? true : null;
    }

    protected function hasPermission(User $user, string $permissionName): bool
    {
        return $user->can($permissionName);
    }
}
