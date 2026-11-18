<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Unit;

use MustikaWijaya\PgsqlFdw\Services\ConnectionManager;
use MustikaWijaya\PgsqlFdw\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class CommandsTest extends TestCase
{
    public function test_artisan_commands_are_registered(): void
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('fdw:prepare', $commands);
        $this->assertArrayHasKey('fdw:make-sql', $commands);
        $this->assertArrayHasKey('fdw:migrate', $commands);
        $this->assertArrayHasKey('fdw:import-schema', $commands);
    }

    public function test_it_forwards_force_flag_from_prepare_command_to_connection_manager(): void
    {
        $mockManager = $this->createMock(ConnectionManager::class);
        $mockManager->method('getServerName')
            ->with('pgsql_src')
            ->willReturn('fdw_server_pgsql_src');
        $mockManager->expects($this->once())
            ->method('prepareDestination')
            ->with('pgsql_src', 'pgsql_dst', true);

        $this->app->instance(ConnectionManager::class, $mockManager);

        $exitCode = Artisan::call('fdw:prepare', [
            '--source' => 'pgsql_src',
            '--dest' => 'pgsql_dst',
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('CASCADE', Artisan::output());
    }
}
