<?php

namespace App\Policies;

use App\Models\Language;
use App\Models\User;
use App\Policies\Concerns\HandlesPolicyPermissions;

class LanguagePolicy
{
    use HandlesPolicyPermissions;

    public function before(User $user, string $ability): ?bool
    {
        return $this->allowByRoleOrPermission($user, 'language', $ability);
    }

    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function view(?User $user, Language $language): bool
    {
        return $user !== null;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Language $language): bool
    {
        return false;
    }

    public function delete(User $user, Language $language): bool
    {
        return false;
    }
}
