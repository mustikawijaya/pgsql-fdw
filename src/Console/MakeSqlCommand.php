<?php

namespace MustikaWijaya\PgsqlFdw\Console;

use Illuminate\Console\Command;
use MustikaWijaya\PgsqlFdw\Services\ConnectionManager;
use MustikaWijaya\PgsqlFdw\Services\SchemaInspector;
use MustikaWijaya\PgsqlFdw\Services\SqlFileHandler;
use MustikaWijaya\PgsqlFdw\Services\SqlGenerator;

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

        foreach ($tables as $t) {
            $columns = $inspector->getTableColumns($source, $t, $remoteSchema);
            $sql = $generator->generate($source, $dest, $t, $columns, $targetSchema, $serverName, $remoteSchema, $tableOptions);
            $filePath = $fileHandler->save($dest, $source, $t, $sql);
            $this->line("<info>Generated:</info> {$filePath}");
        }

        $this->info("✓ Scaffolding complete for " . count($tables) . " table(s).");
        return 0;
    }
}
