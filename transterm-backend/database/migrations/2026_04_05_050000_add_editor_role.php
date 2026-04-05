<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = DB::table('roles')
            ->where('name', 'Editor')
            ->where('guard_name', 'web')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('roles')->insert([
            'name' => 'Editor',
            'guard_name' => 'web',
            'parent_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'Editor')
            ->where('guard_name', 'web')
            ->delete();
    }
};

