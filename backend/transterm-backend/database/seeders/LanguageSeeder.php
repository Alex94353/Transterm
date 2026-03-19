<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['name' => 'slovenčina', 'code' => 'sk', 'flag_path' => '../assets/flags/SVK.GIF'],
            ['name' => 'angličtina', 'code' => 'en', 'flag_path' => '../assets/flags/UNKG.GIF'],
            ['name' => 'nemčina', 'code' => 'de', 'flag_path' => '../assets/flags/GER.GIF'],
            ['name' => 'francúzština', 'code' => 'fr', 'flag_path' => '../assets/flags/FRA.GIF'],
            ['name' => 'ruština', 'code' => 'ru', 'flag_path' => '../assets/flags/RUS.GIF'],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                $language
            );
        }
    }
}
