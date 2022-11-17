<?php

namespace App\Services\TelegramService\Groups;

use App\Models\Telegram\Userbot;

class GroupService
{
    public static function getGroupByPeer(string $peer): ?string
    {
        $userbot = Userbot::query()->whereJsonContains('peers', $peer)->first();
    }
}
