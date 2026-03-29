<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Language;
use App\Models\LanguagePair;
use Illuminate\Database\Seeder;

class LanguagePairSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sk = Language::where('code', 'sk')->firstOrFail();
        $en = Language::where('code', 'en')->firstOrFail();
        $de = Language::where('code', 'de')->firstOrFail();
        $fr = Language::where('code', 'fr')->firstOrFail();
        $ru = Language::where('code', 'ru')->firstOrFail();

        $pairs = [
            [$sk->id, $en->id],
            [$sk->id, $de->id],
            [$sk->id, $fr->id],
            [$sk->id, $ru->id],
            [$en->id, $sk->id],
            [$de->id, $sk->id],
            [$fr->id, $sk->id],
            [$ru->id, $sk->id],
        ];

        foreach ($pairs as [$sourceId, $targetId]) {
            LanguagePair::updateOrCreate([
                'source_language_id' => $sourceId,
                'target_language_id' => $targetId,
            ]);
        }
    }
}
