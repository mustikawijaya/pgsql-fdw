<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Unit;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MustikaWijaya\PgsqlFdw\Exceptions\ConnectionException;
use MustikaWijaya\PgsqlFdw\Exceptions\FdwExecutionException;
use MustikaWijaya\PgsqlFdw\Services\ConnectionManager;
use MustikaWijaya\PgsqlFdw\Tests\TestCase;
use Psr\Log\LoggerInterface;

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
        $this->assertStringContainsString('CREATE SERVER IF NOT EXISTS "fdw_server_pgsql_source"', $queries[1]);
        $this->assertStringContainsString("host '127.0.0.1'", $queries[1]);
        $this->assertStringContainsString("fetch_size '2000'", $queries[1]);
        $this->assertStringContainsString('CREATE USER MAPPING IF NOT EXISTS FOR CURRENT_USER', $queries[2]);
        $this->assertStringContainsString('GRANT USAGE ON FOREIGN SERVER "fdw_server_pgsql_source" TO CURRENT_USER;', $queries[3]);
        $this->assertStringContainsString('CREATE SCHEMA IF NOT EXISTS "fdw_pgsql_source";', $queries[4]);
    }

    public function test_execute_ddl_masks_password_in_thrown_exception(): void
    {
        $sql = "CREATE USER MAPPING FOR CURRENT_USER SERVER srv OPTIONS (user 'admin', password 'UnmaskedPassword');";
        try {
            $this->manager->executeDdl('pgsql_source', $sql);
            $this->fail('Expected exception was not thrown');
        } catch (FdwExecutionException $e) {
            $this->assertStringNotContainsString('UnmaskedPassword', $e->getMessage());
            $this->assertStringContainsString("password '********'", $e->getMessage());
        }
    }

    public function test_execute_ddl_masks_password_with_escaped_quotes_in_thrown_exception(): void
    {
        $sql = "CREATE USER MAPPING FOR CURRENT_USER SERVER srv OPTIONS (user 'admin', password 'p''ass''''word!');";
        try {
            $this->manager->executeDdl('pgsql_source', $sql);
            $this->fail('Expected exception was not thrown');
        } catch (FdwExecutionException $e) {
            $this->assertStringNotContainsString("p''ass''''word!", $e->getMessage());
            $this->assertStringNotContainsString("word!", $e->getMessage());
            $this->assertStringContainsString("password '********'", $e->getMessage());
        }
    }

    public function test_it_escapes_single_quotes_in_password_and_credentials(): void
    {
        config([
            'database.connections.pgsql_quote_test' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => '5432',
                'database' => "db'test",
                'username' => "user'admin",
                'password' => "p'ass''word!",
                'schema' => 'public',
            ],
        ]);

        $queries = $this->manager->getPreparationQueries('pgsql_quote_test');
        $userMappingQuery = $queries[2];

        $this->assertStringContainsString("password 'p''ass''''word!'", $userMappingQuery);
        $this->assertStringContainsString("user 'user''admin'", $userMappingQuery);
    }

    public function test_it_quotes_server_and_schema_identifiers_in_preparation_ddl(): void
    {
        $queries = $this->manager->getPreparationQueries('pgsql_source');

        $this->assertStringContainsString('CREATE SERVER IF NOT EXISTS "fdw_server_pgsql_source"', $queries[1]);
        $this->assertStringContainsString('SERVER "fdw_server_pgsql_source"', $queries[2]);
        $this->assertStringContainsString('ON FOREIGN SERVER "fdw_server_pgsql_source"', $queries[3]);
        $this->assertStringContainsString('CREATE SCHEMA IF NOT EXISTS "fdw_pgsql_source"', $queries[4]);
    }

    public function test_it_rejects_malicious_connection_identifiers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid identifier');

        $this->manager->getPreparationQueries('srv; DROP SCHEMA public CASCADE; --');
    }

    public function test_it_rejects_malicious_server_option_keys_in_preparation_queries(): void
    {
        config([
            'pgsql-fdw.options.server' => [
                "fetch_size '10'); DROP TABLE x; --" => '100',
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid FDW option key');

        $this->manager->getPreparationQueries('pgsql_source');
    }

    public function test_execute_ddl_logs_masked_error_to_configured_channel(): void
    {
        config(['pgsql-fdw.log_channel' => 'fdw_audit']);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->callback(fn (string $msg): bool => str_contains($msg, '[pgsql_source]')
                && str_contains($msg, "password '********'")
                && !str_contains($msg, 'SecretPass!')));

        Log::shouldReceive('channel')->with('fdw_audit')->once()->andReturn($logger);

        try {
            $this->manager->executeDdl('pgsql_source', "CREATE USER MAPPING FOR CURRENT_USER SERVER srv OPTIONS (user 'admin', password 'SecretPass!');");
            $this->fail('Expected FdwExecutionException was not thrown');
        } catch (FdwExecutionException $e) {
            $this->assertStringContainsString("password '********'", $e->getMessage());
        }
    }

    public function test_it_builds_recreation_ddl_queries_when_force_is_true(): void
    {
        $queries = $this->manager->getPreparationQueries('pgsql_source', true);

        $this->assertCount(6, $queries);
        $this->assertEquals('CREATE EXTENSION IF NOT EXISTS postgres_fdw;', $queries[0]);
        $this->assertEquals('DROP SERVER IF EXISTS "fdw_server_pgsql_source" CASCADE;', $queries[1]);
        $this->assertStringContainsString('CREATE SERVER "fdw_server_pgsql_source"', $queries[2]);
        $this->assertStringNotContainsString('IF NOT EXISTS', $queries[2]);
        $this->assertStringContainsString('CREATE USER MAPPING FOR CURRENT_USER SERVER "fdw_server_pgsql_source"', $queries[3]);
        $this->assertStringNotContainsString('IF NOT EXISTS', $queries[3]);
        $this->assertStringContainsString('GRANT USAGE ON FOREIGN SERVER "fdw_server_pgsql_source" TO CURRENT_USER;', $queries[4]);
        $this->assertStringContainsString('CREATE SCHEMA IF NOT EXISTS "fdw_pgsql_source";', $queries[5]);
    }

    public function test_execute_ddl_still_throws_masked_fdw_execution_exception_when_log_channel_is_invalid(): void
    {
        config(['pgsql-fdw.log_channel' => 'broken_channel']);
        Log::shouldReceive('channel')
            ->with('broken_channel')
            ->once()
            ->andThrow(new \InvalidArgumentException('Log [broken_channel] is not defined.'));

        try {
            $this->manager->executeDdl('pgsql_source', "CREATE USER MAPPING FOR CURRENT_USER SERVER srv OPTIONS (user 'admin', password 'Super''Secret!');");
            $this->fail('Expected FdwExecutionException was not thrown');
        } catch (FdwExecutionException $e) {
            $this->assertStringNotContainsString('Super', $e->getMessage());
            $this->assertStringNotContainsString('Secret!', $e->getMessage());
            $this->assertStringContainsString("password '********'", $e->getMessage());
        }
    }

    public function test_execute_ddl_does_not_invoke_error_logger_when_info_logger_throws_after_ddl_succeeds(): void
    {
        config(['pgsql-fdw.log_channel' => 'broken_channel']);

        $conn = $this->createMock(ConnectionInterface::class);
        $conn->expects($this->once())->method('unprepared')->with('SELECT 1;')->willReturn(true);
        DB::shouldReceive('connection')->with('pgsql_source')->once()->andReturn($conn);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->willThrowException(new \RuntimeException('Disk full'));
        $logger->expects($this->never())->method('error');

        Log::shouldReceive('channel')->with('broken_channel')->once()->andReturn($logger);

        $this->manager->executeDdl('pgsql_source', 'SELECT 1;');
    }
}
