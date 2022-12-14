<?php

namespace App\Console\Commands\Tas;

use Amp\Delayed;
use Amp\Loop;
use Amp\Websocket\Client\Rfc6455Connection;
use Amp\Websocket\Message;
use App\Jobs\Telegram\TelegramUpdateJob;
use App\Services\TelegramService\System;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;
use function Amp\Websocket\Client\connect;

class StartListener extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'listener:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to TAS events';
    private $timestart;
    private $skipReason = '';

    public function __construct()
    {
        parent::__construct();
        $this->timestart = Carbon::now();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting listener');
        $websocket_url = 'ws://0.0.0.0:' . config('tas.port') . '/events';
        try {
            Loop::run(function () use ($websocket_url) {
                $this->info("Connecting to: {$websocket_url}");
                while (true) {
                    if (Carbon::now()->diffInMinutes($this->timestart) > 60)
                        echo "Listener restart after 1 hour" && exit(Command::SUCCESS);

                    try {
                        /* Устанавливаем соединение */
                        /** @var Rfc6455Connection $connection */
                        $connection = yield connect($websocket_url);

                        $pingLoop = Loop::repeat(3_000, fn () => yield $connection->send('ping'));

                        /* Хук при обрыве соединения */
                        $connection->onClose(static function () use ($connection, $pingLoop) {
                            Loop::cancel($pingLoop);
                            $this->error("Closed - {$connection->getCloseReason()}");
                            exit(Command::FAILURE);
                        });

                        /* Цикл получения обновлений */
                        while ($message = yield $connection->receive()) {
                            /** @var Message $message */
                            $payload = yield $message->buffer();

                            $this->dispatch($payload);

                        }
                    } catch (Throwable $e) {
                        $this->error("Error: {$e->getMessage()}");
                    }
                    yield new Delayed(500);
                }
            });
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    public function dispatch($payload)
    {
        if ($this->skip($payload)) {
            $this->alert("Skip: {$payload}");
            $this->alert("Reason: {$this->skipReason}");
            return false;
        }

        $this->info("Received: {$payload}");

        $update = json_decode($payload, true)['result']['update'];

        TelegramUpdateJob::dispatch($update, json_decode($payload, true)['result']['session']);
    }

    /**
     * Метод для пропуска ненужных событий
     * @param $payload
     * @return bool
     */
    private function skip($payload)
    {
        $body = json_decode($payload, true);

        /* нет result */
        $this->skipReason = 'no result';
        if (!isset($body['result'])) return true;
        /* нет тела update */
        $this->skipReason = 'no update';
        if (!isset($body['result']['update'])) return true;
        /* update не содержит тело message */
        $this->skipReason = 'no message';
        if (!isset($body['result']['update']['message'])) return true;
        /* пустое сообщение */
        $this->skipReason = 'empty message';
        if (empty($body['result']['update']['message']['message'])) return true;
        /* обновление получил бот */
        $this->skipReason = 'bot update';
        if (in_array($body['result']['session'], ['manager', 'notifier'])) return true;
        /* событие не updateNewMessage */
        $this->skipReason = 'not updateNewMessage/updateNewChannelMessage';
        if (!in_array($body['result']['update']['_'], ['updateNewMessage', 'updateNewChannelMessage'])) return true;
        /* не обычное сообщение */
        $this->skipReason = 'not message';
        if ($body['result']['update']['message']['_'] !== 'message') return true;
        /* исходящее сообщение */
        $this->skipReason = 'outgoing message';
        if ($body['result']['update']['message']['out'] === true) return true;
        /* сообщение не из группы */
        $this->skipReason = 'not from group';
        if ($body['result']['update']['message']['from_id']['_'] !== 'peerUser' ||
            !in_array($body['result']['update']['message']['peer_id']['_'], ['peerChat', 'peerChannel'])) return true;
        /* старое сообщение */
        $this->skipReason = 'old message';
        if (round(time() - $body['result']['update']['message']['date']) > 3) return true;

        return false;
    }
}
