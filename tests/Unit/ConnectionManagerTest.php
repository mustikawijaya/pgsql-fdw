<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Unit;

use MustikaWijaya\PgsqlFdw\Exceptions\ConnectionException;
use MustikaWijaya\PgsqlFdw\Services\ConnectionManager;
use MustikaWijaya\PgsqlFdw\Tests\TestCase;

class ConnectionManagerTest extends TestCase
{
    private ConnectionManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.connections.pgsql_source' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => '5432',
                'database' => 'source_db',
                'username' => 'postgres',
                'password' => 'secret',
                'schema' => 'public',
            ],
            'database.connections.mysql_conn' => [
                'driver' => 'mysql',
                'database' => 'mysql_db',
            ],
            'pgsql-fdw.use_schema_isolation' => true,
            'pgsql-fdw.schema_prefix' => 'fdw_',
        ]);

        $this->manager = new ConnectionManager();
    }

    public function test_it_validates_and_returns_pgsql_connection_config(): void
    {
        $config = $this->manager->getConnectionConfig('pgsql_source');
        $this->assertEquals('pgsql', $config['driver']);
        $this->assertEquals('source_db', $config['database']);
    }

    public function test_it_throws_exception_if_connection_does_not_exist(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage("Connection [non_existent] is not defined");
        $this->manager->getConnectionConfig('non_existent');
    }

    public function test_it_throws_exception_if_driver_is_not_pgsql(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage("Driver for connection [mysql_conn] must be 'pgsql'");
        $this->manager->getConnectionConfig('mysql_conn');
    }

    public function test_it_resolves_server_name_and_target_schema(): void
    {
        $this->assertEquals('fdw_server_pgsql_source', $this->manager->getServerName('pgsql_source'));
        $this->assertEquals('fdw_pgsql_source', $this->manager->getTargetSchema('pgsql_source'));

        config(['pgsql-fdw.use_schema_isolation' => false, 'pgsql-fdw.default_schema' => 'public']);
        $this->assertEquals('public', $this->manager->getTargetSchema('pgsql_source'));
    }

    public function test_it_builds_preparation_ddl_statements(): void
    {
        $queries = $this->manager->getPreparationQueries('pgsql_source');

        $this->assertCount(5, $queries);
        $this->assertStringContainsString('CREATE EXTENSION IF NOT EXISTS postgres_fdw;', $queries[0]);
        $this->assertStringContainsString('CREATE SERVER IF NOT EXISTS fdw_server_pgsql_source', $queries[1]);
        $this->assertStringContainsString("host '127.0.0.1'", $queries[1]);
        $this->assertStringContainsString("fetch_size '2000'", $queries[1]);
        $this->assertStringContainsString('CREATE USER MAPPING IF NOT EXISTS FOR CURRENT_USER', $queries[2]);
        $this->assertStringContainsString('GRANT USAGE ON FOREIGN SERVER fdw_server_pgsql_source TO CURRENT_USER;', $queries[3]);
        $this->assertStringContainsString('CREATE SCHEMA IF NOT EXISTS fdw_pgsql_source;', $queries[4]);
    }
}
