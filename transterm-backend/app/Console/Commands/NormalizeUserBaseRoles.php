<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class NormalizeUserBaseRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:normalize-base-roles {--dry-run : Preview role changes without writing them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize User/Student/Teacher base roles by email domain and drop Editor from base User accounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $scanned = 0;
        $baseRoleAdjusted = 0;
        $editorRemoved = 0;

        User::query()
            ->with(['roles:id,name'])
            ->chunkById(200, function ($users) use ($dryRun, &$scanned, &$baseRoleAdjusted, &$editorRemoved) {
                foreach ($users as $user) {
                    $scanned++;

                    $roleNames = $user->roles->pluck('name');
                    $currentBaseRoles = $roleNames
                        ->filter(fn (string $roleName) => in_array($roleName, User::BASE_ROLE_NAMES, true))
                        ->values();
                    $resolvedBaseRole = User::resolveBaseRoleByEmail((string) $user->email);

                    $needsBaseRoleUpdate = $currentBaseRoles->count() !== 1
                        || $currentBaseRoles->first() !== $resolvedBaseRole;

                    $shouldRemoveEditor = $resolvedBaseRole === 'User' && $roleNames->contains('Editor');

                    if (! $needsBaseRoleUpdate && ! $shouldRemoveEditor) {
                        continue;
                    }

                    if ($needsBaseRoleUpdate) {
                        $baseRoleAdjusted++;
                    }

                    if ($shouldRemoveEditor) {
                        $editorRemoved++;
                    }

                    if ($dryRun) {
                        continue;
                    }

                    foreach (User::BASE_ROLE_NAMES as $baseRoleName) {
                        if ($user->hasRole($baseRoleName)) {
                            $user->removeRole($baseRoleName);
                        }
                    }

                    $user->assignRole($resolvedBaseRole);

                    if ($shouldRemoveEditor && $user->hasRole('Editor')) {
                        $user->removeRole('Editor');
                    }
                }
            });

        $mode = $dryRun ? 'DRY RUN' : 'APPLIED';

        $this->info("Normalization {$mode} complete.");
        $this->line("Scanned users: {$scanned}");
        $this->line("Base roles adjusted: {$baseRoleAdjusted}");
        $this->line("Editor roles removed from base User accounts: {$editorRemoved}");

        return self::SUCCESS;
    }
}

