<?php

namespace App\Console\Commands;

use App\Jobs\Utils\FileProviderJob as FileJob;
use App\Support\Facades\OpenAI;
use Illuminate\Console\Command;


class DeleteFiles extends Command
{
    /**
     * @var string
     */
    protected $signature = 'genie:delete-files';

    /**
     * @var string
     */
    protected $description = 'Delete Provider Files';

    /**
     * @return void
     */
    public function handle(): void
    {
        $list = OpenAI::files()->list();

        foreach ($list->data as $file) {
            FileJob::dispatch($file->id, 'delete');
        }

        $this->info('All files have been deleted to File Sync Job');
    }

}
