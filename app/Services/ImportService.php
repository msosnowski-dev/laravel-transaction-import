<?php

namespace App\Services;

use App\DTO\ImportResultDTO;
use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Transaction;
use App\Services\Parsers\FileParserFactory;
use App\Services\Validation\TransactionValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Brick\Math\BigDecimal;
use Throwable;

class ImportService
{
    protected const CHUNK_SIZE = 500;

    public function __construct(
        protected FileParserFactory $parserFactory,
        protected TransactionValidator $transactionValidator
    ) {}

    /**
     * Process an uploaded file.
     */
    public function importUploadedFile(UploadedFile $file): ImportResultDTO
    {
        $fileName = $file->getClientOriginalName();
        $filePath = $file->getRealPath();

        // Prevention of database record creation and parser execution.
        if (filesize($filePath) === 0) {
            throw new \InvalidArgumentException("File {$fileName} is empty.");
        }

        if ($filePath === false) {
            $filePath = $file->getPathname();
        }

        $parser = $this->parserFactory->makeFromUploadedFile($file);

        return $this->processImport($fileName, $filePath, $parser);
    }

    /**
     * Core import orchestrator.
     */

    protected function processImport(string $fileName, string $filePath, $parser): ImportResultDTO
    {
        $import = Import::create([
            'file_name' => $fileName,
            'total_records' => 0,
            'successful_records' => 0,
            'failed_records' => 0,
            'status' => ImportStatus::PROCESSING->value,
        ]);

        $validBuffer = [];
        $logBuffer = [];

        $totalCount = 0;
        $successCount = 0;
        $failedCount = 0;

        try {
            DB::transaction(function () use (
                $parser,
                $filePath,
                $import,
                &$validBuffer,
                &$logBuffer,
                &$totalCount,
                &$successCount,
                &$failedCount,
            ) {
                foreach($parser->parse($filePath) as $dto) {
                    $totalCount++;

                    $validation = $this->transactionValidator->validate($dto);

                    if($validation->isValid) {
                        $successCount++;

                        $amount = config('import.amount_in_minor_units')
                        ? (string) \Brick\Math\BigDecimal::of($dto->amount)->dividedBy(100, 2)
                        : $dto->amount;

                        $validBuffer[] = [
                            ...$validation->dto->toArray(),
                            'amount'     => $amount,
                            'import_id' => $import->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        if(count($validBuffer) >= self::CHUNK_SIZE) {
                            Transaction::insert($validBuffer);
                            $validBuffer = [];
                        }
                    } else {
                        $failedCount++;

                        $logBuffer[] = [
                            'import_id' => $import->id,
                            'transaction_id' => $dto->transactionId,
                            'error_message' => $validation->getErrorMessage(),
                        ];

                        if(count($logBuffer) >= self::CHUNK_SIZE) {
                            ImportLog::insert($logBuffer);
                            $logBuffer = [];
                        }
                    }
                }

                // Flush remaining buffers
                if(!empty($validBuffer)) {
                    Transaction::insert($validBuffer);
                    $validBuffer = [];
                }

                if(!empty($logBuffer)) {
                    ImportLog::insert($logBuffer);
                    $logBuffer = [];
                }

                if($totalCount == 0) {
                    $status = ImportStatus::FAILED;
                    ImportLog::create([
                        'import_id' => $import->id,
                        'transaction_id' => null,
                        'error_message' => 'File does not contain any records.',
                    ]);
                    $failedCount = 1;
                    $totalCount = 1;
                } else {
                    $status = $this->calculateStatus($successCount, $failedCount);
                }

                $import->update([
                    'total_records' => $totalCount,
                    'successful_records' => $successCount,
                    'failed_records' => $failedCount,
                    'status' => $status->value,
                ]);
            }
        );

                
            
        } catch (Throwable $e) {
            $import->update([
                'status' => ImportStatus::FAILED->value,
            ]);

            ImportLog::create([
                'import_id' => $import->id,
                'transaction_id' => '',
                'error_message' => 'Critical parsing error or database failure: ' . $e->getMessage(),
            ]);

            return new ImportResultDTO(
                import: $import->fresh(),
                totalRecords: $totalCount,
                successfulRecords: 0,
                failedRecords: $totalCount,
                status: ImportStatus::FAILED,
                criticalErrors: [$e->getMessage()]
            );

            
        }

        $import = $import->fresh();
        $finalStatus = ImportStatus::tryFrom($import->status) ?? ImportStatus::FAILED;

        return new ImportResultDTO(
            import: $import,
            totalRecords: $import->total_records,
            successfulRecords: $import->successful_records,
            failedRecords: $import->failed_records,
            status: $finalStatus
        );

    }


    /**
     * Calculate import status based on records count.
     */
    protected function calculateStatus(int $successCount, int $failedCount): ImportStatus
    {
        if($failedCount == 0 && $successCount >0) {
            return ImportStatus::SUCCESS;
        }

        if($successCount == 0) {
            return ImportStatus::FAILED;
        }

        return ImportStatus::PARTIAL;
    }
}