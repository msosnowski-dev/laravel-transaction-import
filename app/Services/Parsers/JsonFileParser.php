<?php

namespace App\Services\Parsers;

use App\DTO\TransactionDTO;
use Generator;
use RuntimeException;
use JsonException;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

class JsonFileParser implements FileParserInterface
{
    /**
     * @return Generator<int, TransactionDTO>
     */
    public function parse(string $filePath): Generator
    {
        if(!is_file($filePath) || !is_readable($filePath)) {
            throw new RuntimeException("File not found or not readable: {$filePath}");
        }

        $fileSize = filesize($filePath);

        if($fileSize === false) {
            throw new RuntimeException("Unable to determine file size: {$filePath}");
        }

        $streamingThresholdBytes = ((int) config('import.json.streaming_threshold_mb')) * 1024 * 1024;

        // Large files are processed using a streaming parser.
        if($fileSize > $streamingThresholdBytes) {
            try {
                $records = Items::fromFile($filePath, ['decoder' => new ExtJsonDecoder(true)]);

                foreach ($records as $index => $record) {
                    if (!is_array($record)) {
                        throw new RuntimeException('Transaction at position ' . ($index + 1) .' must be a JSON object.');
                    }

                    yield TransactionDTO::fromArray($record);
                }
            } catch (JsonException $e) {
                throw new RuntimeException("Invalid JSON format: {$filePath}");
            }

            return;
        }

        // Small files are loaded into memory and decoded at once.
        $content = file_get_contents($filePath);
        if($content === false) {
            throw new RuntimeException("Unable to read file: {$filePath}");
        }

        $trimmed = trim($content);
        if($trimmed === '') {
            throw new RuntimeException("Empty file: {$filePath}");
        }

        try {
            $records = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($records) || !array_is_list($records)) {
                throw new RuntimeException('JSON must contain a list of transactions.');
            }

            foreach($records as $record) {
                yield TransactionDTO::fromArray($record);
            }
        } catch (JsonException $e) {
            throw new RuntimeException("Invalid JSON format: {$filePath}");
        }
    }
}