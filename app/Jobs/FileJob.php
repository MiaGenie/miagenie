<?php

namespace App\Jobs;

use App\Abstracts\GenieJob;
use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class FileJob extends GenieJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var int
     */
    public int $tries = 3;

    /**
     * @var int
     */
    public int $timeout = 30;

    /**
     * @var File
     */
    protected File $file;

    /**
     * @var GenieSyncAction
     */
    protected GenieSyncAction $action;

    /**
     * @param File $file
     * @param GenieSyncAction $action
     */
    public function __construct(File $file, GenieSyncAction $action)
    {
        parent::__construct($file, $action);
        $this->model = $file;
        $this->action = $action;
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        $data = $this->getGenieData();

        if (!($this->action === GenieSyncAction::DELETE && !$data->getModelProviderId())) {
            $state = $this->getGenieState();
            $state->handle($this->model, $this->action, 'init');

            $action = $this->getGenieAction();
            $data = $action->handle($data);

            $this->logRun(GenieType::FILE, $this->action, $data);

            if (!$data) {
                $this->release(30);
                return;
            }

            $state->handle($this->model, $this->action, 'end');
        }

        if ($this->action !== GenieSyncAction::UPDATE) {
            $genieOutput = $this->getGenieOutput($data);
            $data = $genieOutput->handle($data);
        }
    }

    /**
     * @param Throwable|null $exception
     * @return void
     * @throws BindingResolutionException
     */
    public function failed(?Throwable $exception): void
    {
        $state = $this->getGenieState();
        $state->handle($this->model, $this->action, 'fail');
    }

}
