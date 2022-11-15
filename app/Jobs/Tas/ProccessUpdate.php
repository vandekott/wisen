<?php

namespace App\Jobs\Tas;

use App\Models\Tas\Userbot;
use App\Services\Tas\Bots\NotifierBot;
use App\Services\Word\Filter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\Valuestore\Valuestore;

class ProccessUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $payload;
    private float $minScore;
    private array $chatId;
    private ?Userbot $userbot;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $payload, string $session)
    {
        $this->payload = $payload;
        $this->minScore = (float) Valuestore::make(config('filament-settings.path'))->get('min_score');
        $this->chatId = config('tas.bots.notifier.peer');
        $this->userbot = Userbot::where('phone', $session)->firstOrCreate(['phone' => $session]);
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $scoring = resolve(Filter::class)->run($this->payload['message']['message']);

        if (false === $scoring || $scoring['score'] <= $this->minScore) {
            Log::info("Сообщение не прошло фильтрацию по алгоритму с рейтингом {$scoring}");
            $this->delete();
            return;
        }

        $groupInfo = $this->userbot->getApi()->getChatInfo($this->payload['message']['peer_id']);

        $userInfo = $this->userbot->getApi()->getInfo($this->payload['message']['from_id'])['User'];

        $keyboard = [
            [
                [
                    'text' => 'Подробнее',
                    'url' => "https://t.me/c/{$this->payload['message']['peer_id'][
                            strtolower(str_replace('peer', '',$this->payload['message']['peer_id']['_'])) . '_id'
                        ]}/{$this->payload['message']['id']}",
                ],
                [
                    'text' => "Написать {$userInfo['first_name']}",
                    'url' => (!empty($userInfo['username']))
                        ? "https://t.me/{$userInfo['username']}"
                        : "tg://user?id={$userInfo['id']}",
                ],
            ],
        ];


        $report = view('notifier.report', [
            'message' => $this->payload['message']['message'] ?? "Не удалось прочесть",
            'scoring' => $scoring['score'],
            'found' => $scoring['found'],
            'group' => $groupInfo,
            'user' => $userInfo,
        ])->render();

        NotifierBot::getInstance()->query('post', 'sendMessage', params: [ 'data' => [
            'peer' => $this->chatId,
            'message' => $report,
            'parse_mode' => 'html',
            'reply_markup' => [
                'inline_keyboard' => $keyboard,
            ],
        ]]);

    }
}
