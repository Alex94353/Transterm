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
        return $this->allowByRoleOrPermission($user, 'field', $ability);
    }

    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function view(?User $user, Field $field): bool
    {
        return $user !== null;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Field $field): bool
    {
        return false;
    }

    public function delete(User $user, Field $field): bool
    {
        return false;
    }
}
