<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            ['id' => 10, 'name' => 'Armenia'],
            ['id' => 50, 'name' => 'Czech Republic'],
            ['id' => 68, 'name' => 'Germany'],
            ['id' => 159, 'name' => 'Slovakia'],
            ['id' => 183, 'name' => 'United Kingdom'],
            ['id' => 184, 'name' => 'United States'],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(['id' => $country['id']], $country);
        }
    }
}
