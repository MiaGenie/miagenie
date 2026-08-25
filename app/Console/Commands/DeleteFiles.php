<?php

namespace App\Console\Commands;

use App\Jobs\Utils\FileProviderJob as FileJob;
use App\Models\File;
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
    protected $description = 'Delete this application\'s files from the AI provider';

    /**
     * Queue a delete for every file this application has synced.
     *
     * This previously listed every file at the provider and deleted all of them, including
     * anything created outside this application. The SDK exposes no list endpoint, and
     * scoping the sweep to our own records is the safer behaviour regardless.
     */
    public function handle(): void
    {
        $files = File::whereNotNull('file_provider_id')->pluck('file_provider_id');

        foreach ($files as $providerId) {
            FileJob::dispatch($providerId, 'delete');
        }

        $this->info("Queued {$files->count()} file(s) for deletion.");
    }
}
