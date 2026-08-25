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
    use Batchable;
    use Dispatchable;
    use GenieLogger;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * The model call is the long pole here.
     *
     * This, the per-call SDK timeout and the Horizon supervisor timeout all cap each other —
     * the smallest wins — so all three were raised together. At the old 60s a reasoning model
     * could not finish.
     */
    public int $timeout = 600;

    /**
     * The queue model calls run on.
     *
     * Set in the constructor rather than as a property, because Queueable already declares
     * $queue and redeclaring it is a trait composition conflict.
     */
    public const QUEUE = 'genie-ai';

    /**
     * Wait progressively longer between retries.
     *
     * Previously three retries fired 5s apart, exhausting them inside ~15 seconds — far too
     * fast to ride out a rate limit.
     *
     * @var int[]
     */
    public array $backoff = [15, 60, 180];

    private Rule $rule;

    private RunResponse $runResponse;

    protected GenieSyncAction $action;

    public function __construct(RunResponse $runResponse, GenieSyncAction $action)
    {
        parent::__construct($runResponse, $action);
        $this->runResponse = $runResponse;
        $this->action = $action;
        $this->onQueue(self::QUEUE);
    }

    /**
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        $data = $this->getGenieData();

        $genieState = $this->getGenieState($data);
        $genieState->handle($data, 'run');

        if ($data->getRuleType() === RuleType::STRATEGY) {
            $strategyState = $this->getGenieStateStrategy();
            $strategyState->handle($data->getModel()->run->strategy, 'run');
        }

        $action = $this->getGenieAction();

        $data = $action->handle($data);

        if ($data->getError()) {
            $this->logRun(GenieType::RUN_RESPONSE, $this->action, $data);
            $this->release($this->backoff[$this->attempts() - 1] ?? 180);

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
        } else {
            if ($data->getRuleType() === RuleType::STRATEGY) {
                $strategyState = $this->getGenieStateStrategy();
                $strategyState->handle($data->getModel()->run->strategy, 'end', $data->getModel()->step->requires_review);
            }
        }
    }

    /**
     * @throws BindingResolutionException
     */
    public function failed(?Throwable $exception): void
    {
        $data = $this->getGenieData();
        $genieState = $this->getGenieState($data);
        $genieState->handle($data, 'fail');

        if ($data->getRuleType() === RuleType::STRATEGY) {
            $strategyState = $this->getGenieStateStrategy();
            $strategyState->handle($data, 'fail');
        }

        Log::error($exception->getMessage());
    }
}
