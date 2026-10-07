<?php

namespace MustikaWijaya\PgsqlFdw\Services;

use Illuminate\Support\Facades\File;

class SqlFileHandler
{
    public function getBasePath(): string
    {
        return config('pgsql-fdw.sql_output_path', database_path('fdw'));
    }

    public function getDirectoryPath(string $dest, ?string $source = null): string
    {
        $this->validatePathComponent($dest);

        $path = $this->getBasePath() . '/' . $dest;

        if ($source !== null) {
            $this->validatePathComponent($source);
            $path .= '/' . $source;
        }

        return $path;
    }

    public function save(string $dest, string $source, string $table, string $sql): string
    {
        $this->validatePathComponent($table);
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
        $this->validatePathComponent($table);
        $filePath = $this->getDirectoryPath($dest, $source) . '/' . $table . '.sql';

        if (File::exists($filePath)) {
            return File::delete($filePath);
        }

        return false;
    }

    private function validatePathComponent(string $name): void
    {
        if ($name !== basename($name) || !preg_match('/^[a-zA-Z0-9_\-]+$/', $name)) {
            throw new \InvalidArgumentException("Invalid file or directory component: [{$name}]. Path traversal or invalid characters detected.");
        }
    }
}
