<?php
return [
    'host' => env('TAS_HOST', 'tas'),
    'port' => env('TAS_PORT', '9503'),
    'bots' => [
        'notifier' => [
            'session_name' => 'notifier',
            'token' => env('TAS_NOTIFIER_BOT_KEY', '5718378123:AAH8mkSUIwxXZ-wHdkDTKv_22UemgHkzKrY'),
            'peer' => env('TAS_NOTIFIER_BOT_PEER', \Spatie\Valuestore\Valuestore::make(config('filament-settings.path'))->get('notifier_chat_id') ?? 'vandekott'),
        ],
    ]
];
