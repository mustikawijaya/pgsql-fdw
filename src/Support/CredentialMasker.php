<?php

namespace MustikaWijaya\PgsqlFdw\Support;

class CredentialMasker
{
    /**
     * Redact password literals (including escaped single quotes) in SQL or error messages.
     */
    public static function mask(string $text): string
    {
        return (string) preg_replace("/password\s+'(?:[^']|'')*'/i", "password '********'", $text);
    }
}
