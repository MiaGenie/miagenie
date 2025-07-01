<?php

namespace App\Http\Controllers\Admin;

use App\Builders\ThreadQuery;
use App\Enums\RuleStatus;
use App\Enums\RuleSubType;
use App\Enums\RuleType;
use App\Http\Resources\Admin\ThreadResource;
use App\Models\Rule;
use App\Models\RuleStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ThreadsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|Response
    {
        $threadsRecords = ThreadQuery::apply($request)
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('Genie/Admin/Threads/Index', [
            'filter' => [
                'rule_type' => $request->query('rule_type', ''),
            ],
            'ruleTypes' => RuleType::withTitle(),
            'ruleSubTypes' => RuleSubType::withTitle(),
            'rules' => Rule::all(),
            'ruleSteps' => RuleStep::all(),
            'statusTypes' => RuleStatus::withTitle(),
            'records' => ThreadResource::collection($threadsRecords),
        ]);
    }
}
