<?php

namespace App\Console\Commands;

use App\Models\Term;
use App\Models\TermReference;
use App\Models\TermTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class ImportLegacyTermReferences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-term-references {--limit=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import term references from legacy heslo source fields';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy term references import...');

        $query = DB::connection('legacy')
            ->table('heslo')
            ->orderBy('id');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $rows = $query->get();

        foreach ($rows as $row) {
            $term = Term::find($row->id);

            if (! $term) {
                $this->warn("Skipping references for term {$row->id}: term not found.");
                continue;
            }

            $translations = TermTranslation::where('term_id', $term->id)->orderBy('language_id')->get();

            if ($translations->count() < 2) {
                $this->warn("Skipping references for term {$row->id}: translations missing.");
                continue;
            }

            $translation1 = $translations[0];
            $translation2 = $translations[1];

            $this->storeReference($translation1->id, $row->zdroj_defJ1 ?? null, 'definition');
            $this->storeReference($translation1->id, $row->zdroj_konJ1 ?? null, 'context');

            $this->storeReference($translation2->id, $row->zdroj_defJ2 ?? null, 'definition');
            $this->storeReference($translation2->id, $row->zdroj_konJ2 ?? null, 'context');
        }

        $this->info('Legacy term references import finished.');
        $this->info('Term references in new DB: ' . TermReference::count());

        return self::SUCCESS;
    }

    protected function storeReference(int $termTranslationId, $referenceId, string $type): void
    {
        if (empty($referenceId)) {
            return;
        }

        $referenceExists = DB::table('references')->where('id', $referenceId)->exists();

        if (! $referenceExists) {
            return;
        }

        TermReference::updateOrCreate(
            [
                'term_translation_id' => $termTranslationId,
                'reference_id' => $referenceId,
                'type' => $type,
            ],
            [
                'term_translation_id' => $termTranslationId,
                'reference_id' => $referenceId,
                'type' => $type,
            ]
        );
    }
}
