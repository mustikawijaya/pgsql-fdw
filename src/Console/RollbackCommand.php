<?php

namespace MustikaWijaya\PgsqlFdw\Console;

use Illuminate\Console\Command;
use MustikaWijaya\PgsqlFdw\Services\FdwMonitor;
use MustikaWijaya\PgsqlFdw\Services\ConnectionManager;

class RollbackCommand extends Command
{
    protected $signature = 'fdw:rollback
                            {--dest= : Destination connection name}
                            {--table= : Foreign table to drop}
                            {--schema= : Schema of the table to drop}
                            {--server= : Foreign server to drop completely}
                            {--cascade : Drop with CASCADE}
                            {--force : Force drop without confirmation}';

    protected $description = 'Teardown foreign tables or servers on destination database';

    public function handle(FdwMonitor $monitor, ConnectionManager $manager): int
    {
        $dest = $this->option('dest');
        $table = $this->option('table');
        $schema = $this->option('schema');
        $server = $this->option('server');
        $cascade = (bool) $this->option('cascade');
        $force = (bool) $this->option('force');

        if (!$dest) {
            $this->error('The --dest= option is required.');
            return 1;
        }

        if (!$table && !$server) {
            $this->error('Either --table= or --server= must be specified.');
            return 1;
        }

        $identifierRegex = '/^[a-zA-Z0-9_\-]+$/';

        if (!preg_match($identifierRegex, $dest)) {
            $this->error("Invalid identifier provided for --dest: [{$dest}]. Only alphanumeric characters, dashes, and underscores are allowed.");
            return 1;
        }

        if ($table !== null && !preg_match($identifierRegex, $table)) {
            $this->error("Invalid identifier provided for --table: [{$table}]. Only alphanumeric characters, dashes, and underscores are allowed.");
            return 1;
        }

        if ($schema !== null && !preg_match($identifierRegex, $schema)) {
            $this->error("Invalid identifier provided for --schema: [{$schema}]. Only alphanumeric characters, dashes, and underscores are allowed.");
            return 1;
        }

        if ($server !== null && !preg_match($identifierRegex, $server)) {
            $this->error("Invalid identifier provided for --server: [{$server}]. Only alphanumeric characters, dashes, and underscores are allowed.");
            return 1;
        }

        if (!$force && !$this->confirm('Are you sure you want to proceed with teardown?')) {
            $this->info('Rollback aborted.');
            return 0;
        }

        if ($table) {
            $targetSchema = $schema ?: config('pgsql-fdw.default_schema', 'public');
            $monitor->dropForeignTable($dest, $targetSchema, $table, $cascade);
            $this->info("✓ Foreign table [{$targetSchema}.{$table}] dropped.");
        }

        if ($server) {
            $monitor->dropForeignServer($dest, $server, $cascade);
            $this->info("✓ Foreign server [{$server}] dropped.");
        }

        return 0;
    }
}
