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
        return $this->allowAdminBypass($user);
    }

    public function viewAny(?User $user): bool
    {
        return $user !== null && $this->hasPermission($user, 'language.view-any');
    }

    public function view(?User $user, Language $language): bool
    {
        return $user !== null && $this->hasPermission($user, 'language.view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'language.create');
    }

    public function update(User $user, Language $language): bool
    {
        return $this->hasPermission($user, 'language.update');
    }

    public function delete(User $user, Language $language): bool
    {
        return $this->hasPermission($user, 'language.delete');
    }
}
