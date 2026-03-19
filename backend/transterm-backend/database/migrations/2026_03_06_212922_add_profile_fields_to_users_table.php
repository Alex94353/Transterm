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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->default(1)
                ->after('id')
                ->constrained('roles');

            $table->string('username', 50)
                ->nullable()
                ->after('role_id');

            $table->string('surname', 255)
                ->nullable()
                ->after('name');

            $table->string('language', 50)
                ->default('slovak')
                ->after('email');

            $table->boolean('activated')
                ->default(false)
                ->after('language');

            $table->boolean('banned')
                ->default(false)
                ->after('activated');

            $table->string('ban_reason')
                ->nullable()
                ->after('banned');

            $table->foreignId('country_id')
                ->nullable()
                ->after('ban_reason')
                ->constrained('countries')
                ->nullOnDelete();

            $table->boolean('visible')
                ->default(false)
                ->after('country_id');

            $table->timestamp('last_login')
                ->nullable()
                ->after('visible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropConstrainedForeignId('country_id');

            $table->dropColumn([
                'username',
                'surname',
                'language',
                'activated',
                'banned',
                'ban_reason',
                'visible',
                'last_login',
            ]);
        });
    }
};
