<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Storage Path
    |--------------------------------------------------------------------------
    |
    | Directory where backup archives are stored. Keep this path outside the
    | public web root and sync it to external object storage.
    |
    */
    'path' => env('BACKUP_PATH', storage_path('app/backups')),

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Number of most recent backup archives to keep locally.
    |
    */
    'keep_last' => (int) env('BACKUP_KEEP_LAST', 30),

    /*
    |--------------------------------------------------------------------------
    | Schedule
    |--------------------------------------------------------------------------
    */
    'schedule' => [
        'daily_at' => env('BACKUP_DAILY_AT', '02:30'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Directories Included In Backup
    |--------------------------------------------------------------------------
    |
    | Relative paths inside storage/app.
    |
    */
    'file_paths' => [
        'public',
        'private',
    ],
];
