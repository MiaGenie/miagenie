<?php

namespace App\Jobs;

use App\Abstracts\GenieJob;
use App\Concerns\GenieLogger;
use App\Contracts\GenieRunDataContract;
use App\Enums\GenieSyncAction;
use App\Models\Rule;
use App\Models\Run;
use App\Models\RunIdeaResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunDraftJob extends GenieJob implements ShouldQueue
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

        if (! $nextStep) {
            $genieState->handle($data, 'end');

            return;
        }

        $runResponse = $this->run->runResponses()->create([
            'step_id' => $nextStep->id,
        ]);

        if ($nextStep->rule_sub_type->name !== 'DRAFTS_INITIAL') {
            RunIdeaResponse::create([
                'run_idea_id' => $data->getNextIteratorId(),
                'run_response_id' => $runResponse->id,
            ]);
        }

        RunResponseJob::dispatch($runResponse, GenieSyncAction::CREATE);
        $genieState->handle($data, 'run');
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
