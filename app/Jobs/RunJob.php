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
     * @param GenieSyncAction $action
     * @param Run $run
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
        $nextStep = $data->nextStep();

        if ($nextStep) {
            $runResponse = $this->run->runResponses()->create([
                'step_id' => $nextStep->id,
                'status' => GenieSyncStatus::CREATING
            ]);
            if ($nextStep?->rule_sub_type === RuleSubType::COMPETITORS) {
                $runResponse->runCompetitor()->create([
                    'competitor_id' => $data->getNextCompetitor()->id,
                ]);
            }
            RunResponseJob::dispatch($runResponse, GenieSyncAction::CREATE);
        } else {

        }

    }

    /**
     * @param Throwable|null $exception
     * @return void
     */
    public function failed(?Throwable $exception): void
    {

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
                'action' => $this->action,
            ]
        );
    }

    /**
     * @return GenieOutputContract|mixed|object
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getGenieOutput(GenieData $data): mixed
    {
        return App::make(
            GenieOutputContract::class,
            [
                'data' => $data,
                'type' => $data->getType()
            ]
        );
    }
}
