<?php

namespace App\Http\Controllers\Workspace;

use App\Concerns\Controller\HasWorkspaceLocale;
use App\Concerns\StrategySchemas;
use App\Enums\BriefingStatus;
use App\Enums\RuleType;
use App\Enums\RunStatus;
use App\Enums\StrategyStatus;
use App\Enums\VersionGroupType;
use App\Genie\Strategy\StrategyRunState;
use App\Http\Requests\Workspace\Strategy\UpdateStrategy;
use App\Http\Resources\StrategyResource;
use App\Jobs\StrategyRunJob;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\AiRunStepReview;
use App\Models\Briefing;
use App\Models\Rule;
use App\Models\Strategy;
use App\Models\VersionField;
use App\Models\WorkspaceVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Facades\WorkspaceManager;

/**
 * The strategy presentation layer: every field the run wrote, rendered from the step schema and
 * editable in place until the strategy is approved.
 */
class StrategyPresentationController extends Controller
{
    use HasWorkspaceLocale;
    use StrategySchemas;

    public function index(): Response
    {
        $record = Strategy::latest()->first();

        return Inertia::render('Genie/Workspace/Strategies/Presentation', [
            'record' => $record ? new StrategyResource($record) : null,
            'fieldList' => $this->fieldList(),
            'schemas' => $record ? $this->getStrategySchemas($record) : (object) [],
            'meta' => $record ? $this->getStrategyMeta($record) : (object) [],
            'strategyStatusTypes' => StrategyStatus::withState('', true),
        ]);
    }

    /**
     * The entry point for the strategy: it resolves which stage the workspace is at and shows
     * only what that stage needs. A finished strategy goes straight to the presentation.
     */
    public function overview(): Response|RedirectResponse
    {
        $run = AiRun::latest('id')->first();
        $record = $run?->strategy ?? Strategy::latest()->first();
        $status = $record?->status;

        if ($status === StrategyStatus::PENDING_APPROVAL || $status === StrategyStatus::APPROVED) {
            return redirect()->route('genie.strategies.presentation', [
                'workspace' => WorkspaceManager::current()->uuid,
            ]);
        }

        return Inertia::render('Genie/Workspace/Strategies/Overview', [
            'stage' => $this->stage($run, $record),
            'briefing' => $this->briefingState(),
            'record' => $record ? new StrategyResource($record) : null,
            'progress' => $this->progress($run),
        ]);
    }

    /**
     * @return 'briefing'|'generate'|'running'|'review'|'error'
     */
    private function stage(?AiRun $run, ?Strategy $record): string
    {
        if (! $this->briefingState()['complete']) {
            return 'briefing';
        }

        if (! $run && ! $record) {
            return 'generate';
        }

        return match (true) {
            $run?->status === RunStatus::ERROR || $record?->status === StrategyStatus::ERROR => 'error',
            $run?->status === RunStatus::PENDING_REVIEW || $record?->status === StrategyStatus::PENDING_REVIEW => 'review',
            default => 'running',
        };
    }

    /**
     * A briefing counts as complete once the customer has finished it in the wizard. The answers
     * alone cannot say so: the wizard drafts every question on the way through, so a briefing whose
     * fields are all filled may still be one the customer never chose to submit.
     *
     * `missing` is what the wizard's own Finish is held back by — the genie-required fields — so the
     * names listed here are the ones actually standing between the customer and that button.
     *
     * @return array{exists: bool, complete: bool, missing: array<int, string>}
     */
    private function briefingState(): array
    {
        $briefing = Briefing::latest()->first();

        $required = WorkspaceVersion::where('workspace_id', WorkspaceManager::current()->id)
            ->with(['version' => ['briefings']])
            ->firstOrFail()
            ->version
            ->briefings
            ->where('genie_required', true);

        $content = $briefing?->content ?? [];

        $missing = $required
            ->filter(fn ($field) => $this->isBlankAnswer($content[$field->code_name] ?? null))
            ->map(fn ($field) => $field->name)
            ->values()
            ->all();

        return [
            'exists' => (bool) $briefing,
            'complete' => $briefing?->status === BriefingStatus::COMPLETE && $missing === [],
            'missing' => $missing,
        ];
    }

    /**
     * Option-bearing answers arrive as arrays that may hold nulls for unanswered groups.
     */
    private function isBlankAnswer(mixed $answer): bool
    {
        if (is_array($answer)) {
            return array_filter($answer, fn ($item) => $item !== null && $item !== '') === [];
        }

        return blank($answer);
    }

    /**
     * How far the run has got, and which step it is on.
     *
     * A skipped step is done as far as the bar is concerned — it is a step of the rule the run
     * will never come back to — but it is never named as the step in progress, since nothing is
     * being generated for a channel the brand did not pick.
     *
     * @return array{done: int, total: int, step: string|null}|null
     */
    private function progress(?AiRun $run): ?array
    {
        if (! $run) {
            return null;
        }

        $steps = $run->steps()->with('step')->get();

        return [
            'done' => $steps->filter(fn (AiRunStep $step) => $step->status->isComplete())->count(),
            'total' => $run->rule?->steps()->count() ?? 0,
            'step' => $steps->last(fn (AiRunStep $step) => $step->status !== RunStatus::SKIPPED)?->step?->name,
        ];
    }

    /**
     * Start a strategy run on the new pipeline.
     *
     * The run owns its briefing and strategy directly, so the two pivot tables the old path wrote
     * are not involved, and the conversation is opened by the first step.
     */
    public function generate(): RedirectResponse
    {
        $workspace = WorkspaceManager::current();

        $briefing = Briefing::latest()->first();

        if (! $briefing) {
            return redirect()->back()->with('error', __('genie.briefing_not_found'));
        }

        $version = WorkspaceVersion::where('workspace_id', $workspace->id)->firstOrFail();

        $rule = Rule::where('version_id', $version->version_id)
            ->where('rule_type', RuleType::STRATEGY)
            ->firstOrFail();

        $run = AiRun::create([
            'workspace_id' => $workspace->id,
            'rule_id' => $rule->id,
            'briefing_id' => $briefing->id,
            'strategy_id' => Strategy::create([
                'workspace_id' => $workspace->id,
                'status' => StrategyStatus::OPEN,
            ])->id,
            'status' => RunStatus::OPEN,
        ]);

        StrategyRunJob::dispatch($run);

        return redirect()->route('genie.strategies.overview', ['workspace' => $workspace->uuid])
            ->with('success', __('genie.generating_strategy'));
    }

    /**
     * The step a run is waiting on, with every field it writes.
     *
     * The old review page read `$strategy->run->runResponses->last()->step`, which breaks three
     * ways: a strategy from this pipeline has no legacy run at all, `last()` is the newest
     * response rather than the gated one, and it only ever offered `output[0]`, so the other
     * fields of a multi-output step could not be reviewed.
     */
    public function review(): Response|RedirectResponse
    {
        $run = AiRun::latest('id')->first();
        $runStep = $run?->steps()->reorder()->where('status', RunStatus::PENDING_REVIEW)->oldest('position')->first();

        if (! $runStep) {
            return redirect()->route('genie.strategies.overview', [
                'workspace' => WorkspaceManager::current()->uuid,
            ]);
        }

        $content = $run->strategy?->content ?? [];
        $codeNames = $runStep->step->output ?? [];

        return Inertia::render('Genie/Workspace/Strategies/StepReview', [
            'runStep' => [
                'id' => $runStep->uuid,
                'name' => $runStep->step->name,
                'position' => $runStep->position,
                'total' => $run->rule?->steps()->count() ?? 0,
                'message' => $runStep->step->getTranslation('review_message_user', $this->workspaceLocale()),
            ],
            'fieldList' => $this->reviewFields($run, $codeNames),
            'schemas' => $this->getStrategySchemas($run->strategy),
            'meta' => $this->getStrategyMeta($run->strategy),
            'content' => array_intersect_key($content, array_flip($codeNames)),
        ]);
    }

    /**
     * Accept the step review and let the run continue.
     *
     * What the reviewer left is written back to the strategy, and any change is recorded so the
     * next step can be told about it — StrategyPrompt pairs it with the step's
     * `review_message_system`.
     */
    public function approveReview(Request $request): RedirectResponse
    {
        $runStep = AiRunStep::firstOrFailByUuid($request->route('runStep'));
        $run = $runStep->run;

        abort_unless($run->workspace_id === WorkspaceManager::current()->id, 403);

        $request->validate(['content' => ['required', 'array']]);

        $codeNames = $runStep->step->output ?? [];
        $reviewed = array_intersect_key($request->input('content'), array_flip($codeNames));
        $original = array_intersect_key($run->strategy?->content ?? [], array_flip($codeNames));

        if ($reviewed !== $original) {
            $run->strategy?->update([
                'content' => array_merge($run->strategy->content ?? [], $reviewed),
            ]);

            AiRunStepReview::create([
                'run_step_id' => $runStep->id,
                'reviewed_by' => $request->user()?->id,
                'original' => $original,
                'reviewed' => $reviewed,
            ]);
        }

        app(StrategyRunState::class)->resumed($runStep);

        StrategyRunJob::dispatch($run->fresh());

        return redirect()->route('genie.strategies.overview', [
            'workspace' => WorkspaceManager::current()->uuid,
        ])->with('success', __('genie.strategy_updated'));
    }

    /**
     * The fields a step writes, scoped to the run's own version.
     *
     * `code_name` is only unique within a version — `big_idea` exists in four of them — so the
     * old page's unscoped lookup resolved to whichever version came first and rendered the wrong
     * field definition.
     *
     * The options relation is deliberately not loaded: a strategy field's choices come from its
     * sub-field `enum_values` now, which reach the page through the compiled schema.
     *
     * @param  array<int, string>  $codeNames
     * @return array<int, array<string, mixed>>
     */
    private function reviewFields(AiRun $run, array $codeNames): array
    {
        $fields = VersionField::where('version_id', $run->rule?->version_id)
            ->where('group_type', VersionGroupType::STRATEGIES)
            ->whereIn('code_name', $codeNames)
            ->get()
            ->keyBy('code_name');

        return $this->localizedFields(
            collect($codeNames)
                ->map(fn (string $codeName) => $fields->get($codeName))
                ->filter()
                ->values()
        );
    }

    /**
     * Persist the edited content.
     *
     * The keys are filtered against the strategy's *own* version, so a payload cannot introduce
     * fields the version does not define, and anything the request omits keeps its stored value.
     */
    public function update(UpdateStrategy $request): RedirectResponse
    {
        $record = Strategy::firstOrFailByUuid($request->route('strategy'));

        if ($record->status === StrategyStatus::APPROVED) {
            return redirect()->back()->with('error', __('genie.strategy_approved'));
        }

        $request->handle();

        return redirect()->back()->with('success', __('genie.strategy_updated'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fieldList(): array
    {
        $version = WorkspaceVersion::where('workspace_id', WorkspaceManager::current()->id)
            ->with(['version' => ['strategies']])
            ->firstOrFail()
            ->version;

        return $this->localizedFields($version->strategies);
    }
}
