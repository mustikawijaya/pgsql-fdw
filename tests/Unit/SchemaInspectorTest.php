<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Unit;

use MustikaWijaya\PgsqlFdw\Services\SchemaInspector;
use MustikaWijaya\PgsqlFdw\Tests\TestCase;

class SchemaInspectorTest extends TestCase
{
    private SchemaInspector $inspector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inspector = new SchemaInspector();
    }

    public function test_it_formats_postgresql_type_with_full_precision(): void
    {
        $rowVarchar = [
            'data_type' => 'character varying',
            'udt_name' => 'varchar',
            'character_maximum_length' => 255,
            'numeric_precision' => null,
            'numeric_scale' => null,
            'datetime_precision' => null,
        ];
        $this->assertEquals('character varying(255)', $this->inspector->formatPostgreSqlType($rowVarchar));

        $rowNumeric = [
            'data_type' => 'numeric',
            'udt_name' => 'numeric',
            'character_maximum_length' => null,
            'numeric_precision' => 15,
            'numeric_scale' => 2,
            'datetime_precision' => null,
        ];
        $this->assertEquals('numeric(15,2)', $this->inspector->formatPostgreSqlType($rowNumeric));

        $rowTimestamp = [
            'data_type' => 'timestamp without time zone',
            'udt_name' => 'timestamp',
            'character_maximum_length' => null,
            'numeric_precision' => null,
            'numeric_scale' => null,
            'datetime_precision' => 0,
        ];
        $this->assertEquals('timestamp(0) without time zone', $this->inspector->formatPostgreSqlType($rowTimestamp));
    }

    public function test_it_identifies_user_defined_types(): void
    {
        $rowUdt = [
            'data_type' => 'USER-DEFINED',
            'udt_name' => 'order_status_enum',
            'character_maximum_length' => null,
            'numeric_precision' => null,
            'numeric_scale' => null,
            'datetime_precision' => null,
        ];
        $this->assertEquals('order_status_enum', $this->inspector->formatPostgreSqlType($rowUdt));
    }

    public function test_it_formats_boundary_varchar_and_unconstrained_numeric(): void
    {
        $rowMinVarchar = [
            'data_type' => 'character varying',
            'udt_name' => 'varchar',
            'character_maximum_length' => 1,
            'numeric_precision' => null,
            'numeric_scale' => null,
            'datetime_precision' => null,
        ];
        $this->assertEquals('character varying(1)', $this->inspector->formatPostgreSqlType($rowMinVarchar));

        $rowUnconstrainedNumeric = [
            'data_type' => 'numeric',
            'udt_name' => 'numeric',
            'character_maximum_length' => null,
            'numeric_precision' => null,
            'numeric_scale' => null,
            'datetime_precision' => null,
        ];
        $this->assertEquals('numeric', $this->inspector->formatPostgreSqlType($rowUnconstrainedNumeric));
    }

    public function test_it_formats_array_and_timestamp_with_timezone(): void
    {
        $rowArray = [
            'data_type' => 'ARRAY',
            'udt_name' => '_text',
            'character_maximum_length' => null,
            'numeric_precision' => null,
            'numeric_scale' => null,
            'datetime_precision' => null,
        ];
        $this->assertEquals('text[]', $this->inspector->formatPostgreSqlType($rowArray));

        $rowTz = [
            'data_type' => 'timestamp with time zone',
            'udt_name' => 'timestamptz',
            'character_maximum_length' => null,
            'numeric_precision' => null,
            'numeric_scale' => null,
            'datetime_precision' => 3,
        ];
        $this->assertEquals('timestamp(3) with time zone', $this->inspector->formatPostgreSqlType($rowTz));
    }
}
