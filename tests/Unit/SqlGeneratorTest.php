<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Unit;

use MustikaWijaya\PgsqlFdw\Services\SqlGenerator;
use MustikaWijaya\PgsqlFdw\Tests\TestCase;

class SqlGeneratorTest extends TestCase
{
    private SqlGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new SqlGenerator();
    }

    public function test_it_generates_idempotent_foreign_table_ddl_with_exact_data_types(): void
    {
        $columns = [
            [
                'name' => 'id',
                'type' => 'bigint',
                'nullable' => false,
            ],
            [
                'name' => 'title',
                'type' => 'character varying(255)',
                'nullable' => false,
            ],
            [
                'name' => 'price',
                'type' => 'numeric(15,2)',
                'nullable' => true,
            ],
            [
                'name' => 'order', // PostgreSQL reserved keyword
                'type' => 'integer',
                'nullable' => true,
            ],
            [
                'name' => 'created_at',
                'type' => 'timestamp(0) without time zone',
                'nullable' => true,
            ],
        ];

        $sql = $this->generator->generate(
            source: 'source_db',
            dest: 'dest_db',
            table: 'products',
            columns: $columns,
            targetSchema: 'fdw_source_db',
            serverName: 'fdw_server_source_db',
            remoteSchema: 'public'
        );

        $this->assertStringContainsString('DROP FOREIGN TABLE IF EXISTS fdw_source_db.products;', $sql);
        $this->assertStringContainsString('CREATE FOREIGN TABLE fdw_source_db.products (', $sql);
        $this->assertStringContainsString('"id" bigint NOT NULL,', $sql);
        $this->assertStringContainsString('"title" character varying(255) NOT NULL,', $sql);
        $this->assertStringContainsString('"price" numeric(15,2),', $sql);
        $this->assertStringContainsString('"order" integer,', $sql);
        $this->assertStringContainsString('"created_at" timestamp(0) without time zone', $sql);
        $this->assertStringContainsString("SERVER fdw_server_source_db", $sql);
        $this->assertStringContainsString("OPTIONS (schema_name 'public', table_name 'products');", $sql);
    }

    public function test_it_embeds_helpful_comment_when_user_defined_type_is_detected(): void
    {
        $columns = [
            [
                'name' => 'status',
                'type' => 'user_status_enum',
                'is_udt' => true,
                'nullable' => false,
            ],
        ];

        $sql = $this->generator->generate(
            source: 'source_db',
            dest: 'dest_db',
            table: 'users',
            columns: $columns,
            targetSchema: 'fdw_source_db',
            serverName: 'fdw_server_source_db'
        );

        $this->assertStringContainsString('-- Warning: User-Defined Type (UDT) detected: user_status_enum', $sql);
    }
}
