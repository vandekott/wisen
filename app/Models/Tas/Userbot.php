<?php

namespace App\Models\Tas;

use App\Services\Tas\Enums\AuthStatus;
use App\Services\Tas\Enums\Status;
use App\Services\Tas\Wrappers\UserbotWrapper;
use Illuminate\Database\Eloquent\Model;

class Userbot extends Model
{
    protected $fillable = [
        'phone',
        'session',
        'last_auth_status',
        'current_status',
        'listen_peers',
        'need_admin_interact',
    ];

    protected $casts = [
        'listen_peers' => 'array',
        'need_admin_interact' => 'boolean',
        'last_auth_status' => AuthStatus::class,
        'current_status' => Status::class,
    ];

    /**
     * Возвращает актуальное состояние юзербота, совершает действия, если необходимо
     * @return $this
     */
    public function ensureActualStatement(): Userbot
    {
        $api = $this->getApi();
        $this->last_auth_status = $api->updateStatus();
        $this->need_admin_interact = !$api->authenticated();
        /* Статус "на проверку админу" */
        if (!$api->authenticated()) $this->need_admin_interact = Status::WAITING_ADMIN;
        /* Подписать бота на группы, если он ещё не подписан */
        if ($this->listen_peers) $api->ensureSubscribed($this->listen_peers);

        return $this;
    }

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

    protected static function booting()
    {
        parent::booting();

        /* При создании */
        static::creating(function ($model) {
            $model->session = $model->getApi()->getSessionName($model->phone);
            $model->ensureActualStatement();
            /* Сразу же просим код авторизации, чтобы не заставлять админа ждать */
            $model->getApi()->phoneLogin();
        });

        /* При обновлении */
        static::updating(function ($model) {
            $model->ensureActualStatement();
        });

        /* При удалении */
        static::deleting(function ($model) {
            $model->getApi()->deleteSession();
        });
    }
}
