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
    'json' => [
        'streaming_threshold_mb' => (int) env('IMPORT_JSON_STREAMING_THRESHOLD_MB', 10),
        'show_transactions' => (bool) env('IMPORT_SHOW_TRANSACTIONS', false),
    ],
];