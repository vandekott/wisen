<?php

namespace App\Jobs\Tas;

use App\Services\Tas\Bots\ManagerBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ManagerBotUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $payload)
    {
        $this->payload = $payload;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $query = ManagerBot::getInstance()->query('post', 'sendMessage', params: [
            'peer' => '@vandekott',
            'message' => json_decode($this->payload, true)['result']['update']['message']['message'] ?? "Не удалось прочесть"
        ]);

        Log::info(sprintf("Send message result: %s", collect($query)->toJson()));
    }
}
