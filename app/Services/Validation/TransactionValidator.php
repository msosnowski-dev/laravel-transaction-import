<?php

namespace App\Services\Validation;

use App\Models\Transaction;
use App\DTO\TransactionDTO;
use App\Services\Validation\TransactionValidationResult;
use Carbon\Carbon;
use Throwable;

class TransactionValidator
{
    public function __construct(
        protected IbanValidator $ibanValidator
    ) {}

    public function validate(TransactionDTO $dto): TransactionValidationResult
    {
        $errors = [];

        // Validate transaction_id
        if($dto->transactionId === null || trim($dto->transactionId) === '') {
            $errors[] = 'Missing transaction identifier (transaction_id)';
        } else {
            $txId = trim($dto->transactionId);
        }

        // IBAN validation
        if($dto->accountNumber === null || trim($dto->accountNumber) === '') {
            $errors[] = 'Missing account number (account_number)';
        } elseif(!$this->ibanValidator->validate($dto->accountNumber)) {
            $errors[] = 'Invalid IBAN: ' . $dto->accountNumber;
        }

        // Validate amount
        if($dto->amount === null || $dto->amount === '') {
            $errors[] = 'Missing transaction amount (amount).';
        } elseif (!is_numeric($dto->amount) || $dto->amount <= 0) {
            $errors[] = 'The transaction amount must be a number greater than 0.';
        }

        // Validate currency
        if($dto->currency === null || trim($dto->currency) === '') {
            $errors[] = 'Missing currency (currency).';
        } elseif(!preg_match('/^[A-Za-z]{3}$/', $dto->currency)) {
            $errors[] = 'The transaction currency must consist of 3 letters (e.g., PLN, USD).';
        }

        // Validate transaction date
        if($dto->transactionDate === null || trim($dto->transactionDate) === '') {
            $errors[] = 'Missing transaction date (transaction_date).';
        } else {
            try{
                Carbon::parse($dto->transactionDate);
            } catch (Throwable) {
                $errors[] = 'Invalid transaction date format '.$dto->transactionDate.'.';
            }
        }

        if(empty($errors)) {
            return TransactionValidationResult::success($dto);
        }

        return TransactionValidationResult::fail($errors, $dto);
    }
}