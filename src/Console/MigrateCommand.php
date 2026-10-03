<?php

namespace MustikaWijaya\PgsqlFdw\Console;

use Illuminate\Console\Command;
use MustikaWijaya\PgsqlFdw\Services\ConnectionManager;
use MustikaWijaya\PgsqlFdw\Services\SqlFileHandler;

class MigrateCommand extends Command
{
    protected $signature = 'fdw:migrate
                            {--dest= : Destination connection name}
                            {--dry-run : Preview SQL files without executing}';

    protected $description = 'Execute generated FDW SQL files on destination database';

    public function handle(ConnectionManager $manager, SqlFileHandler $fileHandler): int
    {
        $dest = $this->option('dest');
        $dryRun = (bool) $this->option('dry-run');

        if (!$dest) {
            $this->error('The --dest= option is required.');
            return 1;
        }

        $files = $fileHandler->getFiles($dest);

        if (empty($files)) {
            $this->warn("No SQL files found under: " . $fileHandler->getDirectoryPath($dest));
            return 0;
        }

        if ($dryRun) {
            $this->info("Dry-run preview: The following " . count($files) . " file(s) would be executed on [{$dest}]:");
            foreach ($files as $file) {
                $this->line(" - {$file}");
            }
            return 0;
        }

        $this->info("Executing " . count($files) . " SQL file(s) on [{$dest}]...");

        foreach ($files as $file) {
            $sql = file_get_contents($file);
            $manager->executeDdl($dest, $sql);
            $this->line("<info>Executed:</info> " . basename($file));
        }

        $this->info("✓ All FDW migrations executed successfully on [{$dest}]!");
        return 0;
    }
}
