<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Field;
use Illuminate\Database\Seeder;

class FieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            ['id' => 8, 'field_group_id' => 4, 'name' => 'Informačné a komunikačné technológie', 'code' => '020300'],
            ['id' => 20, 'field_group_id' => 2, 'name' => 'Počítačové a informatické vedy', 'code' => '010200'],
            ['id' => 38, 'field_group_id' => 5, 'name' => 'Ekonomické vedy a obchod', 'code' => '050200'],
            ['id' => 41, 'field_group_id' => 5, 'name' => 'Právne vedy', 'code' => '050500'],
            ['id' => 47, 'field_group_id' => 4, 'name' => 'Riadenie procesov (Management of processes)', 'code' => '020313'],
        ];

        foreach ($fields as $field) {
            Field::updateOrCreate(['id' => $field['id']], $field);
        }
    }
}
