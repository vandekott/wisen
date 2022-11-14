<?php
return [
    'host' => env('TAS_HOST', 'tas'),
    'port' => env('TAS_PORT', '9503'),
    'bots' => [
        'notifier' => [
            'session_name' => 'notifier',
            'token' => env('TAS_NOTIFIER_BOT_KEY', '5718378123:AAH8mkSUIwxXZ-wHdkDTKv_22UemgHkzKrY'),
            'peer' => [
                "_" => "peerChannel",
                "channel_id" => 1656740693,
            ],
        ],
    ]
];
