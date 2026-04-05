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
        $this->dropForeignIfExists('language_pairs', 'source_language_id');
        $this->dropForeignIfExists('language_pairs', 'target_language_id');
        $this->addRestrictForeignIfMissing('language_pairs', 'source_language_id', 'languages');
        $this->addRestrictForeignIfMissing('language_pairs', 'target_language_id', 'languages');

        $this->dropForeignIfExists('glossaries', 'language_pair_id');
        $this->dropForeignIfExists('glossaries', 'field_id');
        $this->addRestrictForeignIfMissing('glossaries', 'language_pair_id', 'language_pairs');
        $this->addRestrictForeignIfMissing('glossaries', 'field_id', 'fields');

        $this->dropForeignIfExists('terms', 'field_id');
        $this->addRestrictForeignIfMissing('terms', 'field_id', 'fields');

        $this->dropForeignIfExists('glossary_translations', 'language_id');
        $this->addRestrictForeignIfMissing('glossary_translations', 'language_id', 'languages');

        $this->dropForeignIfExists('term_translations', 'language_id');
        $this->addRestrictForeignIfMissing('term_translations', 'language_id', 'languages');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropForeignIfExists('term_translations', 'language_id');
        $this->addCascadeForeignIfMissing('term_translations', 'language_id', 'languages');

        $this->dropForeignIfExists('glossary_translations', 'language_id');
        $this->addCascadeForeignIfMissing('glossary_translations', 'language_id', 'languages');

        $this->dropForeignIfExists('terms', 'field_id');
        $this->addCascadeForeignIfMissing('terms', 'field_id', 'fields');

        $this->dropForeignIfExists('glossaries', 'language_pair_id');
        $this->dropForeignIfExists('glossaries', 'field_id');
        $this->addCascadeForeignIfMissing('glossaries', 'language_pair_id', 'language_pairs');
        $this->addCascadeForeignIfMissing('glossaries', 'field_id', 'fields');

        $this->dropForeignIfExists('language_pairs', 'source_language_id');
        $this->dropForeignIfExists('language_pairs', 'target_language_id');
        $this->addCascadeForeignIfMissing('language_pairs', 'source_language_id', 'languages');
        $this->addCascadeForeignIfMissing('language_pairs', 'target_language_id', 'languages');
    }
};
