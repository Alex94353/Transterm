<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Support\Str;

trait HandlesPolicyPermissions
{
    protected function allowByRoleOrPermission(User $user, string $resource, string $ability): ?bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        $candidatePermissions = [
            Str::kebab($resource) . '.' . Str::kebab($ability),
            Str::snake($resource) . '.' . Str::snake($ability),
        ];

        $userPermissions = $user->getAllPermissions()->pluck('name');

        foreach ($candidatePermissions as $permissionName) {
            if ($userPermissions->contains($permissionName)) {
                return true;
            }
        }

        return null;
    }
}
