<?php

namespace App\Http\Controllers\Admin;

use App\Builders\AiRunQuery;
use App\Enums\RuleType;
use App\Enums\RunStatus;
use App\Genie\Strategy\StrategyRunner;
use App\Http\Resources\Admin\AiRunResource;
use App\Jobs\StrategyRunJob;
use App\Models\AiRun;
use App\Models\Rule;
use App\Models\Version;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Models\Workspace;

class RunsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|Response
    {
        $runsRecords = AiRunQuery::apply($request)
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('Genie/Admin/Runs/Index', [
            'records' => AiRunResource::collection($runsRecords),
            'workspaces' => Workspace::all()->keyBy('id')->pluck('name', 'id'),
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
     * Put a run back on the queue.
     *
     * A failed run is released first: advance() refuses to move while the run reads ERROR, so
     * without this the job would sit through its backoff before recovering the step.
     */
    public function resume(Request $request): RedirectResponse
    {
        // Without the workspace scope: an admin resumes any workspace's run, not just their own.
        $run = AiRun::withoutWorkspace()->where('uuid', $request->route('run'))->firstOrFail();

        if ($run->rule?->rule_type !== RuleType::STRATEGY) {
            return redirect()->back()->with('error', __('genie.run_not_resumable'));
        }

        app(StrategyRunner::class)->retryFailed($run);

        StrategyRunJob::dispatch($run->fresh());

        return redirect()->back()->with('success', __('genie.run_resumed'));
    }
}
