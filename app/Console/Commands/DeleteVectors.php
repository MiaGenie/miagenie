<?php

namespace App\Console\Commands;

use App\Jobs\Utils\VectorProviderJob as VectorJob;
use App\Models\Vector;
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
    protected $description = 'Delete this application\'s vector stores from the AI provider';

    /**
     * Queue a delete for every vector store this application has synced.
     *
     * See DeleteFiles: this used to sweep every store at the provider, including any created
     * outside this application.
     */
    public function handle(): void
    {
        $vectors = Vector::whereNotNull('vector_provider_id')->pluck('vector_provider_id');

        foreach ($vectors as $providerId) {
            VectorJob::dispatch($providerId, 'delete');
        }

        $this->info("Queued {$vectors->count()} vector store(s) for deletion.");
    }
}
