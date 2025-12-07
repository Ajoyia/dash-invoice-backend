<?php

return [
    'JWT_KEY' => env('JWT_KEY'),

    //stripe
    'STRIPE_SECRET_KEY' => env('STRIPE_SECRET_KEY', ''),
    'STRIPE_SUCCESS_URL' => env('STRIPE_SUCCESS_URL'),
    'STRIPE_CANCEL_URL' => env('STRIPE_CANCEL_URL'),
    'STRIPE_WEBHOOK_SECRET' => env('STRIPE_WEBHOOK_SECRET', ''),
    'AUTH_SERVICE_URL' => env('AUTH_SERVICE_URL', 'https://auth.dev.dashinvoice.com'),
];