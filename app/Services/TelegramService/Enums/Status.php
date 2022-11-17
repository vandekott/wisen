<?php

namespace App\Services\TelegramService\Enums;

enum Status: int
{
    case WAITING_ADMIN = -1;
    case OFFLINE = 0;
    case RUNNING = 1;
    case PAUSED = 2;
}
