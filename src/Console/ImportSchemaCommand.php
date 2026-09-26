<?php

namespace MustikaWijaya\PgsqlFdw\Console;

use Illuminate\Console\Command;
use MustikaWijaya\PgsqlFdw\Services\ConnectionManager;

class ImportSchemaCommand extends Command
{
    protected $signature = 'fdw:import-schema
                            {--source= : Source connection name}
                            {--dest= : Destination connection name}
                            {--remote-schema= : Remote schema to import (default: public)}
                            {--into-schema= : Local schema to import into}';

    protected $description = 'Import an entire foreign schema natively using IMPORT FOREIGN SCHEMA';

    public function handle(ConnectionManager $manager): int
    {
        $source = $this->option('source');
        $dest = $this->option('dest');

        if (!$source || !$dest) {
            $this->error('Both --source= and --dest= options are required.');
            return 1;
        }

        $sourceConfig = $manager->getConnectionConfig($source);
        $remoteSchema = $this->option('remote-schema') ?: ($sourceConfig['schema'] ?? 'public');
        $intoSchema = $this->option('into-schema') ?: $manager->getTargetSchema($source);
        $serverName = $manager->getServerName($source);

        $this->info("Importing foreign schema [{$remoteSchema}] into [{$intoSchema}] on [{$dest}]...");

        $manager->executeDdl($dest, "CREATE SCHEMA IF NOT EXISTS {$intoSchema};");
        $sql = "IMPORT FOREIGN SCHEMA {$remoteSchema} FROM SERVER {$serverName} INTO {$intoSchema};";
        $manager->executeDdl($dest, $sql);

        $this->info("✓ Foreign schema [{$remoteSchema}] successfully imported into [{$intoSchema}]!");
        return 0;
    }
}
