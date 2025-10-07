<?php

namespace App\Console\Commands;

use App\Jobs\FileJob;
use App\Models\File;
use Illuminate\Console\Command;


class SyncFiles extends Command
{
    /**
     * @var string
     */
    protected $signature = 'genie:sync-files';

    /**
     * @var string
     */
    protected $description = 'Sync Files';

    /**
     * @return void
     */
    public function handle(): void
    {
        File::all()->whereNull('file_provider_id')->each(
            function (File $file) {
                FileJob::dispatch($file, 'upload');
            }
        );

        $this->info('All files without sync have been added to File Sync Job');
    }

}
