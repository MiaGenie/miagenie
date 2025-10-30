<?php

namespace App\Http\Controllers\Workspace;

use App\Builders\DraftQuery;
use App\Concerns\Controller\HasFieldOptions;
use App\Enums\FunnelStage;
use App\Enums\GenieSyncAction;
use App\Enums\DraftStatus;
use App\Enums\RuleType;
use App\Enums\RunStatus;
use App\Http\Requests\Workspace\Draft\StoreDraft;
use App\Http\Requests\Workspace\Draft\UpdateDraft;
use App\Http\Resources\DraftResource;
use App\Jobs\RunIdeaJob;
use App\Models\Draft;
use App\Models\Rule;
use App\Models\Run;
use App\Models\Strategy;
use App\Models\WorkspaceVersion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inovector\Mixpost\Facades\WorkspaceManager;

class DraftsController
{
    use HasFieldOptions;

    /**
     * @param Request $request
     */
    public function index(Request $request)
    {

        $records = DraftQuery::apply($request)
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();


        return Inertia::render('Genie/Workspace/Drafts/Index', [
            'filter' => [
                'funnel_stage' => $request->query('funnel_stage', ''),
                'status' => $request->query('status', '')
            ],
            'records' => fn () => DraftResource::collection($records),
            'statusTypes' => DraftStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle()
        ]);
    }

    public function create(Request $request)
    {
        $strategy = Strategy::latest()->first();

        if ($strategy && isset($strategy->content['content_pillars']) && !empty($strategy->content['content_pillars'])) {
            $contentPillars = collect($strategy->content['content_pillars'])->pluck('0_title');
        }

        return Inertia::render('Genie/Workspace/Drafts/CreateEdit', [
            'mode' => 'create',
            'statusTypes' => DraftStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle(),
            'funnelStage' => $request->input('funnel_stage'),
            'contentPillars' => $contentPillars ?? [],
            'record' => null
        ]);
    }

    /**
     * @param StoreDraft $storeDraft
     */
    public function store(StoreDraft $storeDraft)
    {
        $record = $storeDraft->handle();

        return redirect()->route(
            'genie.drafts.edit',
            [
                'workspace' => WorkspaceManager::current()->uuid,
                'draft' => $record->uuid
            ]
        )->with('success', __('genie.draft_created'));
    }

    /**
     * @param Request $request
     */
    public function edit(Request $request)
    {
        $record = Draft::firstOrFailByUuid($request->route('draft'));

        $strategy = Strategy::latest()->first();

        if ($strategy && isset($strategy->content['content_pillars']) && !empty($strategy->content['content_pillars'])) {
            $contentPillars = collect($strategy->content['content_pillars'])->pluck('0_title');
        }

        return Inertia::render('Genie/Workspace/Drafts/CreateEdit', [
            'mode' => 'edit',
            'statusTypes' => DraftStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle(),
            'funnelStage' => $request->input('funnel_stage'),
            'contentPillars' => $contentPillars ?? [],
            'record' => DraftResource::make($record),
        ]);
    }

    public function generate(Request $request)
    {
        $workspace = WorkspaceManager::current();
        $workspaceVersion = WorkspaceVersion::where('workspace_id', $workspace->id)->first();

        $strategy = Strategy::findByUuid(request()->route('strategy'));

        $rule = Rule::where('version_id', $workspaceVersion->version_id)->where('rule_type', RuleType::DRAFTS)->first();

        $run = Run::create([
            'workspace_id' => $workspace->id,
            'rule_id' => $rule->id,
            'status' => RunStatus::OPEN,
        ]);

        $run->runIdea()->create([
            'strategy_id' => $strategy->id
        ]);

        RunIdeaJob::dispatch($run, GenieSyncAction::CREATE);

        return redirect()->back()->with('success', __('genie.generating_drafts'));
    }

    /**
     * @param UpdateDraft $updateDraft
     */
    public function update(UpdateDraft $updateDraft)
    {
        $updateDraft->handle();

        return redirect()->back()->with('success', __('genie.draft_updated'));
    }

    /**
     * @param Request $request
     */
    public function destroy(Request $request)
    {
        $query = Draft::byWorkspace(WorkspaceManager::current())
            ->where('uuid', $request->route('draft'))
            ->delete();

        if (!$query) {
            return redirect()
                ->route('genie.drafts.index', ['workspace' => $request->route('workspace')])
                ->with('error', __('genie.draft_not_found'));
        }

        return redirect()->route('genie.drafts.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.draft_deleted'));
    }
}
