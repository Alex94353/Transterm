<?php

namespace App\Console\Commands;

use App\Models\Glossary;
use App\Models\GlossaryTranslation;
use App\Models\LanguagePair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyGlossaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-glossaries {--limit=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import glossaries from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy glossaries import...');

        $query = DB::connection('legacy')
            ->table('glosar')
            ->orderBy('id');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $rows = $query->get();

        foreach ($rows as $row) {
            $ownerId = $this->normalizeUserId($row->user_id);
            $fieldId = $this->normalizeFieldId($row->odbor_id);
            $languagePairId = $this->normalizeLanguagePairId($row->jazyk_komb_id);

            if (! $ownerId || ! $fieldId || ! $languagePairId) {
                $this->warn("Skipping glossary {$row->id}: missing owner, field or language pair.");
                continue;
            }

            $glossary = Glossary::updateOrCreate(
                ['id' => $row->id],
                [
                    'owner_id' => $ownerId,
                    'language_pair_id' => $languagePairId,
                    'field_id' => $fieldId,
                    'approved' => (bool) ($row->schvaleny ?? 0),
                    'is_public' => (bool) ($row->viditelnost ?? 0),
                    'created_at' => $this->normalizeDate($row->date_created),
                    'updated_at' => $this->normalizeDateTime($row->date_modified),
                ]
            );

            $languagePair = LanguagePair::with(['sourceLanguage', 'targetLanguage'])->find($languagePairId);

            if (! $languagePair) {
                $this->warn("Skipping glossary translations for {$row->id}: language pair not found.");
                continue;
            }

            if ($title1 = $this->nullIfEmpty($row->nazovJ1)) {
                GlossaryTranslation::updateOrCreate(
                    [
                        'glossary_id' => $glossary->id,
                        'language_id' => $languagePair->source_language_id,
                    ],
                    [
                        'title' => $title1,
                        'description' => null,
                    ]
                );
            }

            if ($title2 = $this->nullIfEmpty($row->nazovJ2)) {
                GlossaryTranslation::updateOrCreate(
                    [
                        'glossary_id' => $glossary->id,
                        'language_id' => $languagePair->target_language_id,
                    ],
                    [
                        'title' => $title2,
                        'description' => null,
                    ]
                );
            }
        }

        $this->info('Legacy glossaries import finished.');
        $this->info('Glossaries in new DB: ' . Glossary::count());
        $this->info('Glossary translations in new DB: ' . GlossaryTranslation::count());

        return self::SUCCESS;
    }


    protected function normalizeUserId($userId): ?int
    {
        if (empty($userId)) {
            return null;
        }

        return DB::table('users')->where('id', $userId)->exists() ? (int) $userId : null;
    }

    protected function normalizeFieldId($fieldId): ?int
    {
        if (empty($fieldId)) {
            return null;
        }

        return DB::table('fields')->where('id', $fieldId)->exists() ? (int) $fieldId : null;
    }

    protected function normalizeLanguagePairId($legacyPairId): ?int
    {
        if (empty($legacyPairId)) {
            return null;
        }

        $legacyPair = DB::connection('legacy')
            ->table('jazykova_kombinacia')
            ->where('id', $legacyPairId)
            ->first();

        if (! $legacyPair) {
            return null;
        }

        $languageMap = [
            'slovenčina'   => 'sk',
            'angličtina'   => 'en',
            'nemčina'      => 'de',
            'francúzština' => 'fr',
            'ruština'      => 'ru',
        ];

        $sourceCode = $languageMap[$legacyPair->jazyk1] ?? null;
        $targetCode = $languageMap[$legacyPair->jazyk2] ?? null;

        if (! $sourceCode || ! $targetCode) {
            return null;
        }

        $sourceLanguageId = DB::table('languages')->where('code', $sourceCode)->value('id');
        $targetLanguageId = DB::table('languages')->where('code', $targetCode)->value('id');

        if (! $sourceLanguageId || ! $targetLanguageId) {
            return null;
        }

        return DB::table('language_pairs')
            ->where('source_language_id', $sourceLanguageId)
            ->where('target_language_id', $targetLanguageId)
            ->value('id');
    }

    protected function normalizeDate($value): ?string
    {
        if (! $value || $value === '0000-00-00') {
            return null;
        }

        return $value . ' 00:00:00';
    }

    protected function normalizeDateTime($value): ?string
    {
        if (! $value || $value === '0000-00-00 00:00:00') {
            return null;
        }

        return $value;
    }

    protected function nullIfEmpty($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
