<?php

namespace App\Http\Controllers\Admin;

use App\Builders\RunResponsesQuery;
use App\Enums\RuleStatus;
use App\Enums\RuleSubType;
use App\Http\Resources\Admin\RunResponseResource;
use App\Models\Rule;
use App\Models\RuleStep;
use App\Models\Run;
use App\Models\RunResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class RunResponsesController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|Response
    {
        $runResponsesRecords = RunResponsesQuery::apply($request)
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        $run = Run::firstOrFailByUuid($request->route('run'));
        $rule = Rule::find($run->rule_id);
        $ruleSteps = RuleStep::where('rule_id', '=', $rule->id)->get();

        return Inertia::render('Genie/Admin/RunResponses/Index', [
            'runUuid' => $run->uuid,
            'ruleType' => $rule->rule_type->name,
            'ruleSteps' => $ruleSteps,
            'ruleSubTypes' => RuleSubType::withTitle(),
            'statusTypes' => RuleStatus::withTitle(),
            'records' => RunResponseResource::collection($runResponsesRecords),
        ]);
    }

    public function view(Request $request): Response
    {
        $runResponsesRecords = RunResponse::firstOrFailByUuid($request->route('run_response'));

        $run = Run::where('id', '=', $runResponsesRecords->run_id)->firstOrFail();
        $rule = Rule::find($run->rule_id);
        $ruleSteps = RuleStep::where('rule_id', '=', $rule->id)->where('id', '=',$runResponsesRecords->step_id)->firstOrFail();

        return Inertia::render('Genie/Admin/RunResponses/View', [
            'runUuid' => $run->uuid,
            'ruleType' => $rule->rule_type->name,
            'ruleSteps' => $ruleSteps,
            'ruleSubTypes' => RuleSubType::withTitle(),
            'statusTypes' => RuleStatus::withTitle(),
            'runResponse' => new RunResponseResource($runResponsesRecords),
        ]);
    }
}
