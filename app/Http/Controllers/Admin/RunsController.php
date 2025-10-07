<?php

namespace App\Http\Controllers\Admin;

use App\Builders\RunQuery;
use App\Enums\GenieSyncAction;
use App\Enums\RuleType;
use App\Enums\RunStatus;
use App\Http\Resources\Admin\RunResource;
use App\Jobs\RunJob;
use App\Models\Rule;
use App\Models\Run;
use App\Models\Version;
use App\Models\WorkspaceVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Inovector\Mixpost\Models\Workspace;

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
            'records' => RunResource::collection($runsRecords),
            'workspaces' => Workspace::pluck('name'),
            'rules' => Rule::all(),
            'versions' => Version::all(),
            'ruleTypes' => RuleType::withTitle(),
            'runStatus' => RunStatus::withTitle(),
            'filter' => [
                'rule_type' => $request->query('rule_type', ''),
            ],
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function resume(Request $request)
    {
        $run = Run::firstOrFailByUuid($request->route('run'));

        RunJob::dispatch($run, GenieSyncAction::UPDATE);

        return redirect()->back()->with('success', __('genie.run_resume'));
    }
}
