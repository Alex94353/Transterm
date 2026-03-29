<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\FieldGroup;
use Illuminate\Database\Seeder;

class FieldGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            ['id' => 1, 'name' => 'Humanitné vedy', 'code' => '060000'],
            ['id' => 2, 'name' => 'Prírodné vedy', 'code' => '010000'],
            ['id' => 3, 'name' => 'Pôdohospodárske vedy', 'code' => '040000'],
            ['id' => 4, 'name' => 'Technické vedy', 'code' => '020000'],
            ['id' => 5, 'name' => 'Spoločenské vedy', 'code' => '050000'],
            ['id' => 6, 'name' => 'Lekárske vedy', 'code' => '030000'],
            ['id' => 7, 'name' => 'Iné nezaradené vedy', 'code' => null],
        ];

        foreach ($groups as $group) {
            FieldGroup::updateOrCreate(['id' => $group['id']], $group);
        }
    }
}
