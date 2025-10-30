<?php

namespace App\Jobs;

use App\Abstracts\GenieJob;
use App\Concerns\GenieLogger;
use App\Contracts\GenieRunDataContract;
use App\Enums\GenieSyncAction;
use App\Enums\RuleSubType;
use App\Enums\RunStatus;
use App\Genie\Data\GenieRunData;
use App\Models\Rule;
use App\Models\Run;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunIdeaJob extends GenieJob implements ShouldQueue
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
                if ($nextStep?->rule_sub_type === RuleSubType::IDEAS_MULTIPLE) {
                    $runResponse->runFieldIterator()->create([
                        'field_id' => $nextStep->depends_on_field,
                        'field_index' => $data->getNextIteratorId(),
                    ]);
                }
            } else {
                $runResponse =  $this->run->runResponses()->where('status', '!=', RunStatus::COMPLETE)->firstOrCreate(
                    [
                        'step_id' => $nextStep->id
                    ]
                );
                if ($nextStep?->rule_sub_type === RuleSubType::IDEAS_MULTIPLE) {
                    $runResponse->runFieldIterator()->firstOrCreate([
                        'field_id' => $nextStep->depends_on_field,
                        'field_index' => $data->getNextIteratorId(),
                    ]);
                }
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
