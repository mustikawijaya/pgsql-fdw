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
        $serverRows = array_map(fn($s) => [(array)$s->server_name, (array)$s->wrapper_name, (array)$s->options], $servers);
        $this->table(['Server Name', 'Wrapper', 'Options'], $servers);

        $mappings = $monitor->getUserMappings($dest);
        $this->line("\n<comment>User Mappings (" . count($mappings) . ")</comment>");
        $this->table(['Local User', 'Foreign Server', 'Options'], $mappings);

        $tables = $monitor->getForeignTables($dest);
        $this->line("\n<comment>Foreign Tables (" . count($tables) . ")</comment>");
        $this->table(['Schema', 'Table Name', 'Foreign Server', 'Options'], $tables);

        return 0;
    }
}
