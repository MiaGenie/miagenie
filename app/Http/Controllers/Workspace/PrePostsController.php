<?php

namespace App\Http\Controllers\Workspace;

use App\Builders\PrePostQuery;
use App\Concerns\Controller\PrePostGeneration;
use App\Concerns\Controller\HasFieldOptions;
use App\Enums\FunnelStage;
use App\Enums\PrePostStatus;
use App\Http\Requests\Workspace\PrePost\StorePrePost;
use App\Http\Requests\Workspace\PrePost\UpdatePrePost;
use App\Http\Resources\PrePostResource;
use App\Models\PrePost;
use App\Models\Draft;
use App\Models\Strategy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inovector\Mixpost\Facades\WorkspaceManager;

class PrePostsController
{
    use HasFieldOptions;
    use PrePostGeneration;

    /**
     * @param Request $request
     */
    public function index(Request $request)
    {

        $records = PrePostQuery::apply($request)
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();


        return Inertia::render('Genie/Workspace/PrePosts/Index', [
            'filter' => [
                'funnel_stage' => $request->query('funnel_stage', ''),
                'status' => $request->query('status', '')
            ],
            'records' => fn () => PrePostResource::collection($records),
            'statusTypes' => PrePostStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle()
        ]);
    }

    public function create(Request $request)
    {
        $strategy = Strategy::latest()->first();

        if ($strategy && isset($strategy->content['content_pillars']) && !empty($strategy->content['content_pillars'])) {
            $contentPillars = collect($strategy->content['content_pillars'])->pluck('0_title');
        }

        return Inertia::render('Genie/Workspace/PrePosts/CreateEdit', [
            'mode' => 'create',
            'statusTypes' => PrePostStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle(),
            'funnelStage' => $request->input('funnel_stage'),
            'contentPillars' => $contentPillars ?? [],
            'record' => null
        ]);
    }

    /**
     * @param UpdatePrePost $updatePrePost
     */
    public function updateGenerate(UpdatePrePost $updatePrePost)
    {
        $updatePrePost->handle();

        $record = PrePost::firstOrFailByUuid($updatePrePost->route('pre_post'));
        $this->prePostGeneration(PrePost::where('id', $record->id)->get());

        return redirect()
            ->route('genie.pre_posts.index', ['workspace' => $updatePrePost->route('workspace')])
            ->with('success', __('genie.generating_posts'));
    }

    /**
     * @param StorePrePost $storePrePost
     */
    public function store(StorePrePost $storePrePost)
    {
        $record = $storePrePost->handle();

        return redirect()->route(
            'genie.pre_posts.edit',
            [
                'workspace' => WorkspaceManager::current()->uuid,
                'pre_post' => $record->uuid
            ]
        )->with('success', __('genie.pre_post_created'));
    }

    /**
     * @param Request $request
     */
    public function edit(Request $request)
    {
        $record = PrePost::firstOrFailByUuid($request->route('pre_post'));

        $strategy = Strategy::latest()->first();

        if ($strategy && isset($strategy->content['content_pillars']) && !empty($strategy->content['content_pillars'])) {
            $contentPillars = collect($strategy->content['content_pillars'])->pluck('0_title');
        }

        return Inertia::render('Genie/Workspace/PrePosts/CreateEdit', [
            'mode' => 'edit',
            'statusTypes' => PrePostStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle(),
            'funnelStage' => $request->input('funnel_stage'),
            'contentPillars' => $contentPillars ?? [],
            'record' => PrePostResource::make($record),
        ]);
    }

    public function generateMultiple(Request $request): RedirectResponse
    {
        $drafts = Draft::whereIn('uuid', $request->input('drafts'))->get();
        $this->prePostGeneration($drafts);

        return redirect()
            ->route('genie.drafts.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.generating_pre_posts'));
    }

    /**
     * @param UpdatePrePost $updatePrePost
     */
    public function update(UpdatePrePost $updatePrePost)
    {
        $updatePrePost->handle();

        return redirect()->back()->with('success', __('genie.pre_post_updated'));
    }

    /**
     * @param Request $request
     */
    public function destroy(Request $request)
    {
        $query = PrePost::byWorkspace(WorkspaceManager::current())
            ->where('uuid', $request->route('pre_post'))
            ->delete();

        if (!$query) {
            return redirect()
                ->route('genie.pre_posts.index', ['workspace' => $request->route('workspace')])
                ->with('error', __('genie.pre_post_not_found'));
        }

        return redirect()->route('genie.pre_posts.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.pre_post_deleted'));
    }

}
