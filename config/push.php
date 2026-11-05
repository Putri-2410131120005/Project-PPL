<?php

return [
    'timeout' => (int) env('WEB_PUSH_TIMEOUT', 10),
    'vapid' => [
        'subject' => env('VAPID_SUBJECT'),
        'publicKey' => env('VAPID_PUBLIC_KEY'),
        'privateKey' => env('VAPID_PRIVATE_KEY'),
    ],
];
