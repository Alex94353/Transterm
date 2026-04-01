<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportLegacyAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-all
                            {--limit= : Limit rows for commands that support --limit}
                            {--without-comments : Skip legacy comments import step}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all legacy import commands in the correct order';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting full legacy import pipeline...');

        $limit = $this->option('limit');

        $steps = [
            ['command' => 'legacy:import-reference-data', 'supports_limit' => false],
            ['command' => 'legacy:import-users', 'supports_limit' => true],
            ['command' => 'legacy:import-glossaries', 'supports_limit' => true],
            ['command' => 'legacy:import-terms', 'supports_limit' => true],
            ['command' => 'legacy:import-references', 'supports_limit' => true],
            ['command' => 'legacy:import-term-references', 'supports_limit' => true],
            ['command' => 'legacy:import-comments', 'supports_limit' => true],
        ];

        if ($this->option('without-comments')) {
            $steps = array_values(array_filter(
                $steps,
                fn (array $step): bool => $step['command'] !== 'legacy:import-comments'
            ));
        }

        $total = count($steps);

        foreach ($steps as $index => $step) {
            $current = $index + 1;
            $command = $step['command'];

            $this->newLine();
            $this->info("[{$current}/{$total}] Running {$command}...");

            $arguments = [];
            if ($step['supports_limit'] && $limit !== null) {
                $arguments['--limit'] = $limit;
            }

            $exitCode = $this->call($command, $arguments);

            if ($exitCode !== self::SUCCESS) {
                $this->error("Pipeline stopped: {$command} failed with exit code {$exitCode}.");

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('Legacy import pipeline finished successfully.');

        return self::SUCCESS;
    }
}

