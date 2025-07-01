<?php

namespace App\Http\Controllers\Admin;

use App\Builders\RunResponsesQuery;
use App\Enums\RuleStatus;
use App\Enums\RuleSubType;
use App\Http\Resources\Admin\RunResponsesResource;
use App\Models\Rule;
use App\Models\RuleStep;
use App\Models\Thread;
use App\Models\RunResponses;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class RunResponsesController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|Response
    {
        $threadRunsRecords = RunResponsesQuery::apply($request)
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        $thread = Thread::firstOrFailByUuid($request->route('thread'));
        $rule = Rule::find($thread->rule_id);
        $ruleSubType = RuleStep::where('rule_id', '=', $rule->id)->firstOrFail();

        return Inertia::render('Genie/Admin/RunResponses/Index', [
            'threadUuid' => $thread->uuid,
            'ruleType' => $rule->rule_type->name,
            'ruleSubType' => $ruleSubType->rule_sub_type->name,
            'statusTypes' => RuleStatus::withTitle(),
            'records' => RunResponsesResource::collection($threadRunsRecords),
        ]);
    }

    public function view(Request $request): Response
    {
        $threadRun = RunResponses::firstOrFailByUuid($request->route('thread_run'));

        $threadUuid = Thread::find($threadRun->thread_id, ['uuid']);

        return Inertia::render('Genie/Admin/RunResponses/View', [
            'threadRun' => new RunResponsesResource($threadRun),
            'threadUuid' => $threadUuid->uuid,
        ]);
    }
}
