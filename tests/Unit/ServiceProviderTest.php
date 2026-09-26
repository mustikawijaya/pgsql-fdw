<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Unit;

use MustikaWijaya\PgsqlFdw\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_it_merges_package_configuration(): void
    {
        $this->assertTrue(config()->has('pgsql-fdw'));
        $this->assertEquals('fdw_', config('pgsql-fdw.schema_prefix'));
        $this->assertTrue(config('pgsql-fdw.use_schema_isolation'));
        $this->assertEquals('2000', config('pgsql-fdw.options.server.fetch_size'));
    }
}
