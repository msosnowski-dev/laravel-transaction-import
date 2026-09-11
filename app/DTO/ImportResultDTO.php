<?php

namespace App\DTO;

use App\Enums\ImportStatus;
use App\Models\Import;

readonly class ImportResultDTO
{
    public function __construct(
        public Import $import,
        public int $totalRecords,
        public int $successfulRecords,
        public int $failedRecords,
        public ImportStatus $status,
        public array $criticalErrors = []
    ) {}
}
