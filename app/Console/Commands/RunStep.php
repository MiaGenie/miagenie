<?php

namespace App\Console\Commands;

use App\Jobs\ThreadJob;
use App\Models\Rule;
use App\Models\Thread;
use App\Models\WorkspaceVersion;
use Illuminate\Console\Command;
use Inovector\Mixpost\Models\Workspace;


class RunStep extends Command
{
    /**
     * @var string
     */
    protected $signature = 'genie:step {--thread=}';

    /**
     * @var string
     */
    protected $description = 'Run Strategy Analysis for given workspace ID';

    /**
     * @return void
     */
    public function handle(): void
    {
        if (!$this->option('thread')) {
            $this->info('thread ID is required to run strategy analysis');
            return;
        }

        $thread = Thread::find($this->option('thread'));

        ThreadJob::dispatch($thread, 'status');

        $this->info('All assistants without sync have been added to Assistant Sync Job');
    }

}
