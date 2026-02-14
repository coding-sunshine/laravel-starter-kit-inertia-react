<?php

declare(strict_types=1);

return [

    'vendor_id' => env('PADDLE_VENDOR_ID'),

    'vendor_auth_code' => env('PADDLE_VENDOR_AUTH_CODE'),

    'public_key' => env('PADDLE_PUBLIC_KEY'),

    'webhook_secret' => env('PADDLE_WEBHOOK_SECRET'),

    'sandbox' => env('PADDLE_SANDBOX', true),

];
