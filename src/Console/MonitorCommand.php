<?php

namespace MustikaWijaya\PgsqlFdw\Console;

use Illuminate\Console\Command;
use MustikaWijaya\PgsqlFdw\Services\FdwMonitor;

class MonitorCommand extends Command
{
    protected $signature = 'fdw:monitor
                            {--dest= : Destination connection name}';

    protected $description = 'Display status dashboard of Foreign Servers, User Mappings, and Foreign Tables';

    public function handle(FdwMonitor $monitor): int
    {
        $dest = $this->option('dest');

        if (!$dest) {
            $this->error('The --dest= option is required.');
            return 1;
        }

        $this->info("=== PostgreSQL FDW Status Dashboard [{$dest}] ===");

        $servers = $monitor->getForeignServers($dest);
        $this->line("\n<comment>Foreign Servers (" . count($servers) . ")</comment>");
        $serverRows = array_map(fn($s) => [
            $s->server_name ?? '',
            $s->wrapper_name ?? '',
            $s->options ?? '',
        ], $servers);
        $this->table(['Server Name', 'Wrapper', 'Options'], $serverRows);

        $mappings = $monitor->getUserMappings($dest);
        $this->line("\n<comment>User Mappings (" . count($mappings) . ")</comment>");
        $mappingRows = array_map(fn($m) => [
            $m->local_user ?? '',
            $m->server_name ?? '',
            $m->options ?? '',
        ], $mappings);
        $this->table(['Local User', 'Foreign Server', 'Options'], $mappingRows);

        $tables = $monitor->getForeignTables($dest);
        $this->line("\n<comment>Foreign Tables (" . count($tables) . ")</comment>");
        $tableRows = array_map(fn($t) => [
            $t->foreign_schema ?? '',
            $t->foreign_table ?? '',
            $t->server_name ?? '',
            $t->options ?? '',
        ], $tables);
        $this->table(['Schema', 'Table Name', 'Foreign Server', 'Options'], $tableRows);

        return 0;
    }
}
