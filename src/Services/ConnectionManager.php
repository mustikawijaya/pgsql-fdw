<?php

namespace MustikaWijaya\PgsqlFdw\Services;

use Illuminate\Support\Facades\DB;
use MustikaWijaya\PgsqlFdw\Exceptions\ConnectionException;
use MustikaWijaya\PgsqlFdw\Exceptions\FdwExecutionException;
use Throwable;

class ConnectionManager
{
    public function getConnectionConfig(string $name): array
    {
        $connections = config('database.connections', []);

        if (!isset($connections[$name])) {
            throw new ConnectionException("Connection [{$name}] is not defined in config/database.php.");
        }

        $config = $connections[$name];

        if (($config['driver'] ?? '') !== 'pgsql') {
            throw new ConnectionException("Driver for connection [{$name}] must be 'pgsql', '{$config['driver']}' given.");
        }

        return $config;
    }

    public function getServerName(string $source): string
    {
        return 'fdw_server_' . $source;
    }

    public function getTargetSchema(string $source): string
    {
        $useIsolation = config('pgsql-fdw.use_schema_isolation', true);

        if ($useIsolation) {
            $prefix = config('pgsql-fdw.schema_prefix', 'fdw_');
            return $prefix . $source;
        }

        return config('pgsql-fdw.default_schema', 'public');
    }

    public function getPreparationQueries(string $source): array
    {
        $sourceConfig = $this->getConnectionConfig($source);
        $serverName = $this->getServerName($source);
        $targetSchema = $this->getTargetSchema($source);

        $host = $sourceConfig['host'] ?? '127.0.0.1';
        $port = $sourceConfig['port'] ?? '5432';
        $database = $sourceConfig['database'] ?? '';
        $username = $sourceConfig['username'] ?? 'postgres';
        $password = $sourceConfig['password'] ?? '';

        $serverOptions = config('pgsql-fdw.options.server', [
            'use_remote_estimate' => 'true',
            'fetch_size' => '2000',
            'connect_timeout' => '10',
        ]);

        $optionsStrings = [
            "host '{$host}'",
            "dbname '{$database}'",
            "port '{$port}'",
        ];

        foreach ($serverOptions as $optKey => $optVal) {
            $optionsStrings[] = "{$optKey} '{$optVal}'";
        }
        $serverOptionsSql = implode(', ', $optionsStrings);

        return [
            "CREATE EXTENSION IF NOT EXISTS postgres_fdw;",
            "CREATE SERVER IF NOT EXISTS {$serverName} FOREIGN DATA WRAPPER postgres_fdw OPTIONS ({$serverOptionsSql});",
            "CREATE USER MAPPING IF NOT EXISTS FOR CURRENT_USER SERVER {$serverName} OPTIONS (user '{$username}', password '{$password}');",
            "GRANT USAGE ON FOREIGN SERVER {$serverName} TO CURRENT_USER;",
            "CREATE SCHEMA IF NOT EXISTS {$targetSchema};",
        ];
    }

    public function prepareDestination(string $source, string $dest, bool $force = false): void
    {
        $this->getConnectionConfig($source);
        $this->getConnectionConfig($dest);

        $queries = $this->getPreparationQueries($source);

        foreach ($queries as $query) {
            $this->executeDdl($dest, $query);
        }
    }

    public function executeDdl(string $connection, string $sql): void
    {
        try {
            DB::connection($connection)->unprepared($sql);
        } catch (Throwable $e) {
            throw new FdwExecutionException(
                "Failed executing DDL on [{$connection}]: " . $e->getMessage() . "\nSQL: {$sql}",
                (int)$e->getCode(),
                $e
            );
        }
    }
}
