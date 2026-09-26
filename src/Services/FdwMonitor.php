<?php

namespace MustikaWijaya\PgsqlFdw\Services;

use Illuminate\Support\Facades\DB;
use MustikaWijaya\PgsqlFdw\Exceptions\FdwExecutionException;
use Throwable;

class FdwMonitor
{
    public function getForeignServers(string $connection): array
    {
        return DB::connection($connection)->select("
            SELECT
                s.srvname AS server_name,
                w.fdwname AS wrapper_name,
                ARRAY_TO_STRING(s.srvoptions, ', ') AS options
            FROM pg_foreign_server s
            JOIN pg_foreign_data_wrapper w ON w.oid = s.srvfdw
            ORDER BY s.srvname ASC
        ");
    }

    public function getUserMappings(string $connection): array
    {
        return DB::connection($connection)->select("
            SELECT
                u.usename AS local_user,
                s.srvname AS server_name,
                ARRAY_TO_STRING(m.umoptions, ', ') AS options
            FROM pg_user_mapping m
            JOIN pg_foreign_server s ON s.oid = m.umserver
            JOIN pg_user u ON u.usesysid = m.umuser
            ORDER BY s.srvname ASC
        ");
    }

    public function getForeignTables(string $connection): array
    {
        return DB::connection($connection)->select("
            SELECT
                n.nspname AS foreign_schema,
                c.relname AS foreign_table,
                s.srvname AS server_name,
                ARRAY_TO_STRING(ft.ftoptions, ', ') AS options
            FROM pg_foreign_table ft
            JOIN pg_class c ON c.oid = ft.ftrelid
            JOIN pg_namespace n ON n.oid = c.relnamespace
            JOIN pg_foreign_server s ON s.oid = ft.ftserver
            ORDER BY n.nspname ASC, c.relname ASC
        ");
    }

    public function dropForeignTable(string $connection, string $schema, string $table, bool $cascade = false): void
    {
        $cascadeSql = $cascade ? ' CASCADE' : '';
        $sql = "DROP FOREIGN TABLE IF EXISTS {$schema}.{$table}{$cascadeSql};";

        try {
            DB::connection($connection)->unprepared($sql);
        } catch (Throwable $e) {
            throw new FdwExecutionException(
                "Failed dropping foreign table [{$schema}.{$table}]: " . $e->getMessage(),
                (int)$e->getCode(),
                $e
            );
        }
    }

    public function dropForeignServer(string $connection, string $server, bool $cascade = false): void
    {
        $cascadeSql = $cascade ? ' CASCADE' : '';
        $sql = "DROP SERVER IF EXISTS {$server}{$cascadeSql};";

        try {
            DB::connection($connection)->unprepared($sql);
        } catch (Throwable $e) {
            throw new FdwExecutionException(
                "Failed dropping foreign server [{$server}]: " . $e->getMessage(),
                (int)$e->getCode(),
                $e
            );
        }
    }
}
