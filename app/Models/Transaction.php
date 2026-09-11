<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'account_number',
        'transaction_date',
        'amount',
        'currency',
        'import_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}