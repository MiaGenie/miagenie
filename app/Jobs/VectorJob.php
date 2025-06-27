<?php

namespace App\Jobs;

use App\Abstracts\GenieJob;
use App\Enums\GenieSyncAction;
use App\Models\Vector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class VectorJob extends GenieJob implements ShouldQueue
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
     * @var Vector
     */
    protected Vector $vector;

    /**
     * @var GenieSyncAction
     */
    protected GenieSyncAction $action;

    /**
     * @param Vector $vector
     * @param GenieSyncAction $action
     */
    public function __construct(Vector $vector, GenieSyncAction $action)
    {
        parent::__construct($vector, $action);
        $this->vector = $vector;
        $this->action = $action;
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        $data = $this->getGenieData();

        $state = $this->getGenieState();
        $state->handle($this->model, $this->action, 'init');

        $action = $this->getGenieAction();
        $data = $action->handle($data);

        if (!$data) {
            $this->release(30);
            return;
        }

        $state->handle($this->model, $this->action, 'end');

        if ($this->action !== GenieSyncAction::UPDATE) {
            $genieOutput = $this->getGenieOutput($data);
            $data = $genieOutput->handle($data);
        }
    }

    /**
     * @param Throwable|null $exception
     * @return void
     */
    public function failed(?Throwable $exception): void
    {
        $state = $this->getGenieState();
        $state->handle($this->model, $this->action, 'fail');
    }
}
