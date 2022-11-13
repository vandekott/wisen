<?php

namespace App\Jobs\Tas;

use App\Services\Tas\Bots\ManagerBot;
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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->minScore = (float) Valuestore::make(config('filament-settings.path'))->get('min_score');
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $scoring = Filter::run($this->payload['message']['message']);

        if (false === $scoring || !($scoring >= $this->minScore)) {
            Log::info("Сообщение не прошло фильтрацию по алгоритму с рейтингом {$scoring}");
            $this->delete();
            return;
        }

        ManagerBot::getInstance()->query('post', 'sendMessage', params: [
            'peer' => '@vandekott',
            'message' => $this->payload['message']['message'] ?? "Не удалось прочесть"
        ]);

    }
}