<?php

namespace App\Policies;

use App\Models\Glossary;
use App\Models\User;
use App\Policies\Concerns\HandlesPolicyPermissions;

class GlossaryPolicy
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

    public function view(?User $user, Glossary $glossary): bool
    {
        if ($glossary->approved && $glossary->is_public) {
            return true;
        }

        return $user !== null && (int) $user->id === (int) $glossary->owner_id;
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'glossary.create');
    }

    public function update(User $user, Glossary $glossary): bool
    {
        return $this->hasPermission($user, 'glossary.update')
            && (int) $user->id === (int) $glossary->owner_id;
    }

    public function delete(User $user, Glossary $glossary): bool
    {
        return $this->hasPermission($user, 'glossary.delete')
            && (int) $user->id === (int) $glossary->owner_id;
    }

    public function approve(User $user, Glossary $glossary): bool
    {
        if (! $this->hasPermission($user, 'glossary.approve')) {
            return false;
        }

        if (! $user->hasRole('Teacher') || ! $user->supportsTeacherRoleByEmail()) {
            return false;
        }

        if ((int) $user->id === (int) $glossary->owner_id) {
            return true;
        }

        $glossary->loadMissing('owner.roles:id,name');
        $owner = $glossary->owner;

        return $owner !== null
            && $owner->hasRole('Student')
            && $owner->supportsStudentRoleByEmail();
    }
}
