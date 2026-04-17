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
            if (! Schema::hasColumn('glossaries', 'approved_by')) {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->after('approved')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('glossaries', 'approved_at')) {
                $table->timestamp('approved_at')
                    ->nullable()
                    ->after('approved_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('glossaries', function (Blueprint $table) {
            if (Schema::hasColumn('glossaries', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }

            if (Schema::hasColumn('glossaries', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};
