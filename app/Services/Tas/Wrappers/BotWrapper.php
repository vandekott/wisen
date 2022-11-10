<?php

namespace App\Services\Tas\Wrappers;

use Illuminate\Support\Facades\Http;

class BotWrapper extends BaseWrapper
{
    public static array $instances = [];

    /**
     * Получить экземпляр класса
     * @param string $identifier
     * @return BotWrapper|null
     */
    public static function getInstance(string $identifier): ?BotWrapper
    {
        if (!isset(self::$instances[$identifier])) {
            self::$instances[$identifier] = new self($identifier);
        }
        return self::$instances[$identifier];
    }

    public function __construct(string $identifier)
    {
        parent::__construct($identifier);
        $this->boot();
    }

    /**
     * Запуск авторизации бота
     * @return bool
     */
    private function botLogin(): bool
    {
        /* добавить сессию, если её вдруг нет */
        $this->ensureSessionExist();

        /* если сессия уже авторизована */
        if ($this->authenticated()) return true;

        /* запрос нужен */
        Http::post("http://{$this->system->getHost()}:{$this->system->getPort()}/api/{$this->session_name}/botLogin", [
            'token' => config('tas.bots.' . $this->identifier . '.token'),
        ]);

        if ($this->authenticated()) {
            $this->system->serializeSession($this->session_name);
            $this->system->reboot();
            sleep(12);
        }

        return $this->authenticated();
    }

    /**
     * Инициализация (вызывается только при создании объекта)
     * @return BotWrapper
     */
    private function boot(): BotWrapper
    {
        if (!$this->authenticated()) $this->botLogin();
        return $this;
    }

    public function getSessionName(string $identifier): string
    {
        return $identifier;
    }

    /**
     * Peer с которым бот будет работать
     * @return string
     */
    public function getAddressPeer(): string
    {
        return config('tas.bots.' . $this->identifier . '.peer');
    }
}
