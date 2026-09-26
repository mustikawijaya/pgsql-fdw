<?php

namespace MustikaWijaya\PgsqlFdw;

use Illuminate\Support\ServiceProvider;
use MustikaWijaya\PgsqlFdw\Console\ImportSchemaCommand;
use MustikaWijaya\PgsqlFdw\Console\MakeSqlCommand;
use MustikaWijaya\PgsqlFdw\Console\MigrateCommand;
use MustikaWijaya\PgsqlFdw\Console\MonitorCommand;
use MustikaWijaya\PgsqlFdw\Console\PrepareCommand;
use MustikaWijaya\PgsqlFdw\Console\RollbackCommand;

class PgsqlFdwServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/pgsql-fdw.php', 'pgsql-fdw');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/pgsql-fdw.php' => config_path('pgsql-fdw.php'),
            ], 'pgsql-fdw-config');

            $this->commands([
                PrepareCommand::class,
                MakeSqlCommand::class,
                ImportSchemaCommand::class,
                MigrateCommand::class,
                MonitorCommand::class,
                RollbackCommand::class,
            ]);
        }
    }
}
