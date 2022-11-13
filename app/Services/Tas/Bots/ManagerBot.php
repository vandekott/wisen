<?php

namespace App\Services\Tas\Bots;

use App\Services\Tas\Traits\BotQuery;
use App\Services\Tas\Traits\WsUrls;
use App\Services\Tas\Wrappers\BotWrapper;

class
ManagerBot
{
    use WsUrls, BotQuery;

    public array $config;
    public static ?ManagerBot $instance = null;
    public ?BotWrapper $api;
    public string $session_name = 'manager';
    private string $scope = 'manager';

    public function __construct()
    {
        $this->config = config('tas.bots.' . $this->session_name);
        $this->api = BotWrapper::getInstance($this->session_name);
    }

    public function getPermittedUsers()
    {
        return $this->config['peers'];
    }

    public static function getInstance(): ?ManagerBot
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
