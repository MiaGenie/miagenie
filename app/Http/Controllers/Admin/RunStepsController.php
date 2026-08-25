<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Modality;
use App\Enums\RuleSubType;
use App\Enums\RunStatus;
use App\Http\Requests\Admin\DeleteRunStep;
use App\Http\Resources\Admin\AiRunResource;
use App\Http\Resources\Admin\AiRunStepResource;
use App\Models\AiRun;
use App\Models\AiRunStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class RunStepsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|Response
    {
        $run = $this->run($request->route('run'));

        $records = $run->steps()
            ->paginate(100)
            ->onEachSide(1);

        return Inertia::render('Genie/Admin/Runs/Steps/Index', [
            'versionName' => $run->rule?->version?->name,
            'workspaceName' => $run->workspace->name,
            'ruleName' => $run->rule?->name,
            'ruleType' => $run->rule?->rule_type->name,
            'ruleSteps' => $run->rule?->steps ?? [],
            'ruleSubTypes' => RuleSubType::withTitle(),
            'run' => new AiRunResource($run),
            'runStatus' => RunStatus::withTitle(),
            'modalities' => Modality::withTitle(),
            'records' => AiRunStepResource::collection($records),
        ]);
    }

    /**
     * One turn of the run, with the conversation it produced.
     *
     * The rule step is read through the relation rather than looked up: `step_id` is nulled when a
     * rule step is deleted, and a run that outlived its rule still has to be readable.
     */
    public function view(Request $request): Response
    {
        $runStep = AiRunStep::firstOrFailByUuid($request->route('step'));

        // Reached by id rather than through the relation, which the workspace scope would filter.
        $run = $this->run($runStep->run_id);

        $last = $run->steps()->reorder()->latest('position')->first();

        return Inertia::render('Genie/Admin/Runs/Steps/View', [
            'versionName' => $run->rule?->version?->name,
            'workspaceName' => $run->workspace->name,
            'ruleName' => $run->rule?->name,
            'ruleType' => $run->rule?->rule_type->name,
            'ruleStep' => $runStep->step,
            'ruleSubTypes' => RuleSubType::withTitle(),
            'runStatus' => RunStatus::withTitle(),
            'modalities' => Modality::withTitle(),
            'runStep' => new AiRunStepResource($runStep),
            'conversation' => $this->conversation($runStep),
            'isLast' => $last?->id === $runStep->id,
        ]);
    }

    /**
     * The run, whichever workspace it belongs to.
     *
     * AiRun is workspace scoped for the customer-facing pages; an admin screen has to see past
     * that, or a run from any other workspace reads as missing.
     */
    private function run(int|string $key): AiRun
    {
        $query = AiRun::withoutWorkspace();

        return is_int($key)
            ? $query->findOrFail($key)
            : $query->where('uuid', $key)->firstOrFail();
    }

    /**
     * The turn as it was spoken: what was asked, and what came back.
     *
     * @return array{prompt: string|null, answer: string|null, usage: array<string, mixed>|null}
     */
    private function conversation(AiRunStep $runStep): array
    {
        $answer = $runStep->message();

        return [
            'prompt' => $runStep->promptMessage()?->content,
            'answer' => $answer?->content,
            'usage' => $answer?->usage ? json_decode($answer->usage, true) : null,
        ];
    }

    public function destroy(DeleteRunStep $deleteRunStep): RedirectResponse
    {
        $deleteRunStep->handle();

        return redirect()->route(
            'genie.admin.runs.steps.index',
            ['run' => $deleteRunStep->route('run')]
        )->with('success', __('genie.run_step_deleted'));
    }
}
