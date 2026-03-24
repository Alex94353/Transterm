<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            return;
        }

        $this->ensureRoleGuardName($tableNames['roles']);
        $this->migrateLegacyPermissions($tableNames);
        $this->migrateUserRoleAssignments($tableNames['model_has_roles']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is data-transforming and intentionally has no automatic rollback.
    }

    private function ensureRoleGuardName(string $rolesTable): void
    {
        if (! Schema::hasTable($rolesTable)) {
            return;
        }

        if (! Schema::hasColumn($rolesTable, 'guard_name')) {
            Schema::table($rolesTable, static function (Blueprint $table) {
                $table->string('guard_name')->default('web')->after('name');
            });
        }

        DB::table($rolesTable)
            ->whereNull('guard_name')
            ->update(['guard_name' => 'web']);
    }

    private function migrateLegacyPermissions(array $tableNames): void
    {
        $permissionsTable = $tableNames['permissions'];

        if (! Schema::hasTable($permissionsTable)) {
            return;
        }

        if (! Schema::hasColumn($permissionsTable, 'role_id') || ! Schema::hasColumn($permissionsTable, 'data')) {
            return;
        }

        if (! Schema::hasColumn($permissionsTable, 'name')) {
            Schema::table($permissionsTable, static function (Blueprint $table) {
                $table->string('name')->nullable()->after('id');
            });
        }

        if (! Schema::hasColumn($permissionsTable, 'guard_name')) {
            Schema::table($permissionsTable, static function (Blueprint $table) {
                $table->string('guard_name')->default('web')->after('name');
            });
        }

        $legacyRows = DB::table($permissionsTable)
            ->select(['id', 'role_id', 'data'])
            ->orderBy('id')
            ->get();

        $permissionRoleMap = [];

        foreach ($legacyRows as $row) {
            $decoded = json_decode((string) ($row->data ?? ''), true);

            if (! is_array($decoded)) {
                continue;
            }

            $names = $this->flattenPermissionData($decoded);

            foreach ($names as $name) {
                $permissionRoleMap[$name][] = (int) $row->role_id;
            }
        }

        $permissionRoleMap = array_map(static function (array $roleIds): array {
            return array_values(array_unique(array_filter($roleIds)));
        }, $permissionRoleMap);

        if (Schema::hasTable($tableNames['model_has_permissions'])) {
            DB::table($tableNames['model_has_permissions'])->delete();
        }

        if (Schema::hasTable($tableNames['role_has_permissions'])) {
            DB::table($tableNames['role_has_permissions'])->delete();
        }

        DB::table($permissionsTable)->delete();

        $permissionIds = [];
        $now = now();

        foreach (array_keys($permissionRoleMap) as $permissionName) {
            $permissionIds[$permissionName] = DB::table($permissionsTable)->insertGetId([
                'name' => $permissionName,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable($tableNames['role_has_permissions'])) {
            $rowsToInsert = [];

            foreach ($permissionRoleMap as $permissionName => $roleIds) {
                $permissionId = $permissionIds[$permissionName] ?? null;

                if (! $permissionId) {
                    continue;
                }

                foreach ($roleIds as $roleId) {
                    $rowsToInsert[] = [
                        'permission_id' => $permissionId,
                        'role_id' => $roleId,
                    ];
                }
            }

            if (! empty($rowsToInsert)) {
                DB::table($tableNames['role_has_permissions'])->insertOrIgnore($rowsToInsert);
            }
        }

        try {
            Schema::table($permissionsTable, static function (Blueprint $table) {
                $table->dropForeign(['role_id']);
            });
        } catch (Throwable $e) {
            // Ignore if FK does not exist or has already been dropped.
        }

        Schema::table($permissionsTable, static function (Blueprint $table) {
            $table->dropColumn(['role_id', 'data']);
        });
    }

    private function migrateUserRoleAssignments(string $modelHasRolesTable): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role_id') || ! Schema::hasTable($modelHasRolesTable)) {
            return;
        }

        $rows = DB::table('users')
            ->select(['id', 'role_id'])
            ->whereNotNull('role_id')
            ->get();

        $pivotRows = [];

        foreach ($rows as $row) {
            $pivotRows[] = [
                'role_id' => (int) $row->role_id,
                'model_type' => 'App\\Models\\User',
                'model_id' => (int) $row->id,
            ];
        }

        if (! empty($pivotRows)) {
            DB::table($modelHasRolesTable)->insertOrIgnore($pivotRows);
        }

        try {
            Schema::table('users', static function (Blueprint $table) {
                $table->dropConstrainedForeignId('role_id');
            });
        } catch (Throwable $e) {
            try {
                Schema::table('users', static function (Blueprint $table) {
                    $table->dropForeign(['role_id']);
                });
            } catch (Throwable $inner) {
                // Ignore if FK does not exist or already removed.
            }

            if (Schema::hasColumn('users', 'role_id')) {
                Schema::table('users', static function (Blueprint $table) {
                    $table->dropColumn('role_id');
                });
            }
        }
    }

    /**
     * Convert nested permission JSON to dot-notated permission names.
     * Example: {"terms":{"read":true}} => ["terms.read"]
     *
     * @return array<int, string>
     */
    private function flattenPermissionData(array $data, string $prefix = ''): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $key = (string) $key;
            $path = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenPermissionData($value, $path));
                continue;
            }

            if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                $result[] = $path;
            }
        }

        return array_values(array_unique($result));
    }
};
