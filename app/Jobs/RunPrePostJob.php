<?php

namespace App\Jobs;

use App\Abstracts\GenieJob;
use App\Concerns\GenieLogger;
use App\Contracts\GenieRunDataContract;
use App\Enums\GenieSyncAction;
use App\Models\Rule;
use App\Models\Run;
use App\Models\RunDraftResponse;
use Bus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunPrePostJob extends GenieJob implements ShouldQueue
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
    public int $timeout = 30;

    /**
     * @var Rule
     */
    private Rule $rule;

    /**
     * @var Run
     */
    private Run $run;

    /**
     * @var GenieSyncAction
     */
    protected GenieSyncAction $action;

    /**
     * @param Run $run
     * @param GenieSyncAction $action
    */
    public function __construct(Run $run, GenieSyncAction $action)
    {
        parent::__construct($run, $action);
        $this->action = $action;
        $this->run = $run;
    }

    /**
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle(): void
    {
        $data = $this->getGenieRunData();
        $genieState = $this->getGenieStateRuns();

        if ($this->run->rule->link_upstream) {
            $runJobs = [];
            foreach ($this->run->runDrafts as $runDraft) {
                $runResponseJobs = [];
                foreach ($this->run->rule->ruleSteps as $ruleStep) {
                    $runResponse = $this->run->runResponses()->create([
                        'step_id' => $ruleStep->id
                    ]);
                    $runDraftResponse = $runDraft->runDraftResponses()->create([
                        'run_response_id' => $runResponse->id,
                    ]);
                    $runResponseJobs[] = new RunResponseJob($runResponse, GenieSyncAction::CREATE);
                }
                $runJobs[] = $runResponseJobs;
            }

            Bus::batch($runJobs)
                ->finally(function () use ($genieState, $data) {$genieState->handle($data, 'end');})
                ->allowFailures()
                ->dispatch();

            $genieState->handle($data, 'run');
            return;
        }

        $nextStep = $data->nextStep();

        if (!$nextStep) {
            $genieState->handle($data, 'end');
            return;
        }

        $runResponse = $this->run->runResponses()->create([
            'step_id' => $nextStep->id
        ]);

        if ($nextStep->rule_sub_type->name !== 'PRE_POSTS_INITIAL') {
            RunDraftResponse::create([
                'run_draft_id' => $data->getNextIteratorId(),
                'run_response_id' => $runResponse->id
            ]);
        }

        RunResponseJob::dispatch($runResponse, GenieSyncAction::CREATE);
        $genieState->handle($data, 'run');
    }


    /**
     * @param Throwable|null $exception
     * @return void
     */
    public function failed(?Throwable $exception): void
    {
        $data = $this->getGenieRunData();
        $genieState = $this->getGenieStateRuns();
        $genieState->handle($data, 'fail');
        Log::error($exception->getMessage());
    }

    /**
     * @return GenieRunDataContract
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getGenieRunData(): GenieRunDataContract
    {
        return App::make(
            GenieRunDataContract::class,
            [
                'model' => $this->run,
                'action' => $this->action,
                'type' => $this->run->rule->rule_type
            ]
        );
    }
}
