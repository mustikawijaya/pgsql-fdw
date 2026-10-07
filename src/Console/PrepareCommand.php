<?php

namespace MustikaWijaya\PgsqlFdw\Console;

use Illuminate\Console\Command;
use MustikaWijaya\PgsqlFdw\Services\ConnectionManager;

class PrepareCommand extends Command
{
    protected $signature = 'fdw:prepare
                            {--source= : Source connection name}
                            {--dest= : Destination connection name}
                            {--force : Force recreation or ignore existing objects}';

    protected $description = 'Setup PostgreSQL FDW extension, server, user mapping, and target schema';

    public function handle(ConnectionManager $manager): int
    {
        $source = $this->option('source');
        $dest = $this->option('dest');

        if (!$source || !$dest) {
            $this->error('Both --source= and --dest= options are required.');
            return 1;
        }

        $identifierRegex = '/^[a-zA-Z0-9_\-]+$/';

        if (!preg_match($identifierRegex, $source)) {
            $this->error("Invalid identifier provided for --source: [{$source}]. Only alphanumeric characters, dashes, and underscores are allowed.");
            return 1;
        }

        if (!preg_match($identifierRegex, $dest)) {
            $this->error("Invalid identifier provided for --dest: [{$dest}]. Only alphanumeric characters, dashes, and underscores are allowed.");
            return 1;
        }

        $this->info("Preparing FDW on [{$dest}] for source [{$source}]...");

        $manager->prepareDestination($source, $dest, (bool) $this->option('force'));

        $this->info("✓ FDW environment successfully configured on [{$dest}]!");
        return 0;
    }
}
