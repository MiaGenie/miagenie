<?php

namespace App\Jobs;

use App\Abstracts\GenieData;
use App\Abstracts\GenieJob;
use App\Concerns\GenieLogger;
use App\Contracts\GenieOutputContract;
use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Models\Rule;
use App\Models\RunResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunResponseJob extends GenieJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use GenieLogger;

    /**
     * @var int
     */
    public int $tries = 3;

    /**
     * @var int
     */
    public int $timeout = 60;

    /**
     * @var Rule
     */
    private Rule $rule;

    /**
     * @var RunResponse
     */
    private RunResponse $runResponse;

    /**
     * @var GenieSyncAction
     */
    protected GenieSyncAction $action;

    /**
     * @param GenieSyncAction $action
     * @param RunResponse $runResponse
     */
    public function __construct(RunResponse $runResponse, GenieSyncAction $action)
    {
        parent::__construct($runResponse, $action);
        $this->runResponse = $runResponse;
        $this->action = $action;
    }

    /**
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle(): void
    {
        $data = $this->getGenieData();
        $action = $this->getGenieAction();

        $data = $action->handle($data);

        $this->logRun(GenieType::RUN_RESPONSE, $this->action, $data);

        if ($data->getError()) {
            $this->release(5);
            return;
        }

        $genieOutput = $this->getGenieOutput($data);
        $genieOutput->handle($data);

        $genieState = $this->getGenieState($data);
        $genieState->handle($data, 'end');

        $nextAction = $data->nextAction();
        if ($nextAction) {
            RunJob::dispatch($this->runResponse->run, $nextAction);
        }

    }

    /**
     * @param Throwable|null $exception
     * @return void
     * @throws BindingResolutionException
     */
    public function failed(?Throwable $exception): void
    {
        Log::error($exception->getMessage());
        $data = $this->getGenieData();
        $genieState = $this->getGenieState($data);
        $genieState->handle($data, 'fail');

    }
}
