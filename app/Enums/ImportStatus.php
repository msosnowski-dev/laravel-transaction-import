<?php

namespace App\Enums;

enum ImportStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCESS = 'success';
    case PARTIAL = 'partial';
    case FAILED = 'failed';

    public function label()
    {
        return match($this) {
            self::PENDING => 'Oczekujący',
            self::PROCESSING => 'W trakcie',
            self::SUCCESS => 'Zakończony',
            self::PARTIAL => 'Częściowy',
            self::FAILED => 'Błąd importu',
        };
    }
}
