<?php

namespace App\Http\Controllers\Tas;

use App\Services\Tas\Bots\ManagerBot;
use App\Services\Tas\Enums\AuthStatus;
use App\Services\Tas\Wrappers\UserbotWrapper;

class ManagerBotController
{
    public ManagerBot $bot;
    private array $resolvableEvents = [
        'message',
    ];
    private ?array $update = null;
    private string $state = 'idle';
    private ?string $addSessionPhone = null;

    public function __construct()
    {
        $this->bot = ManagerBot::getInstance();
    }

    public function route(string $message)
    {
        if ($this->getState() !== 'idle') {
            return $this->proceedSession(trim($message));
        }

        $routeMethod = match (trim($message)) {
            '/start' => 'start',
            '/help' => 'help',
            '/cancel' => 'cancel',
            '/addSession' => 'startAddSession',
            default => 'cantHandle',
        };

        return call_user_func([$this, $routeMethod]);
    }

    /**
     * @param array $update
     * @return $this|false
     */
    public function handle(array $update)
    {
        $this->update = $update;

        if (!$this->ensureAccessPermitted()) {
            $this->bot->query('post', 'sendMessage', params: [
                'peer' => $this->getReplyTo(),
                'message' => 'Доступ запрещен'
            ]);
            return false;
        }

        if (!in_array($update['_'], $this->resolvableEvents)) {
            $this->cantHandle();
        }

        return $this->route($this->getMessage());
    }

    public function cantHandle()
    {
        return (bool) $this->bot->query('post', 'sendMessage', params: [
            'peer' => $this->getReplyTo(),
            'message' => 'Похоже, что я не могу обработать это событие. Если вы считаете, что это ошибка, напишите @vandekott',
        ]);
    }

    public function start()
    {
        if (!$this->ensureAccessPermitted()) {
            $this->bot->query('post', 'sendMessage', params: [
                'peer' => $this->getReplyTo(),
                'message' => 'Доступ запрещен'
            ]);
            return false;
        }

        $this->setState('idle');

        return (bool) $this->bot->query('post', 'sendMessage', params: [
            'peer' => $this->getReplyTo(),
            'message' => 'Привет! Я бот, который поможет тебе вести сессии. Чтобы узнать, что я умею, напиши /help',
        ]);
    }

    public function help()
    {
        $this->setState('idle');
        return (bool) $this->bot->query('post', 'sendMessage', params: [
            'peer' => $this->getReplyTo(),
            'message' => 'Я могу помочь тебе вести сессии. Чтобы начать, напиши /addSession',
        ]);
    }

    public function cancel()
    {
        $this->setState('idle');
        return (bool) $this->bot->query('post', 'sendMessage', params: [
            'peer' => $this->getReplyTo(),
            'message' => 'Отменено'
        ]);
    }

    public function startAddSession()
    {
        $this->setState('waitingForPhone');
        return (bool) $this->bot->query('post', 'sendMessage', params: [
            'peer' => $this->getReplyTo(),
            'message' => 'Введите номер телефона в формате +7XXXXXXXXXX (+7 и 10 цифр подряд)'
        ]);
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState(string $state)
    {
        $this->state = $state;
        echo "State changed to $state\n";
    }

    public function getReplyTo()
    {
        return $this->update['peer_id']['user_id'];
    }

    public function ensureAccessPermitted()
    {
        return in_array($this->update['peer_id']['user_id'], $this->bot->config['peers']);
    }

    public function getMessage(): string
    {
        return $this->update['message'] ?? '';
    }

    public function proceedSession(string $message): mixed
    {
        $status = (null !== $this->addSessionPhone)
            ? UserbotWrapper::getInstance($this->addSessionPhone)?->updateStatus()
            : AuthStatus::NOT_LOGGED_IN;

        $callback = match ($status) {
            /* Ждём номер телефона */
            AuthStatus::NOT_LOGGED_IN => function ($message) {
                $reformed = '+' . preg_replace('/\D/', '', $message);

                if (strlen($reformed) !== 12) {
                    return (bool) $this->bot->query('post', 'sendMessage', params: [
                        'peer' => $this->getReplyTo(),
                        'message' => 'Неверный формат номера телефона. Попробуйте еще раз'
                    ]);
                }

                $user = UserbotWrapper::getInstance($reformed);
                $requested = $user->phoneLogin();

                echo ($requested) ? "code sended\n" : "code not sended\n";

                $this->addSessionPhone = $reformed;

                if (!$requested) {
                    return (bool) $this->bot->query('post', 'sendMessage', params: [
                        'peer' => $this->getReplyTo(),
                        'message' => 'Запрос не прошёл! Попробуйте еще раз'
                    ]);
                }

                $this->setState('waitingForCode');

                return (bool) $this->bot->query('post', 'sendMessage', params: [
                    'peer' => $this->getReplyTo(),
                    'message' => 'Введите код из служебных сообщений'
                ]);
            },

            AuthStatus::WAITING_CODE => function ($message) {
                $user = UserbotWrapper::getInstance($this->addSessionPhone);
                $user->completePhoneLogin($message);

                if (!$user->waitingForPassword()) {
                    return (bool) $this->bot->query('post', 'sendMessage', params: [
                        'peer' => $this->getReplyTo(),
                        'message' => 'Неверный код. Попробуйте еще раз'
                    ]);
                }

                $this->setState('waitingForPassword');

                return (bool) $this->bot->query('post', 'sendMessage', params: [
                    'peer' => $this->getReplyTo(),
                    'message' => 'Введите пароль двухфакторной аутентификации'
                ]);
            },
            AuthStatus::WAITING_PASSWORD => function ($message) {
                $user = UserbotWrapper::getInstance($this->addSessionPhone);
                $completedPassword = $user->complete2faLogin($message);

                if (!$completedPassword) {
                    return (bool) $this->bot->query('post', 'sendMessage', params: [
                        'peer' => $this->getReplyTo(),
                        'message' => 'Неверный пароль. Попробуйте еще раз'
                    ]);
                }

                if (!$user->authenticated()) {
                    return (bool) $this->bot->query('post', 'sendMessage', params: [
                        'peer' => $this->getReplyTo(),
                        'message' => 'Не удалось авторизоваться. Попробуйте еще раз'
                    ]);
                }

                $this->setState('idle');
                $this->addSessionPhone = null;

                return (bool) $this->bot->query('post', 'sendMessage', params: [
                    'peer' => $this->getReplyTo(),
                    'message' => 'Сессия успешно добавлена'
                ]);
            },
            default => fn ($message) => $this->cancel(),
        };

        return $callback($message);
    }
}
