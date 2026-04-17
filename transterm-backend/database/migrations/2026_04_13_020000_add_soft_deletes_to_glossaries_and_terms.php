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
        Schema::table('glossaries', function (Blueprint $table) {
            if (! Schema::hasColumn('glossaries', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        Schema::table('terms', function (Blueprint $table) {
            if (! Schema::hasColumn('terms', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            if (Schema::hasColumn('terms', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('glossaries', function (Blueprint $table) {
            if (Schema::hasColumn('glossaries', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
