<?php

namespace MustikaWijaya\PgsqlFdw\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use MustikaWijaya\PgsqlFdw\Services\ConnectionManager;
use MustikaWijaya\PgsqlFdw\Services\SchemaInspector;
use MustikaWijaya\PgsqlFdw\Services\SqlFileHandler;
use MustikaWijaya\PgsqlFdw\Services\SqlGenerator;
use MustikaWijaya\PgsqlFdw\Support\IdentifierValidator;

class MakeSqlCommand extends Command
{
    protected $signature = 'fdw:make-sql
                            {--source= : Source connection name}
                            {--dest= : Destination connection name}
                            {--table= : Specific table name}
                            {--all : Scaffolds all tables in source schema}';

    protected $description = 'Generate raw DDL SQL files for foreign tables';

    public function handle(
        ConnectionManager $manager,
        SchemaInspector $inspector,
        SqlGenerator $generator,
        SqlFileHandler $fileHandler
    ): int {
        $source = $this->option('source');
        $dest = $this->option('dest');
        $table = $this->option('table');
        $all = (bool) $this->option('all');

        if (!$source || !$dest) {
            $this->error('Both --source= and --dest= options are required.');
            return 1;
        }

        if (!$table && !$all) {
            $this->error('Either --table= or --all must be specified.');
            return 1;
        }

        try {
            IdentifierValidator::validate($source, '--source');
            IdentifierValidator::validate($dest, '--dest');

            if ($table !== null) {
                IdentifierValidator::validate($table, '--table');
            }

            $sourceConfig = $manager->getConnectionConfig($source);
            $remoteSchema = $sourceConfig['schema'] ?? 'public';
            $targetSchema = $manager->getTargetSchema($source);
            $serverName = $manager->getServerName($source);
            $tableOptions = config('pgsql-fdw.options.table', []);

            $tables = $all ? $inspector->getTableNames($source, $remoteSchema) : [$table];

            if (empty($tables)) {
                $this->warn('No tables found to generate.');
                return 0;
            }

            $generatedCount = 0;

            foreach ($tables as $t) {
                $columns = $inspector->getTableColumns($source, $t, $remoteSchema);

                if (empty($columns)) {
                    if (!$all) {
                        $this->error("Table [{$remoteSchema}.{$t}] does not exist or has no columns on [{$source}].");
                        return 1;
                    }

                    $this->warn("Skipping table [{$remoteSchema}.{$t}]: no columns found.");
                    continue;
                }

                $sql = $generator->generate($source, $dest, $t, $columns, $targetSchema, $serverName, $remoteSchema, $tableOptions);
                $filePath = $fileHandler->save($dest, $source, $t, $sql);
                $this->line("<info>Generated:</info> {$filePath}");
                $generatedCount++;
            }

            $this->info("✓ Scaffolding complete for {$generatedCount} table(s).");
            return 0;
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
