<?php

namespace App\Models\Telegram;

use App\Services\TelegramService\Enums\AuthStatus;
use App\Services\TelegramService\Enums\Status;
use App\Services\TelegramService\System;
use App\Services\TelegramService\Wrappers\UserbotWrapper;
use Illuminate\Database\Eloquent\Model;

class Userbot extends Model
{
    protected $fillable = [
        'phone',
        'peers',
        'need_admin_interact',
    ];

    protected $casts = [
        'peers' => 'collection',
        'need_admin_interact' => 'boolean',
    ];

    /**
     * Получить обёртку над пользователем TAS
     * @return UserbotWrapper
     */
    public function getApi(): UserbotWrapper
    {
        $wrapper = UserbotWrapper::getInstance($this->phone);
        $wrapper->ensureSessionExist();
        return $wrapper;
    }

    public static function updateStatus()
    {
        return (bool) self::all()->each(function (Userbot $userbot) {
            $userbot->current_status = $userbot->getApi()->getStatus();

            $userbot->need_admin_interact =
                $userbot->getApi()->getAuthStatus() !== AuthStatus::LOGGED_IN;

            $userbot->save();
        });
    }

    protected static function booting()
    {
        parent::booting();

        /* При удалении */
        static::deleting(function ($model) {
            System::getInstance()->removeSession($model->getApi()->session_name);
        });
    }
}
