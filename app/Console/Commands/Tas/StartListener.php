<?php

namespace App\Console\Commands\Tas;

use Amp\Delayed;
use Amp\Loop;
use Amp\Websocket\Client\Rfc6455Connection;
use Amp\Websocket\Message;
use App\Jobs\Tas\ProccessUpdate;
use Illuminate\Console\Command;
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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting listener');
        $websocket_url = 'ws://' . config('tas.host') . ':' . config('tas.port') . '/events';
        try {
            Loop::run(function () use ($websocket_url) {
                $this->info("Connecting to: {$websocket_url}");
                while (true) {
                    try {
                        /* Устанавливаем соединение */
                        /** @var Rfc6455Connection $connection */
                        $connection = yield connect($websocket_url);

                        $pingLoop = Loop::repeat(3_000, fn () => yield $connection->send('ping'));

                        /* Хук при обрыве соединения */
                        $connection->onClose(static function () use ($connection, $pingLoop) {
                            Loop::cancel($pingLoop);
                            $this->error("Closed - {$connection->getCloseReason()}");
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
            return false;
        }
        $this->info("Received: {$payload}");

        $update = json_decode($payload, true)['result']['update'];

        ProccessUpdate::dispatch($update);
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
        if (!isset($body['result'])) return true;
        /* нет тела update */
        if (!isset($body['result']['update'])) return true;
        /* update не содержит тело message */
        if (!isset($body['result']['update']['message'])) return true;
        /* пустое сообщение */
        if (empty($body['result']['update']['message']['message'])) return true;
        /* обновление получил бот */
        if (in_array($body['result']['session'], ['manager', 'notifier'])) return true;
        /* событие не updateNewMessage */
        if ($body['result']['update']['_'] !== 'updateNewMessage') return true;
        /* не обычное сообщение */
        if ($body['result']['update']['message']['_'] !== 'message') return true;
        /* исходящее сообщение */
        if ($body['result']['update']['message']['out'] === true) return true;
        /* сообщение не из группы */
        if ($body['result']['update']['message']['from_id']['_'] !== 'peerUser' ||
            $body['result']['update']['message']['peer_id']['_'] !== 'peerChat') return true;
        /* старое сообщение */
        if (round(time() - $body['result']['update']['message']['date']) > 3) return true;

        return false;
    }
}
