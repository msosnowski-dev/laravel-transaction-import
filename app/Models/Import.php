<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'file_name',
        'total_records',
        'successful_records',
        'failed_records',
        'status',
    ];

    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLogs::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transactions::class);
    }
}