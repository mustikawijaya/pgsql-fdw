<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Unit;

use MustikaWijaya\PgsqlFdw\Services\SqlFileHandler;
use MustikaWijaya\PgsqlFdw\Tests\TestCase;
use Illuminate\Support\Facades\File;

class SqlFileHandlerTest extends TestCase
{
    private string $testPath;
    private SqlFileHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testPath = sys_get_temp_dir() . '/pgsql_fdw_test_' . uniqid();
        config(['pgsql-fdw.sql_output_path' => $this->testPath]);
        $this->handler = new SqlFileHandler();
    }

    protected function tearDown(): void
    {
        if (File::exists($this->testPath)) {
            File::deleteDirectory($this->testPath);
        }
        parent::tearDown();
    }

    public function test_it_saves_sql_file_in_proper_hierarchy(): void
    {
        $sql = "CREATE FOREIGN TABLE test (id int);";
        $savedPath = $this->handler->save('dest_db', 'source_db', 'users', $sql);

        $expectedPath = $this->testPath . '/dest_db/source_db/users.sql';
        $this->assertEquals($expectedPath, $savedPath);
        $this->assertFileExists($expectedPath);
        $this->assertEquals($sql, file_get_contents($expectedPath));
    }

    public function test_it_lists_all_sql_files_for_destination(): void
    {
        $this->handler->save('dest_db', 'source_db', 'users', 'SELECT 1;');
        $this->handler->save('dest_db', 'source_db', 'orders', 'SELECT 2;');
        $this->handler->save('other_dest', 'source_db', 'items', 'SELECT 3;');

        $files = $this->handler->getFiles('dest_db');

        $this->assertCount(2, $files);
        $this->assertStringEndsWith('orders.sql', $files[0]);
        $this->assertStringEndsWith('users.sql', $files[1]);
    }

    public function test_it_deletes_specified_sql_file(): void
    {
        $this->handler->save('dest_db', 'source_db', 'users', 'SELECT 1;');
        $this->assertTrue($this->handler->delete('dest_db', 'source_db', 'users'));
        $this->assertFileDoesNotExist($this->testPath . '/dest_db/source_db/users.sql');
    }
}
