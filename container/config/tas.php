<?php

$settings = [
    'server' => [
        'address' => (string) getenv('SERVER_ADDRESS'),
        'port' => (int) getenv('SERVER_PORT'),
    ],
    'telegram' => [
        'app_info' => [ // obtained in https://my.telegram.org
            'api_id' => (int) getenv('TELEGRAM_API_ID'),
            'api_hash' => (string) getenv('TELEGRAM_API_HASH'),
        ],
        'logger' => [ // Logger settings
            'logger' => 4, //  0 - Logs disabled, 3 - echo logs.
            'logger_param' => \TelegramApiServer\EventObservers\LogObserver::class . '::log',
            'logger_level' => 3, // Logging level, available logging levels are: ULTRA_VERBOSE - 5, VERBOSE - 4 , NOTICE - 3, WARNING - 2, ERROR - 1, FATAL_ERROR - 0.
        ],
        'updates' => [
            'handle_updates' => false, // Should I handle updates?
            'handle_old_updates' => false, // Should I handle old updates on startup?
        ],
        'connection_settings' => [
            'all' => [
                'proxy' => '\SocksProxy',
                'proxy_extra' => [
                    'address' => (string) getenv('TELEGRAM_PROXY_ADDRESS'),
                    'port' => (int) getenv('TELEGRAM_PROXY_PORT'),
                    'username' => getenv('TELEGRAM_PROXY_USERNAME'),
                    'password' => getenv('TELEGRAM_PROXY_PASSWORD'),
                ]
            ],
            'media_socket_count' => [
                'max' => 50
            ]
        ],
        'serialization' => [
            'serialization_interval' => 30,
            'cleanup_before_serialization' => true,
        ],
        'db' => [
            'type' => getenv('DB_TYPE'),
            getenv('DB_TYPE') => [
                'host' => (string) getenv('DB_HOST'),
                'port' => (int) getenv('DB_PORT'),
                'user' => getenv('DB_USER'),
                'password' => getenv('DB_PASSWORD'),
                'database' => getenv('DB_DATABASE'),
                'max_connections' => (int) getenv('DB_MAX_CONNECTIONS'),
                'idle_timeout' => (int) getenv('DB_IDLE_TIMEOUT'),
                'cache_ttl' => getenv('DB_CACHE_TTL'),
            ]
        ],
        'download'=>[
            'report_broken_media' => false,
        ],
        'ipc' => [
            'slow' => true
        ]
    ],
    'api' => [
        'ip_whitelist' => array_filter(
            array_map(
                'trim',
                explode(',', getenv('IP_WHITELIST'))
            )
        ),
    ],
    'health_check' => [
        'enabled' => (bool) filter_var((string)getenv('HEALTHCHECK_ENABLED'), FILTER_VALIDATE_BOOL),
        'interval' => ((int) getenv('HEALTHCHECK_INTERVAL') ?: 30),
        'timeout' => ((int) getenv('HEALTHCHECK_REQUEST_TIMEOUT') ?: 60),
    ]
];

if (empty($settings['telegram']['connection_settings']['all']['proxy_extra']['address'])) {
    $settings['telegram']['connection_settings']['all']['proxy'] = '\Socket';
    $settings['telegram']['connection_settings']['all']['proxy_extra'] = [];
}

return $settings;
