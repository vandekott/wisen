<?php

namespace App\Services\Tas\Wrappers;

use App\Services\Tas\Enums\AuthStatus;
use App\Services\Tas\System;
use App\Services\Tas\Traits\BotQuery;

abstract class BaseWrapper
{
    public ?AuthStatus $auth_status = null;
    public ?System $system = null;

    public string $identifier;
    public string $session_name;

    /**
     * Генерация названия сессии
     * @param string $identifier
     * @return string
     */
    abstract public function getSessionName(string $identifier): string;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
        $this->system = System::getInstance();
        $this->session_name = $this->getSessionName($identifier);
        $this->ensureSessionExist();
        $this->updateStatus();
    }

    /**
     * Проверить авторизована ли сессия
     * @return bool
     */
    public function authenticated(): bool
    {
        return
            $this->system->getSessionExist($this->session_name) &&
            AuthStatus::LOGGED_IN === $this->updateStatus();
    }

    /**
     * Проверить, ждём ли мы код
     * @return bool
     */
    public function waitingForCode(): bool
    {
        return
            $this->system->getSessionExist($this->session_name) &&
            AuthStatus::WAITING_CODE === $this->updateStatus();
    }

    /**
     * Проверить, ждём ли мы пароль
     * @return bool
     */
    public function waitingForPassword(): bool
    {
        return
            $this->system->getSessionExist($this->session_name) &&
            AuthStatus::WAITING_PASSWORD === $this->updateStatus();
    }

    /**
     * Проверить, ждём ли мы регистрацию
     * @return bool
     */
    public function waitingForSignUp(): bool
    {
        return
            $this->system->getSessionExist($this->session_name) &&
            AuthStatus::WAITING_SIGNUP === $this->updateStatus();
    }

    /**
     * Обновить статус авторизации
     * @return AuthStatus|null
     */
    public function updateStatus(): ?AuthStatus
    {
        $this->ensureSessionExist();
        $this->auth_status = $this->system->getSessionStatus($this->session_name);
        return $this->auth_status;
    }

    /**
     * Проверить, нужно ли логиниться
     * @return bool
     */
    public function notLoggedIn(): bool
    {
        return
            $this->system->getSessionExist($this->session_name) &&
            AuthStatus::NOT_LOGGED_IN === $this->updateStatus();
    }

    /**
     * Убедиться, что сессия существует
     * @return void
     */
    public function ensureSessionExist(): void
    {
        if (!$this->system->getSessionExist($this->session_name)) {
            $this->system->addSession($this->session_name);
        }
    }
}
