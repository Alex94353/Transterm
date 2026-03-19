<?php

namespace App\Console\Commands;

use App\Models\Comment;
use App\Models\Term;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyComments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-comments {--limit=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import comments from legacy database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy comments import...');

        $rows = DB::connection('legacy')
            ->table('komentare')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $term = Term::find($row->heslo_id);

            if (! $term) {
                $this->warn("Skipping comment {$row->id}: term not found.");
                continue;
            }

            Comment::updateOrCreate(
                ['id' => $row->id],
                [
                    'term_id' => $term->id,
                    'user_id' => $this->normalizeUserId($row->user_id),
                    'body' => $this->nullIfEmpty($row->telo),
                    'is_spam' => (bool) ($row->is_spam ?? 0),
                    'created_at' => $this->normalizeDateTime($row->datum),
                    'updated_at' => $this->normalizeDateTime($row->datum),
                ]
            );
        }

        $this->info('Legacy comments import finished.');
        $this->info('Comments in new DB: ' . Comment::count());

        return self::SUCCESS;
    }

    protected function normalizeUserId($userId): ?int
    {
        if (empty($userId)) {
            return null;
        }

        return DB::table('users')->where('id', $userId)->exists()
            ? (int) $userId
            : null;
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
