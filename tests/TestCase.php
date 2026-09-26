<?php

namespace MustikaWijaya\PgsqlFdw\Tests;

use MustikaWijaya\PgsqlFdw\PgsqlFdwServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PgsqlFdwServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('pgsql-fdw', require __DIR__ . '/../config/pgsql-fdw.php');
    }
}
