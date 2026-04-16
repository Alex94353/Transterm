<?php

namespace App\Policies;

use App\Models\Field;
use App\Models\User;
use App\Policies\Concerns\HandlesPolicyPermissions;

class FieldPolicy
{
    use HandlesPolicyPermissions;

    public function before(User $user, string $ability): ?bool
    {
        return $this->allowAdminBypass($user);
    }

    public function viewAny(?User $user): bool
    {
        return $user !== null
            && (
                $this->hasPermission($user, 'field.view-any')
                || $this->hasPermission($user, 'editor.access')
            );
    }

    public function view(?User $user, Field $field): bool
    {
        return $user !== null
            && (
                $this->hasPermission($user, 'field.view')
                || $this->hasPermission($user, 'editor.access')
            );
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'field.create');
    }

    public function update(User $user, Field $field): bool
    {
        return $this->hasPermission($user, 'field.update');
    }

    public function delete(User $user, Field $field): bool
    {
        return $this->hasPermission($user, 'field.delete');
    }
}
