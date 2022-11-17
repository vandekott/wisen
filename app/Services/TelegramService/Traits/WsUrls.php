<?php

namespace App\Services\TelegramService\Traits;

trait WsUrls
{
    public function getListenerUrl(): string
    {
        return sprintf(
            "ws://%s:%s/events%s",
            config('tas.host'),
            config('tas.port'),
            !$this->session_name ? '' : '/' . $this->session_name,
        );
    }
}
