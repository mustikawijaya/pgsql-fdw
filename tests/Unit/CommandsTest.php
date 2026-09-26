<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Unit;

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
}
