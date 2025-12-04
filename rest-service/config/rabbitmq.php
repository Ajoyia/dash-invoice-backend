<?php

return [
    'host' => env('DT_RABBITMQ_HOST', 'rabbitmq'),
    'port' => env('DT_RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
];
