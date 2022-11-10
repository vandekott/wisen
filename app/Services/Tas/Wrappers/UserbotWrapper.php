<?php

namespace App\Services\Tas\Wrappers;

use App\Services\Tas\Enums\AuthStatus;
use App\Services\Tas\System;
use Illuminate\Support\Facades\Http;

/**
 * Обёртка для пользователя TAS
 */
class UserbotWrapper extends BaseWrapper
{
    public static array $instances = [];

    /**
     * Получить экземпляр класса
     * @param string $identifier
     * @return UserbotWrapper|null
     */
    public static function getInstance(string $identifier): ?UserbotWrapper
    {
        if (!isset(self::$instances[$identifier])) {
            self::$instances[$identifier] = new self($identifier);
        }
        return self::$instances[$identifier];
    }

    /* МЕТОДЫ АВТОРИЗАЦИИ */

    /**
     * Авторизация по номеру телефона
     * @return bool
     */
    public function phoneLogin(): bool
    {
        /* Проверяем, есть ли сессия. Если нет - создаём */
        $this->ensureSessionExist();

        /* Прерываемся, если сессия авторизована */
        if ($this->authenticated()) return true;

        /* Запрос на логин */
        Http::get("http://{$this->system->getHost()}:{$this->system->getPort()}/api/{$this->session_name}/phoneLogin", [
            'phone' => $this->identifier,
        ]);

        /* Отдаём текущий статус сессии */
        return !$this->notLoggedIn();
    }

    /**
     * Авторизация по коду
     * @param string $code
     * @return bool
     */
    public function completePhoneLogin(string $code): bool
    {
        /* Прерываемся, если сессия авторизована */
        if ($this->waitingForPassword() || $this->waitingForSignUp()) return true;

        /* Запрос на логин */
        Http::post("http://{$this->system->getHost()}:{$this->system->getPort()}/api/{$this->session_name}/completePhoneLogin", [
            'code' => $code,
        ]);

        /* Отдаём текущий статус сессии */
        if ($this->waitingForPassword() || $this->waitingForSignUp()) {
            /* Сохраняем сессию */
            $this->system->serializeSession($this->session_name);
            $this->system->reboot();
            return true;
        }

        return false;
    }

    /**
     * Авторизация по паролю (2FA)
     * @param string $password
     * @return bool
     */
    public function complete2faLogin(string $password): bool
    {
        /* Прерываемся, если сессия авторизована */
        if ($this->authenticated()) return true;

        /* Запрос на двухфакторную авторизацию */
        Http::post("http://{$this->system->getHost()}:{$this->system->getPort()}/api/{$this->session_name}/complete2faLogin", [
            'password' => $password,
        ]);

        /* Сохраняем сессию */
        if ($this->authenticated()) {
            $this->system->serializeSession($this->session_name);
            $this->system->reboot();
            return true;
        }

        return false;
    }





    /* МЕТОДЫ РАБОТЫ С ПОЛЬЗОВАТЕЛЯМИ */


    public function ensureSubscribed(array $groups): bool
    {
        $already_subscribed = $this->getSubscribedGroups();

        $groups_to_subscribe = array_diff($groups, $already_subscribed);

        if (empty($groups_to_subscribe)) return true;

        $this->subscribeToGroups($groups_to_subscribe);

        return true;
    }

    private function getSubscribedGroups(): ?array
    {

    }

    private function subscribeToGroups(array $subscribe_to): array|bool
    {
    }


    public function getSessionName($identifier): string
    {
        return $this->system->phoneToSessionName($identifier);
    }
}
