<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-users {--limit=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy users import...');

        $query = DB::connection('legacy')
            ->table('users')
            ->orderBy('id');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $legacyUsers = $query->get();

        foreach ($legacyUsers as $legacyUser) {
            $user = User::updateOrCreate(
                ['id' => $legacyUser->id],
                [
                    'role_id'           => $this->mapRoleId($legacyUser->role_id),
                    'username'          => $this->nullIfEmpty($legacyUser->username),
                    'name'              => $this->nullIfEmpty($legacyUser->name),
                    'surname'           => $this->nullIfEmpty($legacyUser->surname),
                    'email'             => $legacyUser->email,
                    'language'          => $this->nullIfEmpty($legacyUser->language) ?? 'slovak',
                    'activated'         => (bool) ($legacyUser->activated ?? 0),
                    'banned'            => (bool) ($legacyUser->banned ?? 0),
                    'ban_reason'        => $this->nullIfEmpty($legacyUser->ban_reason),
                    'country_id'        => $this->normalizeCountryId($legacyUser->country_id ?? null),
                    'visible'           => (bool) ($legacyUser->visible ?? 0),
                    'last_login'        => $this->normalizeDateTime($legacyUser->last_login ?? null),
                    'password'          => $legacyUser->password, // legacy hash as-is
                    'email_verified_at' => null,
                ]
            );

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'expertise' => $this->nullIfEmpty($legacyUser->expertise),
                    'about'     => $this->nullIfEmpty($legacyUser->about),
                    'website'   => $this->nullIfEmpty($legacyUser->website),
                    'telephone' => $this->nullIfEmpty($legacyUser->telephone),
                    'facebook'  => $this->nullIfEmpty($legacyUser->facebook),
                    'linkedin'  => $this->nullIfEmpty($legacyUser->linkedin),
                    'twitter'   => $this->nullIfEmpty($legacyUser->twitter),
                ]
            );
        }

        $this->info('Legacy users import finished.');
        $this->info('Users in new DB: ' . User::count());
        $this->info('User profiles in new DB: ' . UserProfile::count());

        return self::SUCCESS;
    }

    protected function mapRoleId(?int $legacyRoleId): int
    {
        return match ((int) $legacyRoleId) {
            2 => 2,
            3 => 3,
            default => 1,
        };
    }

    protected function normalizeCountryId($countryId): ?int
    {
        if (empty($countryId)) {
            return null;
        }

        $exists = DB::table('countries')->where('id', $countryId)->exists();

        return $exists ? (int) $countryId : null;
    }

    protected function normalizeDateTime($value): ?string
    {
        if (! $value || $value === '0000-00-00 00:00:00') {
            return null;
        }

        return $value;
    }

    protected function nullIfEmpty($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
