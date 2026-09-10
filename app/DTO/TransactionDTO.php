<?php

namespace App\DTO;

readonly class TransactionDTO
{
    public function __construct(
        public ?string $transactionId,
        public ?string $accountNumber,
        public ?string $transactionDate,
        public mixed $amount,
        public ?string $currency,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: isset($data['transaction_id']) ? trim((string) $data['transaction_id']) : null,
            accountNumber: isset($data['account_number']) ? trim((string) $data['account_number']) : null,
            transactionDate: isset($data['transaction_date']) ? trim((string) $data['transaction_date']) : null,
            amount: $data['amount'] ?? null,
            currency: isset($data['currency']) ? strtoupper(trim((string) $data['currency'])) : null,
        );
    }
}
