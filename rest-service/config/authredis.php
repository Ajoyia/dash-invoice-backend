<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Redis Sender Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for connecting to Redis,
    | specifically for the Permission Sender functionality.
    | These credentials are used by the EnqueuePermissions Artisan command.
    |
    */

    'connection' => [
        'host' => env('DT_REDIS_HOST', '127.0.0.1'),
        'port' => env('DT_REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD', null),
        'timeout' => env('REDIS_TIMEOUT', 0.0),
        'persistent' => env('REDIS_PERSISTENT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Queue Name
    |--------------------------------------------------------------------------
    |
    | The name of the Redis queue to which permissions will be enqueued.
    |
    */

    'queue' => env('REDIS_QUEUE', 'permissions_queue'),

];
