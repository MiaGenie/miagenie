<?php

namespace App\Http\Controllers\Workspace;

use App\Builders\IdeaQuery;
use App\Concerns\Controller\DraftGeneration;
use App\Concerns\Controller\HasFieldOptions;
use App\Concerns\Controller\PrePostGeneration;
use App\Enums\DraftStatus;
use App\Enums\FunnelStage;
use App\Enums\GenieSyncAction;
use App\Enums\IdeaStatus;
use App\Enums\RuleType;
use App\Enums\RunStatus;
use App\Http\Requests\Workspace\Idea\StoreIdea;
use App\Http\Requests\Workspace\Idea\UpdateIdea;
use App\Http\Resources\IdeaResource;
use App\Http\Resources\StrategyResource;
use App\Jobs\RunIdeaJob;
use App\Models\Idea;
use App\Models\Rule;
use App\Models\Run;
use App\Models\Strategy;
use App\Models\WorkspaceVersion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inovector\Mixpost\Facades\WorkspaceManager;

class IdeasController
{
    use HasFieldOptions;
    use DraftGeneration;
    use PrePostGeneration;

    /**
     * @param Request $request
     */
    public function index(Request $request)
    {

        $records = IdeaQuery::apply($request)
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        $strategy = Strategy::latest()->first();

        $contentPillars = Idea::whereNotNull('content_pillar')->groupBy('content_pillar')->pluck('content_pillar');

        return Inertia::render('Genie/Workspace/Ideas/Index', [
            'filter' => [
                'funnel_stage' => $request->query('funnel_stage', ''),
                'content_pillar' => $request->query('content_pillar', ''),
                'status' => $request->query('status', '')
            ],
            'records' => IdeaResource::collection($records),
            'strategy' => $strategy ? New StrategyResource($strategy) : null,
            'ideaStatusTypes' => IdeaStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle(),
            'contentPillars' => $contentPillars,
        ]);
    }

    public function create(Request $request)
    {
        $strategy = Strategy::latest()->first();

        if ($strategy && isset($strategy->content['content_pillars']) && !empty($strategy->content['content_pillars'])) {
            $contentPillars = collect($strategy->content['content_pillars'])->pluck('0_title');
        }

        return Inertia::render('Genie/Workspace/Ideas/CreateEdit', [
            'mode' => 'create',
            'ideaStatusTypes' => IdeaStatus::withTitle(),
            'draftStatusTypes' => DraftStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle(),
            'funnelStage' => $request->input('funnel_stage'),
            'contentPillars' => $contentPillars ?? [],
            'record' => null
        ]);
    }

    /**
     * @param StoreIdea $storeIdea
     */
    public function store(StoreIdea $storeIdea)
    {
        $record = $storeIdea->handle();

        return redirect()->route(
            'genie.ideas.edit',
            [
                'workspace' => WorkspaceManager::current()->uuid,
                'idea' => $record->uuid
            ]
        )->with('success', __('genie.idea_created'));
    }

    /**
     * @param UpdateIdea $updateIdea
     */
    public function updateGenerate(UpdateIdea $updateIdea)
    {
        $updateIdea->handle();

        $record = Idea::firstOrFailByUuid($updateIdea->route('idea'));
        $this->draftGeneration(Idea::where('id', $record->id)->get());

        return redirect()
            ->route('genie.ideas.index', ['workspace' => $updateIdea->route('workspace')])
            ->with('success', __('genie.generating_drafts'));
    }

    /**
     * @param Request $request
     */
    public function edit(Request $request)
    {
        $record = Idea::firstOrFailByUuid($request->route('idea'));

        $strategy = Strategy::latest()->first();

        if ($strategy && isset($strategy->content['content_pillars']) && !empty($strategy->content['content_pillars'])) {
            $contentPillars = collect($strategy->content['content_pillars'])->pluck('0_title');
        }

        return Inertia::render('Genie/Workspace/Ideas/CreateEdit', [
            'mode' => 'edit',
            'ideaStatusTypes' => IdeaStatus::withTitle(),
            'draftStatusTypes' => DraftStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle(),
            'funnelStage' => $request->input('funnel_stage'),
            'contentPillars' => $contentPillars ?? [],
            'record' => IdeaResource::make($record),
        ]);
    }

    public function generate(Request $request)
    {
        $workspace = WorkspaceManager::current();
        $workspaceVersion = WorkspaceVersion::where('workspace_id', $workspace->id)->first();

        $strategy = Strategy::findByUuid(request()->route('strategy'));

        $rule = Rule::where('version_id', $workspaceVersion->version_id)->where('rule_type', RuleType::IDEAS)->first();

        $run = Run::create([
            'workspace_id' => $workspace->id,
            'rule_id' => $rule->id,
            'status' => RunStatus::OPEN,
        ]);

        $run->runStrategy()->create([
            'strategy_id' => $strategy->id
        ]);

        RunIdeaJob::dispatch($run, GenieSyncAction::CREATE);

        return redirect()->back()->with('success', __('genie.generating_ideas'));
    }

    /**
     * @param UpdateIdea $updateIdea
     */
    public function update(UpdateIdea $updateIdea)
    {
        $updateIdea->handle();

        return redirect()->back()->with('success', __('genie.idea_updated'));
    }

    /**
     * @param Request $request
     */
    public function destroy(Request $request)
    {
        $record = Idea::firstOrFailByUuid($request->route('idea'));

        $redirect = redirect()->route('genie.ideas.index', ['workspace' => $request->route('workspace')]);

        if ($record->status === IdeaStatus::TRASH) {

            $result = $record->delete();
            if ($result) {
                return $redirect->with('success', __('genie.idea_deleted'));
            }

        } else {

            $result = $record->update(['status' => IdeaStatus::TRASH]);
            if ($result) {
                return $redirect->with('success', __('genie.idea_trashed'));
            }
        }

        return $redirect->with('error', __('genie.idea_not_found'));
    }
}
