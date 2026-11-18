<?php

namespace MustikaWijaya\PgsqlFdw\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use MustikaWijaya\PgsqlFdw\Services\FdwMonitor;
use MustikaWijaya\PgsqlFdw\Support\IdentifierValidator;

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

    public function handle(FdwMonitor $monitor): int
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

        $targetSchema = $table ? ($schema ?: config('pgsql-fdw.default_schema', 'public')) : $schema;

        try {
            IdentifierValidator::validate($dest, '--dest');

            if ($table !== null) {
                IdentifierValidator::validate($table, '--table');
            }

            if ($targetSchema !== null) {
                IdentifierValidator::validate((string) $targetSchema, '--schema');
            }

            if ($server !== null) {
                IdentifierValidator::validate($server, '--server');
            }

            if (!$force && !$this->confirm('Are you sure you want to proceed with teardown?')) {
                $this->info('Rollback aborted.');
                return 0;
            }

            if ($table) {
                $monitor->dropForeignTable($dest, (string) $targetSchema, $table, $cascade);
                $this->info("✓ Foreign table [{$targetSchema}.{$table}] dropped.");
            }

            if ($server) {
                $monitor->dropForeignServer($dest, $server, $cascade);
                $this->info("✓ Foreign server [{$server}] dropped.");
            }

            return 0;
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
