<?php

namespace App\Services\TelegramService\Bots;

use App\Services\TelegramService\Traits\BotQuery;
use App\Services\TelegramService\Traits\WsUrls;
use App\Services\TelegramService\Wrappers\BotWrapper;

class NotifierBot extends BotWrapper
{
    use WsUrls, BotQuery;

    public array $config;
    public static ?NotifierBot $instance = null;
    public string $session_name = 'notifier';
    private string $scope = 'notifier';

    public function __construct()
    {
        parent::__construct($this->session_name);
        $this->config = config('tas.bots.' . $this->session_name);
    }

    public static function getInstance($identifier = null): ?NotifierBot
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
