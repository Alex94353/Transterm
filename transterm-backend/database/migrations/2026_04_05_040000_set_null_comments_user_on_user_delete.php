<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function shouldSkipForCurrentDriver(): bool
    {
        return DB::getDriverName() !== 'mysql';
    }

    private function constraintExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->exists();
    }

    private function columnIsNullable(string $table, string $column): bool
    {
        $columnMeta = DB::table('information_schema.COLUMNS')
            ->select('IS_NULLABLE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->first();

        return strtoupper((string) ($columnMeta->IS_NULLABLE ?? 'NO')) === 'YES';
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        $constraint = "{$table}_{$column}_foreign";

        if (! $this->constraintExists($table, $constraint)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->dropForeign([$column]);
        });
    }

    private function addSetNullForeignIfMissing(string $table, string $column, string $referencesTable): void
    {
        $constraint = "{$table}_{$column}_foreign";

        if ($this->constraintExists($table, $constraint)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencesTable) {
            $blueprint->foreign($column)
                ->references('id')
                ->on($referencesTable)
                ->nullOnDelete();
        });
    }

    private function addCascadeForeignIfMissing(string $table, string $column, string $referencesTable): void
    {
        $constraint = "{$table}_{$column}_foreign";

        if ($this->constraintExists($table, $constraint)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencesTable) {
            $blueprint->foreign($column)
                ->references('id')
                ->on($referencesTable)
                ->cascadeOnDelete();
        });
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ($this->shouldSkipForCurrentDriver()) {
            return;
        }

        $this->dropForeignIfExists('comments', 'user_id');

        if (! $this->columnIsNullable('comments', 'user_id')) {
            DB::statement('ALTER TABLE `comments` MODIFY `user_id` BIGINT UNSIGNED NULL');
        }

        $this->addSetNullForeignIfMissing('comments', 'user_id', 'users');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->shouldSkipForCurrentDriver()) {
            return;
        }

        $this->dropForeignIfExists('comments', 'user_id');
        $this->addCascadeForeignIfMissing('comments', 'user_id', 'users');

        // Avoid rollback failure when nullable values already exist.
        $hasNullUsers = DB::table('comments')->whereNull('user_id')->exists();

        if (! $hasNullUsers && $this->columnIsNullable('comments', 'user_id')) {
            DB::statement('ALTER TABLE `comments` MODIFY `user_id` BIGINT UNSIGNED NOT NULL');
        }
    }
};
