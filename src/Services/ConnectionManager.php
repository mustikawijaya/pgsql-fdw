<?php

namespace MustikaWijaya\PgsqlFdw\Services;

use Illuminate\Support\Facades\DB;
use MustikaWijaya\PgsqlFdw\Exceptions\ConnectionException;
use MustikaWijaya\PgsqlFdw\Exceptions\FdwExecutionException;
use MustikaWijaya\PgsqlFdw\Support\CredentialMasker;
use MustikaWijaya\PgsqlFdw\Support\FdwLogger;
use MustikaWijaya\PgsqlFdw\Support\IdentifierValidator;
use Throwable;

class ConnectionManager
{
    public function getConnectionConfig(string $name): array
    {
        IdentifierValidator::validate($name, 'connection name');

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
        IdentifierValidator::validate($source, 'source connection');
        return 'fdw_server_' . $source;
    }

    public function getTargetSchema(string $source): string
    {
        IdentifierValidator::validate($source, 'source connection');
        $useIsolation = config('pgsql-fdw.use_schema_isolation', true);

        if ($useIsolation) {
            $prefix = config('pgsql-fdw.schema_prefix', 'fdw_');
            $schema = $prefix . $source;
        } else {
            $schema = config('pgsql-fdw.default_schema', 'public');
        }

        IdentifierValidator::validate($schema, 'target schema');
        return $schema;
    }

    public function getPreparationQueries(string $source, bool $force = false): array
    {
        $sourceConfig = $this->getConnectionConfig($source);
        $serverName = $this->getServerName($source);
        $targetSchema = $this->getTargetSchema($source);

        $host = str_replace("'", "''", (string) ($sourceConfig['host'] ?? '127.0.0.1'));
        $port = str_replace("'", "''", (string) ($sourceConfig['port'] ?? '5432'));
        $database = str_replace("'", "''", (string) ($sourceConfig['database'] ?? ''));
        $username = str_replace("'", "''", (string) ($sourceConfig['username'] ?? 'postgres'));
        $password = str_replace("'", "''", (string) ($sourceConfig['password'] ?? ''));

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
            $key = (string) $optKey;
            IdentifierValidator::validateOptionKey($key);
            $escapedOptVal = str_replace("'", "''", (string) $optVal);
            $optionsStrings[] = "{$key} '{$escapedOptVal}'";
        }
        $serverOptionsSql = implode(', ', $optionsStrings);
        $ifNotExists = $force ? '' : 'IF NOT EXISTS ';

        $queries = ['CREATE EXTENSION IF NOT EXISTS postgres_fdw;'];

        if ($force) {
            $queries[] = "DROP SERVER IF EXISTS \"{$serverName}\" CASCADE;";
        }

        $queries[] = "CREATE SERVER {$ifNotExists}\"{$serverName}\" FOREIGN DATA WRAPPER postgres_fdw OPTIONS ({$serverOptionsSql});";
        $queries[] = "CREATE USER MAPPING {$ifNotExists}FOR CURRENT_USER SERVER \"{$serverName}\" OPTIONS (user '{$username}', password '{$password}');";
        $queries[] = "GRANT USAGE ON FOREIGN SERVER \"{$serverName}\" TO CURRENT_USER;";
        $queries[] = "CREATE SCHEMA IF NOT EXISTS \"{$targetSchema}\";";

        return $queries;
    }

    public function prepareDestination(string $source, string $dest, bool $force = false): void
    {
        $this->getConnectionConfig($source);
        $this->getConnectionConfig($dest);

        $queries = $this->getPreparationQueries($source, $force);

        foreach ($queries as $query) {
            $this->executeDdl($dest, $query);
        }
    }

    public function executeDdl(string $connection, string $sql): void
    {
        IdentifierValidator::validate($connection, 'connection name');

        try {
            DB::connection($connection)->unprepared($sql);
        } catch (Throwable $e) {
            FdwLogger::error($connection, $e->getMessage(), $sql);

            $maskedSql = CredentialMasker::mask($sql);
            $maskedError = CredentialMasker::mask($e->getMessage());

            throw new FdwExecutionException(
                "Failed executing DDL on [{$connection}]: {$maskedError}\nSQL: {$maskedSql}",
                (int) $e->getCode(),
                $e
            );
        }

        FdwLogger::info($connection, $sql);
    }
}
