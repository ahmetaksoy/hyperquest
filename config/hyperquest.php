<?php

return [
    'log' => [
        'type' => env('HYPERQUEST_LOG_TYPE', 'table'),
        'table' => env('HYPERQUEST_DB_TABLE', 'hyper_request_logs'),
    ],
];