<?php

namespace App\Http\Controllers\Workspace;

use App\Builders\IdeaQuery;
use App\Enums\FunnelStage;
use App\Enums\IdeaStatus;
use App\Enums\PostFormat;
use App\Enums\RuleType;
use App\Enums\RunStatus;
use App\Enums\StrategyStatus;
use App\Genie\Ideas\ContentPillars;
use App\Genie\Ideas\IdeasRequest;
use App\Genie\Runs\PostFormatGate;
use App\Http\Requests\Workspace\Idea\GenerateIdeas;
use App\Http\Requests\Workspace\Idea\StoreIdea;
use App\Http\Requests\Workspace\Idea\UpdateIdea;
use App\Http\Resources\IdeaResource;
use App\Http\Resources\StrategyResource;
use App\Jobs\AiRunJob;
use App\Models\AiRun;
use App\Models\Idea;
use App\Models\Rule;
use App\Models\Strategy;
use App\Models\WorkspaceVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Facades\WorkspaceManager;

class IdeasController
{
    public function index(Request $request): Response
    {
        $records = IdeaQuery::apply($request)
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        $strategy = Strategy::latest()->first();

        $hasPending = $records->where('status', IdeaStatus::PENDING_REVIEW)->count() > 0;

        $contentPillars = Idea::whereNotNull('content_pillar')->groupBy('content_pillar')->pluck('content_pillar');

        return Inertia::render('Genie/Workspace/Ideas/Index', [
            'filter' => [
                'funnel_stage' => $request->query('funnel_stage', ''),
                'content_pillar' => $request->query('content_pillar', ''),
                'post_format' => $request->query('post_format', ''),
                'status' => $request->query('status', ''),
            ],
            'records' => IdeaResource::collection($records),
            'strategy' => $strategy ? new StrategyResource($strategy) : null,
            'canGenerate' => $strategy?->status === StrategyStatus::APPROVED,
            'ideaStatusTypes' => IdeaStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle(),
            'postFormats' => PostFormat::withTitle(),
            'contentPillars' => $contentPillars,
            'strategyPillars' => ContentPillars::titles($strategy),
            'counts' => IdeasRequest::COUNTS,
            'generating' => $this->generating(),
            'hasPending' => $hasPending,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Genie/Workspace/Ideas/CreateEdit', [
            'mode' => 'create',
            'ideaStatusTypes' => IdeaStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle(),
            'postFormats' => PostFormat::withTitle(),
            'funnelStage' => $request->input('funnel_stage'),
            'contentPillars' => ContentPillars::titles(Strategy::latest()->first()),
            'record' => null,
        ]);
    }

    public function store(StoreIdea $storeIdea): RedirectResponse
    {
        $record = $storeIdea->handle();

        return redirect()->route(
            'genie.ideas.edit',
            [
                'workspace' => WorkspaceManager::current()->uuid,
                'idea' => $record->uuid,
            ]
        )->with('success', __('genie.idea_created'));
    }

    public function edit(Request $request): Response
    {
        $record = Idea::firstOrFailByUuid($request->route('idea'));

        return Inertia::render('Genie/Workspace/Ideas/CreateEdit', [
            'mode' => 'edit',
            'ideaStatusTypes' => IdeaStatus::withTitle(),
            'funnelStages' => FunnelStage::withTitle(),
            'postFormats' => PostFormat::withTitle(),
            'funnelStage' => $request->input('funnel_stage'),
            'contentPillars' => ContentPillars::titles(Strategy::latest()->first()),
            'record' => IdeaResource::make($record),
        ]);
    }

    /**
     * Start an ideas run for what the page asked: one run, gated to the requested format, whose
     * generator steps share the requested total between them.
     */
    public function generate(GenerateIdeas $request): RedirectResponse
    {
        $strategy = $request->strategy();

        if (! $strategy) {
            return redirect()->back()->with('error', __('genie.ideas_strategy_not_approved'));
        }

        if ($this->generating()) {
            return redirect()->back()->with('error', __('genie.ideas_already_generating'));
        }

        $workspace = WorkspaceManager::current();
        $version = WorkspaceVersion::where('workspace_id', $workspace->id)->firstOrFail();

        $rule = Rule::where('version_id', $version->version_id)
            ->where('rule_type', RuleType::IDEAS)
            ->firstOrFail();

        $run = new AiRun([
            'workspace_id' => $workspace->id,
            'rule_id' => $rule->id,
            'strategy_id' => $strategy->id,
            'parameters' => $request->toRequest()->toArray(),
            'status' => RunStatus::OPEN,
        ]);

        $run->setRelation('rule', $rule);

        // A format no step generates for would run to completion with nothing to show; better
        // to say so before the run exists.
        if (new PostFormatGate()->generators($run)->isEmpty()) {
            return redirect()->back()->with('error', __('genie.ideas_no_generator_for_format'));
        }

        $run->save();

        AiRunJob::dispatch($run);

        return redirect()->back()->with('success', __('genie.generating_ideas_started'));
    }

    public function update(UpdateIdea $updateIdea): RedirectResponse
    {
        $updateIdea->handle();

        return redirect()->back()->with('success', __('genie.idea_updated'));
    }

    public function destroy(Request $request): RedirectResponse
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

    /**
     * Whether an ideas run is still going in this workspace.
     */
    protected function generating(): bool
    {
        return AiRun::query()
            ->whereIn('status', [RunStatus::OPEN, RunStatus::RUNNING])
            ->whereHas('rule', fn ($query) => $query->where('rule_type', RuleType::IDEAS))
            ->exists();
    }
}
