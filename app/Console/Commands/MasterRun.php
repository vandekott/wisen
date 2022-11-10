<?php

namespace App\Console\Commands;

use App\Services\MadelineService\Master;
use Illuminate\Console\Command;

class MasterRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'master:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run master process';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        (new Master())->run();
        //return Command::SUCCESS;
    }
}
