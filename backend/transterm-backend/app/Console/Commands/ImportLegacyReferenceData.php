<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Language;
use App\Models\LanguagePair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyReferenceData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-reference-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import reference data from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy reference data import...');

        $this->importCountries();
        $this->importLanguages();
        $this->importFieldGroups();
        $this->importFields();
        $this->importLanguagePairs();

        $this->info('Legacy reference data import finished.');

        return self::SUCCESS;
    }

    protected function importCountries(): void
    {
        $this->info('Importing countries...');

        $rows = DB::connection('legacy')
            ->table('country')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            Country::updateOrCreate(
                ['id' => $row->id],
                ['name' => $row->name]
            );
        }

        $this->info('Countries imported: ' . Country::count());
    }

    protected function importLanguages(): void
    {
        $this->info('Importing languages...');

        $languages = [
            ['name' => 'slovenčina',   'code' => 'sk', 'flag_path' => '../assets/flags/SVK.GIF'],
            ['name' => 'angličtina',   'code' => 'en', 'flag_path' => '../assets/flags/UNKG.GIF'],
            ['name' => 'nemčina',      'code' => 'de', 'flag_path' => '../assets/flags/GER.GIF'],
            ['name' => 'francúzština', 'code' => 'fr', 'flag_path' => '../assets/flags/FRA.GIF'],
            ['name' => 'ruština',      'code' => 'ru', 'flag_path' => '../assets/flags/RUS.GIF'],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                $language
            );
        }

        $this->info('Languages imported: ' . Language::count());
    }

    protected function importFieldGroups(): void
    {
        $this->info('Importing field groups...');

        $rows = DB::connection('legacy')
            ->table('okruh')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            FieldGroup::updateOrCreate(
                ['id' => $row->id],
                [
                    'name' => $row->nazov_okruh,
                    'code' => $row->kod_okruhu ?: null,
                ]
            );
        }

        $this->info('Field groups imported: ' . FieldGroup::count());
    }

    protected function importFields(): void
    {
        $this->info('Importing fields...');

        $rows = DB::connection('legacy')
            ->table('odbor')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $fieldGroup = FieldGroup::find($row->okruh_id);

            if (! $fieldGroup) {
                $this->warn("Skipping field {$row->id}: field group {$row->okruh_id} not found.");
                continue;
            }

            Field::updateOrCreate(
                ['id' => $row->id],
                [
                    'field_group_id' => $fieldGroup->id,
                    'name' => $row->nazov,
                    'code' => $row->kod ?: null,
                ]
            );
        }

        $this->info('Fields imported: ' . Field::count());
    }

    protected function importLanguagePairs(): void
    {
        $this->info('Importing language pairs...');

        $languageMap = [
            'slovenčina'   => 'sk',
            'angličtina'   => 'en',
            'nemčina'      => 'de',
            'francúzština' => 'fr',
            'ruština'      => 'ru',
        ];

        $tables = ['jazykova_kombinacia', 'jazykova_kombinacia_z'];

        foreach ($tables as $table) {
            $rows = DB::connection('legacy')
                ->table($table)
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                $sourceCode = $languageMap[$row->jazyk1] ?? null;
                $targetCode = $languageMap[$row->jazyk2] ?? null;

                if (! $sourceCode || ! $targetCode) {
                    $this->warn("Skipping pair {$row->id} from {$table}: unknown language.");
                    continue;
                }

                $sourceLanguage = Language::where('code', $sourceCode)->first();
                $targetLanguage = Language::where('code', $targetCode)->first();

                if (! $sourceLanguage || ! $targetLanguage) {
                    $this->warn("Skipping pair {$row->id}: languages not found.");
                    continue;
                }

                LanguagePair::updateOrCreate(
                    [
                        'source_language_id' => $sourceLanguage->id,
                        'target_language_id' => $targetLanguage->id,
                    ],
                    [
                        'source_language_id' => $sourceLanguage->id,
                        'target_language_id' => $targetLanguage->id,
                    ]
                );
            }
        }

        $this->info('Language pairs imported: ' . LanguagePair::count());
    }
}
