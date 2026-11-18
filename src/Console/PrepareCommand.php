<?php

namespace MustikaWijaya\PgsqlFdw\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use MustikaWijaya\PgsqlFdw\Services\ConnectionManager;
use MustikaWijaya\PgsqlFdw\Support\IdentifierValidator;

class PrepareCommand extends Command
{
    protected $signature = 'fdw:prepare
                            {--source= : Source connection name}
                            {--dest= : Destination connection name}
                            {--force : Drop and recreate foreign server (CASCADE) before preparing}';

    protected $description = 'Setup PostgreSQL FDW extension, server, user mapping, and target schema';

    public function handle(ConnectionManager $manager): int
    {
        $source = $this->option('source');
        $dest = $this->option('dest');
        $force = (bool) $this->option('force');

        if (!$source || !$dest) {
            $this->error('Both --source= and --dest= options are required.');
            return 1;
        }

        try {
            IdentifierValidator::validate($source, '--source');
            IdentifierValidator::validate($dest, '--dest');

            $this->info("Preparing FDW on [{$dest}] for source [{$source}]...");

            if ($force) {
                $this->warn("--force enabled: dropping and recreating foreign server [{$manager->getServerName($source)}] with CASCADE.");
            }

            $manager->prepareDestination($source, $dest, $force);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 1;
        }

        $this->info("✓ FDW environment successfully configured on [{$dest}]!");
        return 0;
    }
}
