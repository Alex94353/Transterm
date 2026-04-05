<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function constraintExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->exists();
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

    private function addRestrictForeignIfMissing(string $table, string $column, string $referencesTable): void
    {
        $constraint = "{$table}_{$column}_foreign";

        if ($this->constraintExists($table, $constraint)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencesTable) {
            $blueprint->foreign($column)
                ->references('id')
                ->on($referencesTable)
                ->restrictOnDelete();
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
        $this->dropForeignIfExists('terms', 'glossary_id');
        $this->addRestrictForeignIfMissing('terms', 'glossary_id', 'glossaries');

        $this->dropForeignIfExists('glossaries', 'owner_id');
        $this->addRestrictForeignIfMissing('glossaries', 'owner_id', 'users');

        $this->dropForeignIfExists('terms', 'created_by');
        $this->addRestrictForeignIfMissing('terms', 'created_by', 'users');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropForeignIfExists('terms', 'created_by');
        $this->addCascadeForeignIfMissing('terms', 'created_by', 'users');

        $this->dropForeignIfExists('glossaries', 'owner_id');
        $this->addCascadeForeignIfMissing('glossaries', 'owner_id', 'users');

        $this->dropForeignIfExists('terms', 'glossary_id');
        $this->addCascadeForeignIfMissing('terms', 'glossary_id', 'glossaries');
    }
};

