<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->dropForeign(['field_group_id']);
            $table->foreign('field_group_id')
                ->references('id')
                ->on('field_groups')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->dropForeign(['field_group_id']);
            $table->foreign('field_group_id')
                ->references('id')
                ->on('field_groups')
                ->cascadeOnDelete();
        });
    }
};
