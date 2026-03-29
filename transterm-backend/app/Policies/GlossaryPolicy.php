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
        return $this->allowByRoleOrPermission($user, 'glossary', $ability);
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
        return true;
    }

    public function update(User $user, Glossary $glossary): bool
    {
        return (int) $user->id === (int) $glossary->owner_id;
    }

    public function delete(User $user, Glossary $glossary): bool
    {
        return (int) $user->id === (int) $glossary->owner_id;
    }
}
