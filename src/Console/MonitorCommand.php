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

        $this->renderSection(
            'Foreign Servers',
            ['Server Name', 'Wrapper', 'Options'],
            $monitor->getForeignServers($dest),
            ['server_name', 'wrapper_name', 'options']
        );

        $this->renderSection(
            'User Mappings',
            ['Local User', 'Foreign Server', 'Options'],
            $monitor->getUserMappings($dest),
            ['local_user', 'server_name', 'options']
        );

        $this->renderSection(
            'Foreign Tables',
            ['Schema', 'Table Name', 'Foreign Server', 'Options'],
            $monitor->getForeignTables($dest),
            ['foreign_schema', 'foreign_table', 'server_name', 'options']
        );

        return 0;
    }

    /**
     * Render a section header and formatted table.
     *
     * @param string $title
     * @param array<int, string> $headers
     * @param array<int, object|array> $items
     * @param array<int, string> $keys
     */
    private function renderSection(string $title, array $headers, array $items, array $keys): void
    {
        $this->line("\n<comment>{$title} (" . count($items) . ")</comment>");
        $this->table($headers, $this->formatRows($items, $keys));
    }

    /**
     * Normalize items (objects or arrays) into rows suitable for console table rendering.
     *
     * @param array<int, object|array> $items
     * @param array<int, string> $keys
     * @return array<int, array<int, string>>
     */
    private function formatRows(array $items, array $keys): array
    {
        return array_map(function ($item) use ($keys): array {
            $data = (array) $item;

            return array_map(fn(string $key): string => (string) ($data[$key] ?? ''), $keys);
        }, $items);
    }
}
