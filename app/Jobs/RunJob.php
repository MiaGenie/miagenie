<?php

namespace App\Jobs;

use App\Abstracts\GenieData;
use App\Abstracts\GenieJob;
use App\Concerns\GenieLogger;
use App\Contracts\GenieOutputContract;
use App\Enums\GenieSyncAction;
use App\Enums\GenieSyncStatus;
use App\Enums\RuleSubType;
use App\Enums\RunResponseStatus;
use App\Enums\RunStatus;
use App\Genie\Data\GenieRunData;
use App\Models\Rule;
use App\Models\Run;
use App\Models\RunResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunJob extends GenieJob implements ShouldQueue
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
        $nextStep = $data->nextStep();

        if ($nextStep) {

            if ($this->action === GenieSyncAction::CREATE) {
                $runResponse = $this->run->runResponses()->create([
                    'step_id' => $nextStep->id
                ]);
            } else {
                $runResponse =  $this->run->runResponses()->where('status', '!=', RunStatus::COMPLETE)->firstOrCreate(
                    [
                        'run_id' => $this->run->id,
                        'step_id' => $nextStep->id,
                    ]
                );
            }
            if ($nextStep?->rule_sub_type === RuleSubType::COMPETITORS) {
                $runResponse->runCompetitor()->create([
                    'competitor_id' => $data->getNextCompetitor()->id,
                ]);
            }

            $genieState->handle($data, 'run');
            RunResponseJob::dispatch($runResponse, GenieSyncAction::CREATE);
        } else {
            $genieState->handle($data, 'end');
        }
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
     * @return GenieRunData
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getGenieRunData(): GenieRunData
    {
        return App::make(
            GenieRunData::class,
            [
                'model' => $this->run,
                'action' => $this->action
            ]
        );
    }
}
