<?php

namespace App\Console;

use App\Models\Tas\Userbot;
use App\Services\Tas\Bots\NotifierBot;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /* Проверяем неавторизованные сессии */
        $schedule->call(function () {
            $userbots = Userbot::all();
            $needAdmin = [];

            foreach ($userbots as $userbot) {
                if (!$userbot->getApi()->authenticated()) {
                    $needAdmin[] = $userbot;
                }
            }

            $report = view('notifier.bots_need_admin', ['userbots' => $needAdmin])->render();

            if (count($needAdmin) > 0) {
                NotifierBot::getInstance()->query('post', 'sendMessage', params: [ 'data' => [
                    'peer' => config('tas.bots.notifier.peer'),
                    'message' => $report,
                    'parse_mode' => 'html',
                ]]);
            }
        })->dailyAt('12:00');

        $schedule->command('log:clear --keep-last')->everyThreeHours();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
