<?php

namespace App\Console\Commands;

use App\Enums\GenieSyncAction;
use App\Jobs\RunJob;
use App\Models\Run;
use Illuminate\Console\Command;

class RunStep extends Command
{
    /**
     * @var string
     */
    protected $signature = 'genie:step {--run=}';

    /**
     * @var string
     */
    protected $description = 'Run Strategy Analysis for given workspace ID';

    public function handle(): void
    {
        if (! $this->option('run')) {
            $this->info('run ID is required to run strategy analysis');

            return;
        }

        $run = Run::find($this->option('run'));

        RunJob::dispatch($run, GenieSyncAction::CREATE);

        $this->info('Run dispatched.');
    }
}
