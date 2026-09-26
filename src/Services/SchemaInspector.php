<?php

namespace MustikaWijaya\PgsqlFdw\Services;

use Illuminate\Support\Facades\DB;

class SchemaInspector
{
    public function getTableNames(string $connection, string $schema = 'public'): array
    {
        $rows = DB::connection($connection)->select("
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = ?
              AND table_type = 'BASE TABLE'
            ORDER BY table_name ASC
        ", [$schema]);

        return array_map(fn($r) => $r->table_name, $rows);
    }

    public function getTableColumns(string $connection, string $table, string $schema = 'public'): array
    {
        $rows = DB::connection($connection)->select("
            SELECT
                column_name,
                data_type,
                udt_name,
                character_maximum_length,
                numeric_precision,
                numeric_scale,
                datetime_precision,
                is_nullable
            FROM information_schema.columns
            WHERE table_schema = ?
              AND table_name = ?
            ORDER BY ordinal_position ASC
        ", [$schema, $table]);

        $columns = [];
        foreach ($rows as $row) {
            $rowData = (array)$row;
            $type = $this->formatPostgreSqlType($rowData);
            $isUdt = ($rowData['data_type'] === 'USER-DEFINED');

            $columns[] = [
                'name' => $rowData['column_name'],
                'type' => $type,
                'nullable' => ($rowData['is_nullable'] === 'YES'),
                'is_udt' => $isUdt,
            ];
        }

        return $columns;
    }

    public function formatPostgreSqlType(array $row): string
    {
        $dataType = $row['data_type'];
        $udtName = $row['udt_name'] ?? '';

        if ($dataType === 'USER-DEFINED') {
            return $udtName;
        }

        if (in_array($dataType, ['character varying', 'character', 'char', 'varchar'])) {
            $len = $row['character_maximum_length'] ?? null;
            return $len ? "{$dataType}({$len})" : $dataType;
        }

        if (in_array($dataType, ['numeric', 'decimal'])) {
            $prec = $row['numeric_precision'] ?? null;
            $scale = $row['numeric_scale'] ?? null;
            if ($prec !== null && $scale !== null) {
                return "{$dataType}({$prec},{$scale})";
            }
            if ($prec !== null) {
                return "{$dataType}({$prec})";
            }
            return $dataType;
        }

        if (str_contains($dataType, 'timestamp') || str_contains($dataType, 'time')) {
            $prec = $row['datetime_precision'] ?? null;
            if ($prec !== null && $prec < 6) {
                if (str_contains($dataType, 'without time zone')) {
                    $base = str_replace(' without time zone', '', $dataType);
                    return "{$base}({$prec}) without time zone";
                }
                if (str_contains($dataType, 'with time zone')) {
                    $base = str_replace(' with time zone', '', $dataType);
                    return "{$base}({$prec}) with time zone";
                }
            }
            return $dataType;
        }

        if ($dataType === 'ARRAY') {
            return ltrim($udtName, '_') . '[]';
        }

        return $dataType;
    }
}
