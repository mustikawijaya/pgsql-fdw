<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SQL Output Directory
    |--------------------------------------------------------------------------
    | The base directory where generated DDL SQL files will be stored.
    | Files are organized hierarchically: {path}/{dest}/{source}/{table}.sql
    */
    'sql_output_path' => database_path('fdw'),

    /*
    |--------------------------------------------------------------------------
    | Schema Isolation
    |--------------------------------------------------------------------------
    | If TRUE, foreign tables are created inside a dedicated PostgreSQL schema:
    | {schema_prefix}{source_connection_name} (e.g., fdw_pgsql_central.users).
    | If FALSE, foreign tables are placed into 'default_schema'.
    */
    'use_schema_isolation' => true,
    'schema_prefix' => 'fdw_',
    'default_schema' => env('DB_FDW_SCHEMA', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Default FDW Connection & Server Options
    |--------------------------------------------------------------------------
    | Tuning parameters for PostgreSQL foreign servers and tables.
    */
    'options' => [
        'server' => [
            'use_remote_estimate' => 'true',
            'fetch_size' => '2000',
            'connect_timeout' => '10',
        ],
        'table' => [
            'updatable' => 'false',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Channel
    |--------------------------------------------------------------------------
    | Destination channel for DDL execution logging.
    */
    'log_channel' => env('FDW_LOG_CHANNEL', 'single'),
];
