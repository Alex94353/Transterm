<?php

namespace App\Console\Commands;

use App\Models\Reference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyReferences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-references {--limit=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import references from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy references import...');

        $query = DB::connection('legacy')
            ->table('zdroj_referencii')
            ->orderBy('id');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $rows = $query->get();

        foreach ($rows as $row) {
            Reference::updateOrCreate(
                ['id' => $row->id],
                [
                    'user_id' => $this->normalizeUserId($row->user_id),
                    'source'  => $this->normalizeText($row->zdroj),
                    'type'    => $this->nullIfEmpty($row->druh),
                    'language'=> $this->nullIfEmpty($row->jazyk),
                    'created_at' => $this->normalizeDateTime($row->date_created),
                    'updated_at' => $this->normalizeDateTime($row->date_modified),
                ]
            );
        }

        $this->info('Legacy references import finished.');
        $this->info('References in new DB: ' . Reference::count());

        return self::SUCCESS;
    }

    protected function normalizeUserId($userId): ?int
    {
        if (empty($userId)) {
            return null;
        }

        $exists = DB::table('users')->where('id', $userId)->exists();

        return $exists ? (int) $userId : null;
    }

    protected function normalizeDateTime($value): ?string
    {
        if (! $value || $value === '0000-00-00 00:00:00') {
            return null;
        }

        return $value;
    }

    protected function normalizeText($value): string
    {
        return trim((string) $value);
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
