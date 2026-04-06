<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = [
            'admin.access',
            'editor.access',
            'editor.assess',

            'field.view-any',
            'field.view',
            'field.create',
            'field.update',
            'field.delete',

            'language.view-any',
            'language.view',
            'language.create',
            'language.update',
            'language.delete',

            'role.view-any',
            'role.view',
            'role.create',
            'role.update',
            'role.delete',

            'permission.view-any',
            'permission.view',
            'permission.create',
            'permission.update',
            'permission.delete',

            'comment.update',
            'comment.delete',

            'glossary.view-any',
            'glossary.view',
            'glossary.create',
            'glossary.update',
            'glossary.delete',

            'term.view-any',
            'term.view',
            'term.create',
            'term.update',
            'term.delete',

            'reference.view-any',
            'reference.view',
            'reference.create',
            'reference.update',
            'reference.delete',

            'user.view',
            'user.update',
        ];

        $permissions = collect($permissionNames)->map(function (string $permissionName) {
            return Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        });

        $permissionsByName = $permissions->keyBy('name');

        $rolePermissions = [
            'Admin' => $permissionNames,
            'Editor' => [
                'editor.access',
            ],
            'User' => [
                'comment.update',
                'comment.delete',
                'glossary.create',
                'glossary.update',
                'glossary.delete',
                'term.create',
                'term.update',
                'term.delete',
                'reference.create',
                'reference.update',
                'reference.delete',
                'user.view',
                'user.update',
            ],
            'Student' => [
                'comment.update',
                'comment.delete',
                'term.create',
                'term.update',
                'term.delete',
                'reference.create',
                'reference.update',
                'reference.delete',
                'user.view',
                'user.update',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissionList) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                continue;
            }

            $permissionsForRole = collect($permissionList)
                ->map(fn (string $permissionName) => $permissionsByName->get($permissionName))
                ->filter()
                ->values();

            if ($permissionsForRole->isNotEmpty()) {
                $role->givePermissionTo($permissionsForRole);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
