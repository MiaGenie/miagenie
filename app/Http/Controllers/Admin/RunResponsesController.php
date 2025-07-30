<?php

namespace App\Http\Controllers\Admin;

use App\Builders\RunResponsesQuery;
use App\Enums\RunResponseStatus;
use App\Enums\RuleSubType;
use App\Http\Resources\Admin\RunResponseResource;
use App\Models\RuleStep;
use App\Models\RunResponse;
use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\MixpostEnterprise\Models\Workspace;

class RunResponsesController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|Response
    {
        $runResponsesRecords = RunResponsesQuery::apply($request)
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        $ruleSteps = RuleStep::where('rule_id', '=', $runResponsesRecords[0]->step->rule->id)->get();

        return Inertia::render('Genie/Admin/RunResponses/Index', [
            'versionName' => Version::find($runResponsesRecords[0]->step->rule->version_id)->name,
            'workspaceName' => Workspace::find($runResponsesRecords[0]->run->workspace_id)->name,
            'ruleName' => $runResponsesRecords[0]->step->rule->name,
            'ruleType' => $runResponsesRecords[0]->step->rule->rule_type->name,
            'ruleSteps' => $ruleSteps,
            'ruleSubTypes' => RuleSubType::withTitle(),
            'statusTypes' => RunResponseStatus::withTitle(),
            'records' => RunResponseResource::collection($runResponsesRecords),
        ]);
    }

    public function view(Request $request): Response
    {
        $runResponse = RunResponse::all()->find($request->route('run_response'));

        $ruleStep = RuleStep::where('rule_id', '=', $runResponse->all()[0]->step->rule->id)
            ->where('id', '=', $runResponse->step_id)->firstOrFail();

        return Inertia::render('Genie/Admin/RunResponses/View', [
            'versionName' => Version::find($runResponse->all()[0]->step->rule->version_id)->name,
            'workspaceName' => Workspace::find($runResponse->all()[0]->run->workspace_id)->name,
            'ruleName' => $runResponse->all()[0]->step->rule->name,
            'ruleType' => $runResponse->all()[0]->step->rule->rule_type->name,
            'ruleStep' => $ruleStep,
            'ruleSubTypes' => RuleSubType::withTitle(),
            'statusTypes' => RunResponseStatus::withTitle(),
            'runResponse' => new RunResponseResource($runResponse),
        ]);
    }
}
