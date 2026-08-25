<?php

namespace App\Jobs;

use App\Abstracts\GenieJob;
use App\Concerns\GenieLogger;
use App\Contracts\GenieRunDataContract;
use App\Enums\GenieSyncAction;
use App\Enums\RuleType;
use App\Enums\RunStatus;
use App\Models\Rule;
use App\Models\Run;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
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
    use GenieLogger;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    private Rule $rule;

    private Run $run;

    protected GenieSyncAction $action;

    public function __construct(Run $run, GenieSyncAction $action)
    {
        parent::__construct($run, $action);
        $this->action = $action;
        $this->run = $run;
    }

    /**
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        $data = $this->getGenieRunData();
        $genieState = $this->getGenieStateRuns();
        $nextStep = $data->nextStep();

        if ($nextStep) {

            if ($this->action === GenieSyncAction::CREATE) {
                $runResponse = $this->run->runResponses()->create([
                    'step_id' => $nextStep->id,
                ]);
            } else {
                $runResponse = $this->run->runResponses()->where('status', '!=', RunStatus::COMPLETE)->firstOrCreate([
                    'step_id' => $nextStep->id,
                ]);
            }

            $genieState->handle($data, 'run');
            RunResponseJob::dispatch($runResponse, GenieSyncAction::CREATE);
        } else {
            $genieState->handle($data, 'end');
            if ($data->getModel()->rule->rule_type === RuleType::STRATEGY) {
                $strategyState = $this->getGenieStateStrategy();
                $strategyState->handle($data->getModel()->strategy, 'end');
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        $data = $this->getGenieRunData();
        $genieState = $this->getGenieStateRuns();
        $genieState->handle($data, 'fail');
        Log::error($exception->getMessage());
    }

    /**
     * @throws BindingResolutionException
     */
    public function getGenieRunData(): GenieRunDataContract
    {
        return App::make(
            GenieRunDataContract::class,
            [
                'model' => $this->run,
                'action' => $this->action,
                'type' => $this->run->rule->rule_type,
            ]
        );
    }
}
