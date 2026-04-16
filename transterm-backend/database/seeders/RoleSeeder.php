<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'User'],
            ['id' => 2, 'name' => 'Admin'],
            ['id' => 3, 'name' => 'Student'],
            ['id' => 4, 'name' => 'Editor'],
            ['id' => 5, 'name' => 'Teacher'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['id' => $role['id']],
                [
                    'name' => $role['name'],
                    'guard_name' => 'web',
                    'parent_id' => null,
                ]
            );
        }
    }
}
