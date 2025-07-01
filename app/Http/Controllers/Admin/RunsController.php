<?php

namespace App\Http\Controllers\Admin;

use App\Builders\RunQuery;
use App\Enums\RuleStatus;
use App\Enums\RuleSubType;
use App\Enums\RuleType;
use App\Http\Resources\Admin\RunResource;
use App\Models\Rule;
use App\Models\RuleStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class RunsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|Response
    {
        $runsRecords = RunQuery::apply($request)
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('Genie/Admin/Runs/Index', [
            'filter' => [
                'rule_type' => $request->query('rule_type', ''),
            ],
            'ruleTypes' => RuleType::withTitle(),
            'ruleSubTypes' => RuleSubType::withTitle(),
            'rules' => Rule::all(),
            'ruleSteps' => RuleStep::all(),
            'statusTypes' => RuleStatus::withTitle(),
            'records' => RunResource::collection($runsRecords),
        ]);
    }
}
