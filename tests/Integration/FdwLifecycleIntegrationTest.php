<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Integration;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use MustikaWijaya\PgsqlFdw\Services\FdwMonitor;
use MustikaWijaya\PgsqlFdw\Tests\TestCase;

class FdwLifecycleIntegrationTest extends TestCase
{
    private string $sourceConn = 'test_source';
    private string $destConn = 'test_dest';

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.connections.test_source', [
            'driver' => 'pgsql',
            'host' => '/var/run/postgresql',
            'port' => '5432',
            'database' => 'postgres',
            'username' => 'postgres',
            'password' => '',
            'schema' => 'test_src_schema',
        ]);

        $app['config']->set('database.connections.test_dest', [
            'driver' => 'pgsql',
            'host' => '/var/run/postgresql',
            'port' => '5432',
            'database' => 'postgres',
            'username' => 'postgres',
            'password' => '',
            'schema' => 'public',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Cleanup any leftover objects from previous runs
        DB::connection($this->destConn)->unprepared("
            DROP FOREIGN TABLE IF EXISTS fdw_test_source.e2e_products;
            DROP SCHEMA IF EXISTS fdw_test_source CASCADE;
            DROP SERVER IF EXISTS fdw_server_test_source CASCADE;
            DROP SCHEMA IF EXISTS test_src_schema CASCADE;
        ");

        // 2. Create source schema & physical table with data
        DB::connection($this->sourceConn)->unprepared("
            CREATE SCHEMA test_src_schema;
            CREATE TABLE test_src_schema.e2e_products (
                id serial PRIMARY KEY,
                name varchar(100) NOT NULL,
                price numeric(10,2) NOT NULL
            );
            INSERT INTO test_src_schema.e2e_products (name, price) VALUES ('Test Item', 99.95);
        ");
    }

    protected function tearDown(): void
    {
        DB::connection($this->destConn)->unprepared("
            DROP FOREIGN TABLE IF EXISTS fdw_test_source.e2e_products;
            DROP SCHEMA IF EXISTS fdw_test_source CASCADE;
            DROP SERVER IF EXISTS fdw_server_test_source CASCADE;
            DROP SCHEMA IF EXISTS test_src_schema CASCADE;
        ");

        parent::tearDown();
    }

    public function test_full_fdw_lifecycle(): void
    {
        // 1. Prepare
        $exitCode = Artisan::call('fdw:prepare', [
            '--source' => $this->sourceConn,
            '--dest' => $this->destConn,
        ]);
        $this->assertEquals(0, $exitCode);

        // 2. Make SQL
        $exitCode = Artisan::call('fdw:make-sql', [
            '--source' => $this->sourceConn,
            '--dest' => $this->destConn,
            '--table' => 'e2e_products',
        ]);
        $this->assertEquals(0, $exitCode);

        // 3. Migrate
        $exitCode = Artisan::call('fdw:migrate', [
            '--dest' => $this->destConn,
        ]);
        $this->assertEquals(0, $exitCode);

        // 4. Query remote data through foreign table
        $data = DB::connection($this->destConn)->table('fdw_test_source.e2e_products')->get();
        $this->assertNotEmpty($data);
        $this->assertEquals('Test Item', $data[0]->name);
        $this->assertEquals('99.95', $data[0]->price);

        // 5. Monitor
        $exitCode = Artisan::call('fdw:monitor', [
            '--dest' => $this->destConn,
        ]);
        $this->assertEquals(0, $exitCode);

        $monitor = app(FdwMonitor::class);
        $tables = $monitor->getForeignTables($this->destConn);
        $tableNames = array_map(fn($t) => $t->foreign_table, $tables);
        $this->assertContains('e2e_products', $tableNames);

        // 6. Rollback
        $exitCode = Artisan::call('fdw:rollback', [
            '--dest' => $this->destConn,
            '--table' => 'e2e_products',
            '--schema' => 'fdw_test_source',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode);
    }
}
