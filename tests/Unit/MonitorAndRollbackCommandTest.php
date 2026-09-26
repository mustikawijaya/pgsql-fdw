<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Unit;

use MustikaWijaya\PgsqlFdw\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class MonitorAndRollbackCommandTest extends TestCase
{
    public function test_monitor_and_rollback_commands_are_registered(): void
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('fdw:monitor', $commands);
        $this->assertArrayHasKey('fdw:rollback', $commands);
    }

    public function test_monitor_command_renders_tables_with_stdclass_objects_without_errors(): void
    {
        $mockMonitor = $this->createMock(\MustikaWijaya\PgsqlFdw\Services\FdwMonitor::class);
        $mockMonitor->method('getForeignServers')->willReturn([
            (object)['server_name' => 'srv_1', 'wrapper_name' => 'postgres_fdw', 'options' => 'host 127.0.0.1'],
        ]);
        $mockMonitor->method('getUserMappings')->willReturn([
            (object)['local_user' => 'postgres', 'server_name' => 'srv_1', 'options' => 'user remote'],
        ]);
        $mockMonitor->method('getForeignTables')->willReturn([
            (object)['foreign_schema' => 'fdw_src', 'foreign_table' => 'users', 'server_name' => 'srv_1', 'options' => ''],
        ]);

        $this->app->instance(\MustikaWijaya\PgsqlFdw\Services\FdwMonitor::class, $mockMonitor);

        $exitCode = Artisan::call('fdw:monitor', ['--dest' => 'pgsql']);
        $this->assertEquals(0, $exitCode);
    }
}
