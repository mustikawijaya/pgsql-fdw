<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use MustikaWijaya\PgsqlFdw\Services\SchemaInspector;
use MustikaWijaya\PgsqlFdw\Services\SqlGenerator;
use MustikaWijaya\PgsqlFdw\Tests\TestCase;

class MakeSqlCommandTest extends TestCase
{
    private string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPath = sys_get_temp_dir() . '/pgsql_fdw_makesql_' . uniqid();
        config([
            'pgsql-fdw.sql_output_path' => $this->tempPath,
            'pgsql-fdw.use_schema_isolation' => true,
            'pgsql-fdw.schema_prefix' => 'fdw_',
            'database.connections.pgsql_src' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => '5432',
                'database' => 'src_db',
                'username' => 'postgres',
                'password' => 'secret',
                'schema' => 'public',
            ],
            'database.connections.pgsql_dst' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => '5432',
                'database' => 'dst_db',
                'username' => 'postgres',
                'password' => 'secret',
                'schema' => 'public',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->tempPath)) {
            File::deleteDirectory($this->tempPath);
        }

        parent::tearDown();
    }

    public function test_make_sql_fails_and_creates_no_files_when_single_table_has_no_columns(): void
    {
        $mockInspector = $this->createMock(SchemaInspector::class);
        $mockInspector->expects($this->once())
            ->method('getTableColumns')
            ->with('pgsql_src', 'missing_table', 'public')
            ->willReturn([]);

        $mockGenerator = $this->createMock(SqlGenerator::class);
        $mockGenerator->expects($this->never())->method('generate');

        $this->app->instance(SchemaInspector::class, $mockInspector);
        $this->app->instance(SqlGenerator::class, $mockGenerator);

        $exitCode = Artisan::call('fdw:make-sql', [
            '--source' => 'pgsql_src',
            '--dest' => 'pgsql_dst',
            '--table' => 'missing_table',
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('does not exist or has no columns', Artisan::output());
        $this->assertDirectoryDoesNotExist($this->tempPath);
    }

    public function test_make_sql_all_skips_tables_without_columns_and_reports_accurate_count(): void
    {
        $mockInspector = $this->createMock(SchemaInspector::class);
        $mockInspector->expects($this->once())
            ->method('getTableNames')
            ->with('pgsql_src', 'public')
            ->willReturn(['empty_tbl', 'users']);
        $mockInspector->expects($this->exactly(2))
            ->method('getTableColumns')
            ->willReturnMap([
                ['pgsql_src', 'empty_tbl', 'public', []],
                ['pgsql_src', 'users', 'public', [['name' => 'id', 'type' => 'bigint', 'nullable' => false, 'is_udt' => false]]],
            ]);

        $this->app->instance(SchemaInspector::class, $mockInspector);

        $exitCode = Artisan::call('fdw:make-sql', [
            '--source' => 'pgsql_src',
            '--dest' => 'pgsql_dst',
            '--all' => true,
        ]);

        $output = Artisan::output();
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Skipping table [public.empty_tbl]', $output);
        $this->assertStringContainsString('Scaffolding complete for 1 table(s).', $output);
        $this->assertFileExists($this->tempPath . '/pgsql_dst/pgsql_src/users.sql');
        $this->assertFileDoesNotExist($this->tempPath . '/pgsql_dst/pgsql_src/empty_tbl.sql');
    }
}
