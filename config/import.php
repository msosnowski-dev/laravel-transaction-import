<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Import Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for importing transaction files.
    |
    */
    'amount_in_minor_units' => (bool) env('IMPORT_AMOUNT_IN_MINOR_UNITS', true),

    'json' => [
        'streaming_threshold_mb' => (int) env('IMPORT_JSON_STREAMING_THRESHOLD_MB', 10),
        'show_transactions' => (bool) env('IMPORT_SHOW_TRANSACTIONS', false),
    ],
];