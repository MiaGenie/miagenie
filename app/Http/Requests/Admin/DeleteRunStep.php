<?php

namespace App\Http\Requests\Admin;

use App\Enums\RunStatus;
use App\Models\AiRun;
use App\Models\AiRunStep;
use Illuminate\Foundation\Http\FormRequest;

class DeleteRunStep extends FormRequest
{
    /**
     * Drop a turn so the run can take it again.
     *
     * The run is reopened along with it: StrategyRunner::nextStep() settles a step by the row it
     * left behind, so without the row the step is next in line again, and a run left in ERROR or
     * PENDING_REVIEW would refuse to advance to it.
     */
    public function handle(): void
    {
        $runStep = AiRunStep::firstOrFailByUuid($this->route('step'));

        // The run is reached by id: its workspace scope would hide another workspace's run here.
        $run = AiRun::withoutWorkspace()->findOrFail($runStep->run_id);

        $runStep->delete();

        $run->update(['status' => RunStatus::OPEN]);
    }
}
