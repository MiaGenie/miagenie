<?php

namespace App\Jobs;

use App\Abstracts\GenieJob;
use App\Concerns\GenieLogger;
use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Enums\RuleType;
use App\Models\Rule;
use App\Models\RunResponse;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunResponseJob extends GenieJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use Batchable;
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

        $genieState = $this->getGenieState($data);
        $genieState->handle($data, 'run');

        $action = $this->getGenieAction();

        $data = $action->handle($data);

        if ($data->getError()) {
            $this->logRun(GenieType::RUN_RESPONSE, $this->action, $data);
            $this->release(5);
            return;
        }

        $genieOutput = $this->getGenieOutput($data);
        $genieOutput->handle($data);

        $genieState->handle($data, 'end');
        $this->logRun(GenieType::RUN_RESPONSE, $this->action, $data);

        $nextAction = $data->nextAction();
        if ($nextAction) {
            switch ($data->getRuleType()) {
                case RuleType::STRATEGY:
                    RunJob::dispatch($this->runResponse->run, $nextAction);
                    break;
                case RuleType::IDEAS:
                    RunIdeaJob::dispatch($this->runResponse->run, $nextAction);
                    break;
                case RuleType::DRAFTS:
                    RunDraftJob::dispatch($this->runResponse->run, $nextAction);
                    break;
                case RuleType::PRE_POSTS:
                    RunPrePostJob::dispatch($this->runResponse->run, $nextAction);
                    break;
            }
        }
    }

    /**
     * @param Throwable|null $exception
     * @return void
     * @throws BindingResolutionException
     */
    public function failed(?Throwable $exception): void
    {
        $data = $this->getGenieData();
        $genieState = $this->getGenieState($data);
        $genieState->handle($data, 'fail');
        Log::error($exception->getMessage());
    }
}
