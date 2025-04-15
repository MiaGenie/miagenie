<?php

namespace App\Console\Commands;

use App\Jobs\VectorJob;
use App\Models\Vector;
use Illuminate\Console\Command;


class SyncVectors extends Command
{
    /**
     * @var string
     */
    protected $signature = 'genie:sync-vectors';

    /**
     * @var string
     */
    protected $description = 'Sync Vectors';

    /**
     * @return void
     */
    public function handle(): void
    {
        Vector::all()->whereNull('vector_provider_id')->each(
            function (Vector $vector) {
                VectorJob::dispatch($vector, 'upload');
            }
        );

        $this->info('All vectors without sync have been added to Vector Sync Job');
    }

}
