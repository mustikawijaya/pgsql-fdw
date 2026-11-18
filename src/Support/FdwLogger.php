<?php

namespace MustikaWijaya\PgsqlFdw\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

class FdwLogger
{
    /**
     * Log a successful DDL execution with masked credentials.
     */
    public static function info(string $connection, string $sql): void
    {
        $maskedSql = CredentialMasker::mask($sql);
        self::write('info', "Executed FDW DDL on [{$connection}]: {$maskedSql}");
    }

    /**
     * Log a failed DDL execution with masked credentials.
     */
    public static function error(string $connection, string $message, string $sql): void
    {
        $maskedMessage = CredentialMasker::mask($message);
        $maskedSql = CredentialMasker::mask($sql);
        self::write('error', "Failed FDW DDL on [{$connection}]: {$maskedMessage} | SQL: {$maskedSql}");
    }

    private static function write(string $level, string $message): void
    {
        $channel = self::resolveChannel();
        if ($channel === null) {
            return;
        }

        try {
            Log::channel($channel)->{$level}($message);
        } catch (Throwable $e) {
            if (
                $e instanceof \PHPUnit\Framework\Exception
                || str_starts_with(get_class($e), 'Mockery\\')
            ) {
                throw $e;
            }
            // Best-effort logging: broken or unwritable log_channel must not break DDL execution or hide FdwExecutionException.
        }
    }

    private static function resolveChannel(): ?string
    {
        $channel = config('pgsql-fdw.log_channel', 'single');

        if (!is_string($channel) || $channel === '' || strtolower($channel) === 'none') {
            return null;
        }

        return $channel;
    }
}
