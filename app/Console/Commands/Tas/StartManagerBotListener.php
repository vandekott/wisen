<?php

namespace App\Console\Commands\Tas;

use Amp\Delayed;
use Amp\Loop;
use Amp\Websocket\Client\Rfc6455Connection;
use Amp\Websocket\Message;
use App\Jobs\Tas\ManagerBotUpdate;
use App\Services\Tas\Bots\ManagerBot;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use function Amp\Websocket\Client\connect;

class StartManagerBotListener extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'managerbot:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Starts manager bot listener';

    private array $processedMessages = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting manager bot listener');
        $managerBot = ManagerBot::getInstance();
        $websocket_url = $managerBot->getListenerUrl();
        try {
            Loop::run(function () use ($websocket_url) {
                $this->info("Connecting to: {$websocket_url}");
                while (true) {
                    try {
                        /* Устанавливаем соединение */
                        /** @var Rfc6455Connection $connection */
                        $connection = yield connect($websocket_url);

                        $pingLoop = Loop::repeat(10_000, fn () => yield $connection->send('ping'));

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
                    } catch (\Throwable $e) {
                        $this->error("Error: {$e->getMessage()}");
                    }
                    yield new Delayed(500);

                }
            });
        } catch (\Throwable $e) {
            $this->info($e->getMessage());
            $this->warn('Restarting manager bot listener');
            return Command::FAILURE;
        }
    }

    public function dispatch($update): bool|string
    {
        echo dd($update);
        /* Мы ждём только строку, в противном случае прерываем вызов */
        if (gettype($update) !== 'string') {
            $this->error('Update is not a string');
            return false;
        }

        $body = json_decode($update, true);

        $this->info(sprintf("Received at %s: \"'%s\"", Carbon::parse($body['result']['update']['message']['date'])->format('H:i:s'), $body['result']['update']['message']['message'] ?? ''));

        if (empty($body['result']['update']['message']['message']) ||
            $body['result']['update']['message']['_'] === 'messageEmpty' ||
            $body['result']['update']['message']['out'] ?? false
        ) {
            $this->error('No need to process this update');
            return false;
        }

        if (round(time() - $body['result']['update']['message']['date']) > 3) {
            $this->error('Old message received (older than 3 seconds)');
            return false;
        }

        /* Прерываем, если пришёл не update */
        if (!isset($body['result']['update'])) {
            $this->error('Update does not contain result.update, it will not be dispatched');
            return false;
        }

        /* Если в теле есть то, что нужно */
        ManagerBotUpdate::dispatch($update)->onQueue('ManagerBot');

        $this->info(sprintf("Dispatched %s which is %s bytes long", $body['result']['update']['_'], strlen($update)));

        return $body['result']['update']['_'];
    }
}
