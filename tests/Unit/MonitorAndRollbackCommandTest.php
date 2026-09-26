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
}
