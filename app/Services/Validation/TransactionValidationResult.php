<?php

namespace App\Services\Validation;

use App\DTO\TransactionDTO;

readonly class TransactionValidationResult
{
    public function __construct(
        public bool $isValid,
        public ?TransactionDTO $dto = null,
        public array $errors = []
    ) {}

    public static function success(TransactionDTO $dto): self
    {
        return new self(isValid: true, dto: $dto);
    }

    public static function fail(array $errors, ?TransactionDTO $dto = null): self
    {
        return new self(isValid: false, dto: $dto, errors: $errors);
    }

    public function getErrorMessage(): string
    {
        return implode("\n", $this->errors);
    }
}