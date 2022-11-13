<?php

namespace App\Services\Tas;

use App\Services\Tas\Enums\AuthStatus;
use danog\MadelineProto\MTProto;
use Illuminate\Support\Facades\Http;

class System
{
    public static ?System $instance = null;
    private ?string $host;
    private ?string $port;

    /**
     * @return System|null
     */
    public static function getInstance(): ?System
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->host = config('tas.host');
        $this->port = config('tas.port');
    }

    /**
     * Преобразует номер телефона в название сессии
     * @param string $phone
     * @return string
     */
    public function phoneToSessionName(string $phone): string
    {
        return '+' . preg_replace('/\D/', '', $phone);
    }

    /**
     * Отвязка файла сессии (удаляет файл сессии)
     * @param string $session
     * @return bool
     */
    public function unlinkSessionFile(string $session): bool
    {
        return Http::get("http://{$this->host}:{$this->port}/unlinkSessionFile", ['session' => $session])
            ->successful();
    }

    /**
     * Добавить сессию в TAS
     * @param string $session
     * @return bool
     */
    public function addSession(string $session): bool
    {
        Http::get("http://{$this->host}:{$this->port}/system/addSession", ['session' => $session]);
        return $this->getSessionExist($session);
    }

    /**
     * Проверить сессию на существование
     * @param string $session
     * @return bool
     */
    public function getSessionExist(string $session): bool
    {
        return isset($this->getSessionList()[$session]);
    }

    /**
     * Получить статус сессии
     * @param string $session
     * @return AuthStatus|null
     */
    public function getSessionStatus(string $session): ?AuthStatus
    {
        $raw = ($this->getSessionExist($session))
            ? $this->getSessionList()[$session]['status']
            : 'NOT_EXIST';

        return match ($raw) {
            'NOT_EXIST' => AuthStatus::NOT_EXIST,
            'NOT_LOGGED_IN' => AuthStatus::NOT_LOGGED_IN,
            'WAITING_CODE' => AuthStatus::WAITING_CODE,
            'WAITING_PASSWORD' => AuthStatus::WAITING_PASSWORD,
            'WAITING_SIGNUP' => AuthStatus::WAITING_SIGNUP,
            'LOGGED_IN' => AuthStatus::LOGGED_IN,
            default => null,
        };
    }

    /**
     * Удалить сессию из TAS
     * @param string $session
     * @return bool
     */
    public function removeSession(string $session): bool
    {
        Http::get("http://{$this->host}:{$this->port}/system/removeSession", [
            'session' => $session
        ]);

        /* Перезапуск TAS для обновления сессий (даже после удаления файлов сессия останется в памяти) */
        $this->unlinkSessionFile($session) && $this->reboot() && sleep(12);

        return !$this->getSessionExist($session);
    }

    /**
     * Получить список сессий
     * @return mixed
     */
    public function getSessionList(): mixed
    {
        return Http::get("http://{$this->host}:{$this->port}/system/getSessionList")
            ->json()['response']['sessions'] ?? null;
    }

    /**
     * Сохранить текущую сессию
     * @param string $session
     * @return string
     */
    public function serializeSession(string $session): string
    {
        return Http::get("http://{$this->getHost()}:{$this->getPort()}/api/{$session}/serialize")->successful();
    }

    /**
     * Перезапуск сервиса (требуется запуск из Docker / Supervisor)
     * @return bool
     */
    public function reboot(): bool
    {
        return (bool) Http::get("http://{$this->host}:{$this->port}/system/exit")->successful();
    }

    /**
     * Хост TAS
     * @return string
     */
    public function getHost(): string
    {
        return (string) $this->host;
    }

    /**
     * Порт TAS
     * @return string
     */
    public function getPort(): string
    {
        return (string) $this->port;
    }
}
