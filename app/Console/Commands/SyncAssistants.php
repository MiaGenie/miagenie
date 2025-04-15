<?php

namespace App\Console\Commands;

use App\Jobs\AssistantJob;
use App\Models\Assistant;
use Illuminate\Console\Command;


class SyncAssistants extends Command
{
    /**
     * @var string
     */
    protected $signature = 'genie:sync-assistants';

    /**
     * @var string
     */
    protected $description = 'Sync Assistants';

    /**
     * @return void
     */
    public function handle(): void
    {
        Assistant::all()->whereNull('assistant_provider_id')->each(
            function (Assistant $assistant) {
                AssistantJob::dispatch($assistant, 'upload');
            }
        );

        $this->info('All assistants without sync have been added to Assistant Sync Job');
    }

}
