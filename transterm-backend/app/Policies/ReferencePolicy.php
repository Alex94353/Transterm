<?php

namespace App\Policies;

use App\Models\Reference;
use App\Models\User;
use App\Policies\Concerns\HandlesPolicyPermissions;

class ReferencePolicy
{
    use HandlesPolicyPermissions;

    public function before(User $user, string $ability): ?bool
    {
        return $this->allowAdminBypass($user);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Reference $reference): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'reference.create');
    }

    public function update(User $user, Reference $reference): bool
    {
        return $this->hasPermission($user, 'reference.update')
            && (int) $user->id === (int) $reference->user_id;
    }

    public function delete(User $user, Reference $reference): bool
    {
        return $this->hasPermission($user, 'reference.delete')
            && (int) $user->id === (int) $reference->user_id;
    }
}
