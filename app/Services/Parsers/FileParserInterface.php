<?php

namespace App\Services\Parsers;

use App\DTO\TransactionDTO;
use Generator;

interface FileParserInterface
{
    /**
     * Parse the given file and yield TransactionDTO instances.
     *
     * @param string $filePath Absolute path to the file
     * @return Generator<int, TransactionDTO>
     * @throws \RuntimeException
     */
    public function parse(string $filePath) : Generator;
}
