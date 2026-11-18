<?php

namespace MustikaWijaya\PgsqlFdw\Services;

use Illuminate\Support\Facades\File;
use MustikaWijaya\PgsqlFdw\Support\IdentifierValidator;

class SqlFileHandler
{
    public function getBasePath(): string
    {
        return config('pgsql-fdw.sql_output_path', database_path('fdw'));
    }

    public function getDirectoryPath(string $dest, ?string $source = null): string
    {
        IdentifierValidator::validatePathComponent($dest, 'dest');

        $path = $this->getBasePath() . '/' . $dest;

        if ($source !== null) {
            IdentifierValidator::validatePathComponent($source, 'source');
            $path .= '/' . $source;
        }

        return $path;
    }

    public function save(string $dest, string $source, string $table, string $sql): string
    {
        IdentifierValidator::validatePathComponent($table, 'table');
        $dir = $this->getDirectoryPath($dest, $source);

        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $filePath = $dir . '/' . $table . '.sql';
        File::put($filePath, $sql);

        return $filePath;
    }

    public function getFiles(string $dest, ?string $source = null): array
    {
        $dir = $this->getDirectoryPath($dest, $source);

        if (!File::exists($dir)) {
            return [];
        }

        $allFiles = File::allFiles($dir);
        $sqlFiles = [];

        foreach ($allFiles as $file) {
            if ($file->getExtension() === 'sql') {
                $sqlFiles[] = $file->getRealPath();
            }
        }

        sort($sqlFiles);

        return $sqlFiles;
    }

    public function delete(string $dest, string $source, string $table): bool
    {
        IdentifierValidator::validatePathComponent($table, 'table');
        $filePath = $this->getDirectoryPath($dest, $source) . '/' . $table . '.sql';

        if (File::exists($filePath)) {
            return File::delete($filePath);
        }

        return false;
    }
}
