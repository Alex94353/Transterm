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
            $table->index(['approved', 'is_public'], 'glossaries_approved_public_idx');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index(['is_spam', 'id'], 'comments_spam_id_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['activated', 'banned', 'id'], 'users_activated_banned_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_activated_banned_id_idx');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_spam_id_idx');
        });

        Schema::table('glossaries', function (Blueprint $table) {
            $table->dropIndex('glossaries_approved_public_idx');
        });
    }
};

