<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Unit;

use Illuminate\Support\Facades\Log;
use MustikaWijaya\PgsqlFdw\Services\FdwMonitor;
use MustikaWijaya\PgsqlFdw\Tests\TestCase;

class FdwMonitorTest extends TestCase
{
    public function test_it_can_be_instantiated(): void
    {
        $monitor = new FdwMonitor();
        $this->assertInstanceOf(FdwMonitor::class, $monitor);
    }

    public function test_it_throws_fdw_execution_exception_on_drop_table_failure(): void
    {
        $monitor = new FdwMonitor();

        $this->expectException(\MustikaWijaya\PgsqlFdw\Exceptions\FdwExecutionException::class);
        $this->expectExceptionMessage('Failed dropping foreign table');

        $monitor->dropForeignTable('non_existent_conn', 'public', 'users');
    }

    public function test_it_throws_fdw_execution_exception_on_drop_server_failure(): void
    {
        $monitor = new FdwMonitor();

        $this->expectException(\MustikaWijaya\PgsqlFdw\Exceptions\FdwExecutionException::class);
        $this->expectExceptionMessage('Failed dropping foreign server');

        $monitor->dropForeignServer('non_existent_conn', 'srv_test');
    }

    public function test_it_throws_invalid_argument_exception_on_malicious_table_identifier(): void
    {
        $monitor = new FdwMonitor();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid identifier');

        $monitor->dropForeignTable('non_existent_conn', 'public', 'users; DROP TABLE evil; --');
    }

    public function test_it_throws_invalid_argument_exception_on_malicious_server_identifier(): void
    {
        $monitor = new FdwMonitor();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid identifier');

        $monitor->dropForeignServer('non_existent_conn', 'srv; DROP SERVER evil; --');
    }

    public function test_drop_foreign_table_still_throws_fdw_execution_exception_when_log_channel_is_invalid(): void
    {
        config(['pgsql-fdw.log_channel' => 'broken_channel']);
        Log::shouldReceive('channel')
            ->with('broken_channel')
            ->once()
            ->andThrow(new \InvalidArgumentException('Log [broken_channel] is not defined.'));

        $monitor = new FdwMonitor();

        $this->expectException(\MustikaWijaya\PgsqlFdw\Exceptions\FdwExecutionException::class);
        $this->expectExceptionMessage('Failed dropping foreign table');

        $monitor->dropForeignTable('non_existent_conn', 'public', 'users');
    }
}
