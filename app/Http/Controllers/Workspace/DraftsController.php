<?php

namespace App\Http\Controllers\Workspace;

use App\Builders\DraftQuery;
use App\Concerns\Controller\DraftGeneration;
use App\Concerns\Controller\HasFieldOptions;
use App\Concerns\Controller\PrePostGeneration;
use App\Enums\FunnelStage;
use App\Enums\DraftStatus;
use App\Http\Requests\Workspace\Draft\StoreDraft;
use App\Http\Requests\Workspace\Draft\UpdateDraft;
use App\Http\Resources\DraftResource;
use App\Models\Draft;
use App\Models\Idea;
use App\Models\Strategy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inovector\Mixpost\Facades\WorkspaceManager;

class DraftsController
{
    use HasFieldOptions;
    use DraftGeneration;
    use PrePostGeneration;

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
            'draftStatusTypes' => DraftStatus::withTitle(),
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
            'draftStatusTypes' => DraftStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle(),
            'funnelStage' => $request->input('funnel_stage'),
            'contentPillars' => $contentPillars ?? [],
            'record' => null
        ]);
    }


    /**
     * @param UpdateDraft $updateDraft
     */
    public function updateGenerate(UpdateDraft $updateDraft)
    {
        $updateDraft->handle();

        $record = Draft::firstOrFailByUuid($updateDraft->route('draft'));
        $this->prePostGeneration(Draft::where('id', $record->id)->get());

        return redirect()
            ->route('genie.drafts.index', ['workspace' => $updateDraft->route('workspace')])
            ->with('success', __('genie.generating_pre_posts'));
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
            'draftStatusTypes' => DraftStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle(),
            'funnelStage' => $request->input('funnel_stage'),
            'contentPillars' => $contentPillars ?? [],
            'record' => DraftResource::make($record),
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $ideas = Idea::where('uuid', $request->route('idea'))->get();
        $this->draftGeneration($ideas);

        return redirect()
            ->route('genie.ideas.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.generating_drafts'));
    }

    public function generateMultiple(Request $request): RedirectResponse
    {
        $ideas = Idea::whereIn('uuid', $request->input('ideas'))->get();
        $this->draftGeneration($ideas);

        return redirect()
            ->route('genie.ideas.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.generating_drafts'));
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
        $record = Draft::firstOrFailByUuid($request->route('draft'))->byWorkspace(WorkspaceManager::current())->first();

        $redirect = redirect()->route('genie.drafts.index', ['workspace' => $request->route('workspace')]);

        if ($record->status === DraftStatus::TRASH) {

            $result = $record->delete();
            if ($result) {
                return $redirect->with('success', __('genie.draft_deleted'));
            }

        } else {

            $result = $record->update(['status' => DraftStatus::TRASH]);
            if ($result) {
                return $redirect->with('success', __('genie.draft_trashed'));
            }
        }

        return $redirect->with('error', __('genie.draft_not_found'));
    }

}
