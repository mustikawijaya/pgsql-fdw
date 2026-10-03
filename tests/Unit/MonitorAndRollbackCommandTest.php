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

        $output = Artisan::output();
        $this->assertStringContainsString('srv_1', $output);
        $this->assertStringContainsString('postgres_fdw', $output);
        $this->assertStringContainsString('postgres', $output);
        $this->assertStringContainsString('fdw_src', $output);
        $this->assertStringContainsString('users', $output);
    }

    public function test_monitor_command_renders_tables_with_array_rows_without_errors(): void
    {
        $mockMonitor = $this->createMock(\MustikaWijaya\PgsqlFdw\Services\FdwMonitor::class);
        $mockMonitor->method('getForeignServers')->willReturn([
            ['server_name' => 'srv_array', 'wrapper_name' => 'postgres_fdw', 'options' => 'host 127.0.0.1'],
        ]);
        $mockMonitor->method('getUserMappings')->willReturn([
            ['local_user' => 'postgres_arr', 'server_name' => 'srv_array', 'options' => 'user remote'],
        ]);
        $mockMonitor->method('getForeignTables')->willReturn([
            ['foreign_schema' => 'fdw_arr', 'foreign_table' => 'orders', 'server_name' => 'srv_array', 'options' => ''],
        ]);

        $this->app->instance(\MustikaWijaya\PgsqlFdw\Services\FdwMonitor::class, $mockMonitor);

        $exitCode = Artisan::call('fdw:monitor', ['--dest' => 'pgsql']);
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('srv_array', $output);
        $this->assertStringContainsString('postgres_arr', $output);
        $this->assertStringContainsString('fdw_arr', $output);
        $this->assertStringContainsString('orders', $output);
    }

    public function test_monitor_command_fails_when_dest_is_missing(): void
    {
        $exitCode = Artisan::call('fdw:monitor');
        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('The --dest= option is required.', Artisan::output());
    }

    public function test_monitor_command_renders_empty_state_without_errors(): void
    {
        $mockMonitor = $this->createMock(\MustikaWijaya\PgsqlFdw\Services\FdwMonitor::class);
        $mockMonitor->method('getForeignServers')->willReturn([]);
        $mockMonitor->method('getUserMappings')->willReturn([]);
        $mockMonitor->method('getForeignTables')->willReturn([]);

        $this->app->instance(\MustikaWijaya\PgsqlFdw\Services\FdwMonitor::class, $mockMonitor);

        $exitCode = Artisan::call('fdw:monitor', ['--dest' => 'pgsql']);
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Foreign Servers (0)', $output);
        $this->assertStringContainsString('User Mappings (0)', $output);
        $this->assertStringContainsString('Foreign Tables (0)', $output);
    }

    public function test_rollback_command_fails_when_dest_is_missing(): void
    {
        $exitCode = Artisan::call('fdw:rollback');
        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('The --dest= option is required.', Artisan::output());
    }

    public function test_rollback_command_fails_when_neither_table_nor_server_is_specified(): void
    {
        $exitCode = Artisan::call('fdw:rollback', ['--dest' => 'pgsql']);
        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Either --table= or --server= must be specified.', Artisan::output());
    }

    public function test_rollback_command_drops_foreign_table_with_force(): void
    {
        $mockMonitor = $this->createMock(\MustikaWijaya\PgsqlFdw\Services\FdwMonitor::class);
        $mockMonitor->expects($this->once())
            ->method('dropForeignTable')
            ->with('pgsql', 'fdw_src', 'users', false);

        $this->app->instance(\MustikaWijaya\PgsqlFdw\Services\FdwMonitor::class, $mockMonitor);

        $exitCode = Artisan::call('fdw:rollback', [
            '--dest' => 'pgsql',
            '--table' => 'users',
            '--schema' => 'fdw_src',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Foreign table [fdw_src.users] dropped.', Artisan::output());
    }

    public function test_rollback_command_drops_foreign_server_with_force_and_cascade(): void
    {
        $mockMonitor = $this->createMock(\MustikaWijaya\PgsqlFdw\Services\FdwMonitor::class);
        $mockMonitor->expects($this->once())
            ->method('dropForeignServer')
            ->with('pgsql', 'srv_1', true);

        $this->app->instance(\MustikaWijaya\PgsqlFdw\Services\FdwMonitor::class, $mockMonitor);

        $exitCode = Artisan::call('fdw:rollback', [
            '--dest' => 'pgsql',
            '--server' => 'srv_1',
            '--cascade' => true,
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Foreign server [srv_1] dropped.', Artisan::output());
    }
}
