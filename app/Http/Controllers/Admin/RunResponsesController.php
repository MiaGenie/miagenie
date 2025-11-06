<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RuleSubType;
use App\Enums\RunResponseStatus;
use App\Enums\RunStatus;
use App\Http\Requests\Admin\DeleteRunResponse;
use App\Http\Resources\Admin\RunResource;
use App\Http\Resources\Admin\RunResponseResource;
use App\Models\RuleStep;
use App\Models\Run;
use App\Models\RunResponse;
use App\Models\Version;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Models\Workspace;

class RunResponsesController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|Response
    {

        $run = Run::firstOrFailByUuid($request->route('run'));

        $records = RunResponse::query()
            ->where('run_id', $run->id)
            ->oldest()
            ->paginate(100)
            ->onEachSide(1);

        return Inertia::render('Genie/Admin/Runs/RunResponses/Index', [
            'versionName' => $run->rule->version->name,
            'workspaceName' => $run->workspace->name,
            'ruleName' => $run->rule->name,
            'ruleType' => $run->rule->rule_type->name,
            'ruleSteps' => $run->rule->ruleSteps,
            'ruleSubTypes' => RuleSubType::withTitle(),
            'run' => new RunResource($run),
            'runResponseStatus' => RunStatus::withTitle(),
            'records' => RunResponseResource::collection($records),
        ]);
    }

    public function view(Request $request): Response
    {
        $runResponse = RunResponse::findByUuid($request->route('run_response'));

        $lastRunResponse = RunResponse::where('run_id', $runResponse->run_id)->latest()->first();

        $isLast = $lastRunResponse->id === $runResponse->id;

        $ruleStep = RuleStep::where('rule_id', '=', $runResponse->step->rule->id)
            ->where('id', '=', $runResponse->step_id)
            ->firstOrFail();

        return Inertia::render('Genie/Admin/Runs/RunResponses/View', [
            'versionName' => Version::find($runResponse->step->rule->version_id)->name,
            'workspaceName' => Workspace::find($runResponse->run->workspace_id)->name,
            'ruleName' => $runResponse->step->rule->name,
            'ruleType' => $runResponse->step->rule->rule_type->name,
            'ruleStep' => $ruleStep,
            'ruleSubTypes' => RuleSubType::withTitle(),
            'runResponseProviderStatus' => RunResponseStatus::withTitle(),
            'runResponseStatus' => RunStatus::withTitle(),
            'runResponse' => new RunResponseResource($runResponse),
            'isLast' => $isLast,
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function destroy(DeleteRunResponse $deleteRunResponse)
    {
        $deleteRunResponse->handle();

        return redirect()->route(
            'genie.admin.runs.run_responses.index',
            ['run' => $deleteRunResponse->route('run')]
        )->with('success', __('genie.run_response_deleted'));
    }
}
