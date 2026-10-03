<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Unit;

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
}
