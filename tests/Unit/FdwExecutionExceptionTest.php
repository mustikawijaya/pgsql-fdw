<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Unit;

use MustikaWijaya\PgsqlFdw\Exceptions\FdwExecutionException;
use MustikaWijaya\PgsqlFdw\Tests\TestCase;

class FdwExecutionExceptionTest extends TestCase
{
    public function test_it_redacts_password_in_exception_message(): void
    {
        $rawMessage = "Failed executing DDL: CREATE USER MAPPING FOR CURRENT_USER SERVER srv OPTIONS (user 'admin', password 'SuperSecret123');";
        $exception = new FdwExecutionException($rawMessage);

        $this->assertStringNotContainsString('SuperSecret123', $exception->getMessage());
        $this->assertStringContainsString("password '********'", $exception->getMessage());
    }

    public function test_it_redacts_password_containing_escaped_quotes_in_exception_message(): void
    {
        $rawMessage = "Failed executing DDL: CREATE USER MAPPING FOR CURRENT_USER SERVER srv OPTIONS (user 'admin', password 'p''ass''''word!');";
        $exception = new FdwExecutionException($rawMessage);

        $this->assertStringNotContainsString("p''ass''''word!", $exception->getMessage());
        $this->assertStringNotContainsString("word!", $exception->getMessage());
        $this->assertStringContainsString("password '********'", $exception->getMessage());
    }

    public function test_it_appends_dba_permission_hint_on_code_42501_or_permission_denied(): void
    {
        $exception = new FdwExecutionException("SQLSTATE[42501]: Insufficient privilege: permission denied");

        $this->assertStringContainsString('[Hint: Creating FDW servers/extensions may require DBA', $exception->getMessage());
    }
}
