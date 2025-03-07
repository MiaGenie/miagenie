<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Sleep extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mixpost:sleep {--seconds=1}';

    /**
     * @var string
     */
    protected $description = 'Sleep for seconds. Use this command only in composer.json';

    /**
     * @return void
     */
    public function handle(): void
    {
        sleep($this->option('seconds'));
    }
}
