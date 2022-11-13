<?php

namespace App\Services\Tas\Wrappers;

use App\Models\Tas\Userbot;
use App\Services\Tas\Enums\AuthStatus;
use App\Services\Tas\System;
use App\Services\Tas\Traits\BotQuery;
use Illuminate\Support\Facades\Http;

/**
 * Обёртка для пользователя TAS
 */
class UserbotWrapper extends BaseWrapper
{
    use BotQuery;

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
            'phone' => '+' . $this->identifier,
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


    public function getChats(): ?array
    {
        $query = $this->query('post', 'messages', 'getDialogs');

        if (!isset($query['response']['dialogs'])) {
            return null;
        }

        return collect($query['response']['dialogs'])->where('peer._', '=', 'peerChat')
            ->map(fn($chat) => [
                'id' => $chat['peer']['chat_id'],
                'info' => $this->query('get', 'getInfo', params: [
                    'id[_]' => $chat['peer']['_'],
                    'id[chat_id]' => $chat['peer']['chat_id'],
                ])['response'],
            ])->toArray();
    }

    public function joinChat($link): bool
    {
        $query = $this->query('post', 'messages', 'importChatInvite', [
            'hash' => trim($link),
        ]);

        if (isset($query['response']['updates']['chats'][0]['id'])) {
            $userbot = Userbot::where('phone', $this->getSessionName($this->identifier))->first();
            $userbot->peers->push($query['response']['updates']['chats'][0]['id']);
            $userbot->save();
        }

        return (bool) $query;
    }


    public function getSessionName($identifier): string
    {
        return $this->system->phoneToSessionName($identifier);
    }
}
