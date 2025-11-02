<?php

namespace App\Http\Controllers\Workspace;

use App\Builders\IdeaQuery;
use App\Concerns\Controller\DraftGeneration;
use App\Concerns\Controller\HasFieldOptions;
use App\Concerns\Controller\PrePostGeneration;
use App\Enums\FunnelStage;
use App\Enums\GenieSyncAction;
use App\Enums\IdeaStatus;
use App\Enums\RuleType;
use App\Enums\RunStatus;
use App\Http\Requests\Workspace\Idea\StoreIdea;
use App\Http\Requests\Workspace\Idea\UpdateIdea;
use App\Http\Resources\IdeaResource;
use App\Jobs\RunIdeaJob;
use App\Models\Draft;
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


        return Inertia::render('Genie/Workspace/Ideas/Index', [
            'filter' => [
                'funnel_stage' => $request->query('funnel_stage', ''),
                'status' => $request->query('status', '')
            ],
            'records' => fn () => IdeaResource::collection($records),
            'statusTypes' => IdeaStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle()
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
            'statusTypes' => IdeaStatus::withTitle(),
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
            'statusTypes' => IdeaStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle(),
            'funnelStage' => $request->input('funnel_stage'),
            'contentPillars' => $contentPillars ?? [],
            'record' => IdeaResource::make($record),
        ]);
    }

    public function generate(Request $request)
    {

        $record = Draft::where('uuid', $request->input('draft'))->get();
        $this->prePostGeneration(Draft::where('id', $record->id)->get());

        return redirect()
            ->route('genie.drafts.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.generating_pre_posts'));
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
        $query = Idea::byWorkspace(WorkspaceManager::current())
            ->where('uuid', $request->route('idea'))
            ->delete();

        if (!$query) {
            return redirect()
                ->route('genie.ideas.index', ['workspace' => $request->route('workspace')])
                ->with('error', __('genie.idea_not_found'));
        }

        return redirect()->route('genie.ideas.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.idea_deleted'));
    }
}
