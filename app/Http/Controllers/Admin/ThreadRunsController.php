<?php

namespace App\Http\Controllers\Admin;

use App\Builders\ThreadRunsQuery;
use App\Enums\RuleStatus;
use App\Enums\RuleSubType;
use App\Http\Resources\Admin\ThreadRunsResource;
use App\Models\Rule;
use App\Models\RuleStep;
use App\Models\Thread;
use App\Models\ThreadRuns;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ThreadRunsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|Response
    {
        $threadRunsRecords = ThreadRunsQuery::apply($request)
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        $thread = Thread::firstOrFailByUuid($request->route('thread'));
        $rule = Rule::find($thread->rule_id);
        $ruleSubType = RuleStep::where('rule_id', '=', $rule->id)->firstOrFail();

        return Inertia::render('Genie/Admin/ThreadRuns/Index', [
            'threadUuid' => $thread->uuid,
            'ruleType' => $rule->rule_type->name,
            'ruleSubType' => $ruleSubType->rule_sub_type->name,
            'statusTypes' => RuleStatus::withTitle(),
            'records' => ThreadRunsResource::collection($threadRunsRecords),
        ]);
    }

    public function view(Request $request): Response
    {
        $threadRun = ThreadRuns::firstOrFailByUuid($request->route('thread_run'));

        $threadUuid = Thread::find($threadRun->thread_id, ['uuid']);

        return Inertia::render('Genie/Admin/ThreadRuns/View', [
            'threadRun' => new ThreadRunsResource($threadRun),
            'threadUuid' => $threadUuid->uuid,
        ]);
    }
}
