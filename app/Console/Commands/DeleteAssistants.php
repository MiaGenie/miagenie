<?php

namespace App\Console\Commands;

use App\Jobs\Utils\AssistantProviderJob as AssistantJob;
use App\Support\Facades\OpenAI;
use Illuminate\Console\Command;


class DeleteAssistants extends Command
{
    /**
     * @var string
     */
    protected $signature = 'genie:delete-assistants';

    /**
     * @var string
     */
    protected $description = 'Delete Provider Assistants';

    /**
     * @return void
     */
    public function handle(): void
    {
        $list = OpenAI::assistants()->list();

        foreach ($list->data as $assistant) {
            AssistantJob::dispatch($assistant->id, 'delete');
        }

        $this->info('All assistant have been deleted to Assistant Sync Job');
    }

}
