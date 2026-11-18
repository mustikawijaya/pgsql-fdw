<?php

namespace MustikaWijaya\PgsqlFdw\Tests\Unit;

use Illuminate\Support\Facades\Log;
use MustikaWijaya\PgsqlFdw\Support\CredentialMasker;
use MustikaWijaya\PgsqlFdw\Support\FdwLogger;
use MustikaWijaya\PgsqlFdw\Support\IdentifierValidator;
use MustikaWijaya\PgsqlFdw\Tests\TestCase;
use Psr\Log\LoggerInterface;

class SupportHelpersTest extends TestCase
{
    public function test_identifier_validator_accepts_valid_and_rejects_invalid(): void
    {
        IdentifierValidator::validate('users_2026-v1', 'table');
        $this->assertTrue(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid identifier provided for server');
        IdentifierValidator::validate('srv; DROP TABLE users; --', 'server');
    }

    public function test_path_component_validator_rejects_traversal(): void
    {
        IdentifierValidator::validatePathComponent('users', 'table');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file or directory component');
        IdentifierValidator::validatePathComponent('../../evil', 'table');
    }

    public function test_option_key_validator_rejects_invalid_keys(): void
    {
        IdentifierValidator::validateOptionKey('fetch_size');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid FDW option key');
        IdentifierValidator::validateOptionKey("fetch_size '10'); DROP TABLE x; --");
    }

    public function test_credential_masker_redacts_simple_and_escaped_quote_passwords(): void
    {
        $sql = "CREATE USER MAPPING FOR CURRENT_USER SERVER srv OPTIONS (user 'admin', password 'p''ass''''word!');";
        $masked = CredentialMasker::mask($sql);

        $this->assertStringNotContainsString("word!", $masked);
        $this->assertStringContainsString("password '********'", $masked);
    }

    public function test_fdw_logger_logs_masked_sql_to_configured_channel_and_skips_when_disabled(): void
    {
        config(['pgsql-fdw.log_channel' => 'fdw_custom']);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with($this->callback(fn (string $msg): bool => str_contains($msg, '[pgsql_dest]')
                && str_contains($msg, "password '********'")
                && !str_contains($msg, 'SecretPass')));
        $logger->expects($this->once())
            ->method('error')
            ->with($this->callback(fn (string $msg): bool => str_contains($msg, '[pgsql_dest]')
                && str_contains($msg, "password '********'")
                && !str_contains($msg, "Secret''Pass!")));

        Log::shouldReceive('channel')->with('fdw_custom')->twice()->andReturn($logger);

        FdwLogger::info('pgsql_dest', "CREATE USER MAPPING ... OPTIONS (password 'SecretPass');");
        FdwLogger::error('pgsql_dest', "Error with password 'Secret''Pass!'", "SQL password 'Secret''Pass!'");

        config(['pgsql-fdw.log_channel' => 'none']);
        FdwLogger::info('pgsql_dest', 'SELECT 1;');
        config(['pgsql-fdw.log_channel' => null]);
        FdwLogger::error('pgsql_dest', 'err', 'SELECT 1;');
    }
}
