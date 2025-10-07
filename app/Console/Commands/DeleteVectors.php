<?php

namespace App\Console\Commands;

use App\Jobs\Utils\VectorProviderJob as VectorJob;
use App\Support\Facades\OpenAI;
use Illuminate\Console\Command;


class DeleteVectors extends Command
{
    /**
     * @var string
     */
    protected $signature = 'genie:delete-vectors';

    /**
     * @var string
     */
    protected $description = 'Delete Provider Vectors';

    /**
     * @return void
     */
    public function handle(): void
    {
        $list = OpenAI::vectorStores()->list();

        foreach ($list->data as $vector) {
            VectorJob::dispatch($vector->id, 'delete');
        }

        $this->info('All vectors have been deleted to Vector Sync Job');
    }

}
