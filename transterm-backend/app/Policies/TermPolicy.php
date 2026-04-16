<?php

namespace App\Policies;

use App\Models\Term;
use App\Models\User;
use App\Policies\Concerns\HandlesPolicyPermissions;

class TermPolicy
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

    public function view(?User $user, Term $term): bool
    {
        $term->loadMissing('glossary:id,approved,is_public');

        if ($term->glossary && $term->glossary->approved && $term->glossary->is_public) {
            return true;
        }

        return $user !== null && (int) $user->id === (int) $term->created_by;
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'term.create');
    }

    public function update(User $user, Term $term): bool
    {
        return $this->hasPermission($user, 'term.update')
            && (int) $user->id === (int) $term->created_by;
    }

    public function delete(User $user, Term $term): bool
    {
        return $this->hasPermission($user, 'term.delete')
            && (int) $user->id === (int) $term->created_by;
    }
}
