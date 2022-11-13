<?php

namespace App\Services\Tas\Groups;

use App\Models\Tas\Userbot;

class GroupService
{
    public static function getGroupByPeer(string $peer): ?string
    {
        $userbot = Userbot::query()->whereJsonContains('peers', $peer)->first();
    }
}
