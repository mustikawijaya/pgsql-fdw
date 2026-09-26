<?php

namespace MustikaWijaya\PgsqlFdw\Exceptions;

use RuntimeException;
use Throwable;

class FdwExecutionException extends RuntimeException
{
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        if (str_contains($message, '42501') || str_contains(strtolower($message), 'permission denied')) {
            $message .= "\n[Hint: Creating FDW servers/extensions may require DBA or 'rds_superuser' privileges.]";
        }

        parent::__construct($message, $code, $previous);
    }
}
