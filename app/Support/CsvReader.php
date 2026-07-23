<?php

namespace App\Support;

use InvalidArgumentException;

class CsvReader
{
    /**
     * @return array{headers: list<string>, rows: list<array<string, string|null>>}
     */
    public static function parse(string $contents, int $maxRows = 500): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/u', '', $contents) ?? $contents;
        $contents = trim($contents);

        if ($contents === '') {
            throw new InvalidArgumentException('The CSV file is empty.');
        }

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new InvalidArgumentException('Unable to read the CSV file.');
        }

        fwrite($handle, $contents);
        rewind($handle);

        $headerRow = fgetcsv($handle);

        if ($headerRow === false || $headerRow === [null]) {
            fclose($handle);

            throw new InvalidArgumentException('The CSV file must include a header row.');
        }

        $headers = array_map(
            fn (mixed $header): string => self::normalizeHeader((string) $header),
            $headerRow,
        );

        $rows = [];
        $lineNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            if ($row === [null] || self::isBlankRow($row)) {
                continue;
            }

            if (count($rows) >= $maxRows) {
                fclose($handle);

                throw new InvalidArgumentException("CSV imports are limited to {$maxRows} data rows.");
            }

            $associative = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $value = $row[$index] ?? null;
                $associative[$header] = is_string($value) ? trim($value) : $value;
            }

            $rows[] = $associative;
        }

        fclose($handle);

        if ($rows === []) {
            throw new InvalidArgumentException('The CSV file does not contain any data rows.');
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    public static function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));

        return (string) preg_replace('/[\s-]+/', '_', $header);
    }

    /**
     * @param  list<string|null>  $row
     */
    protected static function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
