<?php

namespace App\Enums;

enum FileType: string
{
    case CSV = 'csv';
    case JSON = 'json';
    case XML = 'xml';

    public static function fromExtensionOrMime(string $extension, ?string $mimeType = null): ?self
    {
        $ext = strtolower($extension);
        return match ($ext) {
            'csv', => self::CSV,
            'json' => self::JSON,
            'xml' => self::XML,
            default => match ($mimeType) {
                'text/csv', 'application/csv' => self::CSV,
                'application/json' => self::JSON,
                'application/xml', 'text/xml' => self::XML,
                default => null,
            },
        };
    }
}

