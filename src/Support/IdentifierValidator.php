<?php

namespace MustikaWijaya\PgsqlFdw\Support;

use InvalidArgumentException;

class IdentifierValidator
{
    public const IDENTIFIER_REGEX = '/^[a-zA-Z0-9_\-]+$/';

    public const OPTION_KEY_REGEX = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';

    /**
     * Validate a PostgreSQL identifier or Laravel connection name.
     */
    public static function validate(string $value, string $label = 'identifier'): void
    {
        if (!preg_match(self::IDENTIFIER_REGEX, $value)) {
            throw new InvalidArgumentException(
                "Invalid identifier provided for {$label}: [{$value}]. Only alphanumeric characters, dashes, and underscores are allowed."
            );
        }
    }

    /**
     * Validate a filesystem directory or file stem against path traversal.
     */
    public static function validatePathComponent(string $value, string $label = 'component'): void
    {
        if ($value !== basename($value) || !preg_match(self::IDENTIFIER_REGEX, $value)) {
            throw new InvalidArgumentException(
                "Invalid file or directory component for {$label}: [{$value}]. Path traversal or invalid characters detected."
            );
        }
    }

    /**
     * Validate a PostgreSQL FDW server or table option key.
     */
    public static function validateOptionKey(string $key): void
    {
        if (!preg_match(self::OPTION_KEY_REGEX, $key)) {
            throw new InvalidArgumentException(
                "Invalid FDW option key [{$key}]: only alphanumeric characters and underscores starting with a letter or underscore are allowed."
            );
        }
    }
}
