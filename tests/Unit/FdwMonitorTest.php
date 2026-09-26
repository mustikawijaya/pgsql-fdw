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
}
