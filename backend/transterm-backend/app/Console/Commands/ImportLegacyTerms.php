<?php

namespace App\Console\Commands;

use App\Models\Glossary;
use App\Models\LanguagePair;
use App\Models\Term;
use App\Models\TermTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyTerms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-terms {--limit=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import terms and term translations from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy terms import...');

        $query = DB::connection('legacy')
            ->table('heslo')
            ->orderBy('id');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $rows = $query->get();

        foreach ($rows as $row) {
            $glossary = Glossary::find($row->glosar_id);

            if (! $glossary) {
                $this->warn("Skipping term {$row->id}: glossary {$row->glosar_id} not found.");
                continue;
            }

            $fieldId = $this->normalizeFieldId($row->odbor_id);
            $createdBy = $this->normalizeUserId($row->user_id);

            if (! $fieldId || ! $createdBy) {
                $this->warn("Skipping term {$row->id}: missing field or creator.");
                continue;
            }

            $term = Term::updateOrCreate(
                ['id' => $row->id],
                [
                    'glossary_id' => $glossary->id,
                    'field_id' => $fieldId,
                    'created_by' => $createdBy,
                    'created_at' => $this->normalizeDateTime($row->date_created),
                    'updated_at' => $this->normalizeDateTime($row->date_modified),
                ]
            );

            $languagePair = LanguagePair::find($glossary->language_pair_id);

            if (! $languagePair) {
                $this->warn("Skipping translations for term {$row->id}: language pair not found.");
                continue;
            }

            $this->storeTranslation(
                $term->id,
                $languagePair->source_language_id,
                $row->nazovJ1 ?? null,
                $row->mnozne_cisloJ1 ?? null,
                $row->definiciaJ1 ?? null,
                $row->kontextJ1 ?? null,
                $row->synonymumJ1 ?? null,
                $row->poznamkyJ1 ?? null
            );

            $this->storeTranslation(
                $term->id,
                $languagePair->target_language_id,
                $row->nazovJ2 ?? null,
                $row->mnozne_cisloJ2 ?? null,
                $row->definiciaJ2 ?? null,
                $row->kontextJ2 ?? null,
                $row->synonymumJ2 ?? null,
                $row->poznamkyJ2 ?? null
            );
        }

        $this->info('Legacy terms import finished.');
        $this->info('Terms in new DB: ' . Term::count());
        $this->info('Term translations in new DB: ' . TermTranslation::count());

        return self::SUCCESS;
    }

    protected function storeTranslation(
        int $termId,
        int $languageId,
        ?string $title,
        ?string $plural,
        ?string $definition,
        ?string $context,
        ?string $synonym,
        ?string $notes
    ): void {
        if (! $this->nullIfEmpty($title)) {
            return;
        }

        TermTranslation::updateOrCreate(
            [
                'term_id' => $termId,
                'language_id' => $languageId,
            ],
            [
                'title' => $this->nullIfEmpty($title),
                'plural' => $this->nullIfEmpty($plural),
                'definition' => $this->nullIfEmpty($definition),
                'context' => $this->nullIfEmpty($context),
                'synonym' => $this->nullIfEmpty($synonym),
                'notes' => $this->nullIfEmpty($notes),
            ]
        );
    }

    protected function normalizeFieldId($fieldId): ?int
    {
        if (empty($fieldId)) {
            return null;
        }

        return DB::table('fields')->where('id', $fieldId)->exists() ? (int) $fieldId : null;
    }

    protected function normalizeUserId($userId): ?int
    {
        if (empty($userId)) {
            return null;
        }

        return DB::table('users')->where('id', $userId)->exists() ? (int) $userId : null;
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
