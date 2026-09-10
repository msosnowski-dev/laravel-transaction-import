<?php

namespace App\Services\Parsers;

use App\DTO\TransactionDTO;
use Generator;
use RuntimeException;

class CsvFileParser implements FileParserInterface
{
    /**
     * @return Generator<int, TransactionDTO>
     */
    public function parse(string $filePath): Generator
    {
        if(!is_file($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException("File not found or not readable: {$filePath}");
        }

        $handle = fopen($filePath, 'rb');
        if($handle === false) {
            throw new RuntimeException("Unable to open file: {$filePath}");
        }

        try {
            // Detect bom
            $bom = fread($handle, 3);
            if($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Detect separator of csv using first line
            $firstLine = fgets($handle);
            if($firstLine === false) {
                throw new RuntimeException("Unable to read first line of file (Empty file): {$filePath}");
            }

            $separator = $this->detectSeparator($firstLine);
            rewind($handle);
            if ($bom === "\xEF\xBB\xBF") {
                fread($handle, 3);
            }

            // Read header row
            $headerRow = fgetcsv($handle, null, $separator, '"', '\\');
            if ($headerRow === false || empty($headerRow)) {
                return;
            }

            $headers = array_map(function($col) {
                return strtolower(trim((string) $col));
            }, $headerRow);

            while(($row = fgetcsv($handle, null, $separator, '"', '\\')) !== false) {

                // Skip completly empty lines
                if(count($row) === 1 && $row[0] === null) {
                    continue;
                }

                $record = [];
                foreach($headers as $index => $colName) {
                    $record[$colName] = $row[$index] ?? null;
                }

                yield TransactionDTO::fromArray($record);

            }

        } finally {
            fclose($handle);
        }
    }

    /**
     * Detects separator by counting occurrences of ',' and ';'.
     */
    protected function detectSeparator(string $sample): string
    {
        $commaCount = substr_count($sample, ',');
        $semicolonCount = substr_count($sample, ';');

        return $semicolonCount > $commaCount ? ';' : ',';
    }
}