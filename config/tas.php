<?php
return [
    'host' => env('TAS_HOST', 'tas'),
    'port' => env('TAS_PORT', '9503'),
    'bots' => [
        'manager' => [
            'session_name' => 'manager',
            'token'=> env('TAS_MANAGER_BOT_KEY', '5685199326:AAHtzdqsQ2XHOE0Y7Z_7MXPqfV337ooQ1_c'),
            'peer' => env('TAS_MANAGER_BOT_PEER', 'vandekott'),
        ],
        'notifier' => [
            'session_name' => 'notifier',
            'token' => env('TAS_NOTIFIER_BOT_KEY', '5718378123:AAH8mkSUIwxXZ-wHdkDTKv_22UemgHkzKrY'),
            'peer' => env('TAS_NOTIFIER_BOT_PEER', 'vandekott'),
        ],
    ]
];
