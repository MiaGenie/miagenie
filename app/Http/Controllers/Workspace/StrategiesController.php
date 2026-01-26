<?php

namespace App\Http\Controllers\Workspace;

use App\Concerns\Controller\HasFieldOptions;
use App\Concerns\StrategySchemas;
use App\Enums\FormFieldType;
use App\Enums\GenieSyncAction;
use App\Enums\RuleType;
use App\Enums\RunStatus;
use App\Enums\StrategyStatus;
use App\Http\Requests\Workspace\Strategy\ApproveStrategy;
use App\Http\Requests\Workspace\Strategy\ReviewUpdateStrategy;
use App\Http\Requests\Workspace\Strategy\StoreStrategy;
use App\Http\Requests\Workspace\Strategy\UpdateStrategy;
use App\Http\Resources\StrategyResource;
use App\Jobs\RunJob;
use App\Models\Briefing;
use App\Models\Rule;
use App\Models\Run;
use App\Models\Strategy;
use App\Models\VersionField;
use App\Models\WorkspaceVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Facades\WorkspaceManager;

class StrategiesController extends Controller
{
    use HasFieldOptions;
    use StrategySchemas;

    /**
     * @return Response
     */
    public function index(Request $request)
    {
        $record = Strategy::latest()->first();

        $fields = WorkspaceVersion::byWorkspace(WorkspaceManager::current())
            ->with(['version' => ['strategies' => ['options']]])
            ->firstOrFail()
            ->version
            ->toArray();

        $fieldList = $this->groupFieldOptions($fields['strategies']);

        $strategySchemas = $record ? $this->getStrategySchemas($record) : null;

        return Inertia::render(
            'Genie/Workspace/Strategies/Index',
            [
                'record' => $record ? new StrategyResource($record) : null,
                'fieldList' => $fieldList,
                'strategyStatusTypes' => StrategyStatus::withState('', true),
                'fieldTypes' => FormFieldType::withFieldOptions(),
                'schemas' => $strategySchemas,
            ]
        );
    }

    /**
     * @return Response
     */
    public function list(Request $request)
    {

        $records = Strategy::query()
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        $fieldList = WorkspaceVersion::byWorkspace(WorkspaceManager::current())
            ->with(['version' => ['strategies']])
            ->firstOrFail()
            ->version
            ->strategies
            ->toArray();

        return Inertia::render(
            'Genie/Workspace/Strategies/List',
            [
                'filter' => [
                    'keyword' => $request->query('keyword', ''),
                ],
                'records' => StrategyResource::collection($records),
                'fieldList' => $fieldList,
                'runStatus' => RunStatus::withState('', true),
            ]
        );
    }

    /**
     * @return Response
     */
    public function review(Request $request)
    {
        $strategy = Strategy::firstOrFailByUuid($request->strategy);
        $step = $strategy->run->runResponses->last()->step;
        $fieldName = $step->output[0];
        $field = VersionField::where('code_name', $fieldName)->with('options')->firstOrFail();

        $record[$fieldName] = $strategy->content[$fieldName];

        return Inertia::render(
            'Genie/Workspace/Strategies/Review',
            [
                'field' => $field,
                'fieldName' => $fieldName,
                'fieldTypes' => FormFieldType::withFieldOptions(),
                'step' => $step,
                'record' => $record
            ]
        );
    }

    /**
     * @return RedirectResponse
     */
    public function create()
    {
        $workspace = WorkspaceManager::current();
        $workspaceVersion = WorkspaceVersion::first();

        $briefing = Briefing::latest()->first();

        $rule = Rule::where('version_id', $workspaceVersion->version_id)->where('rule_type', RuleType::STRATEGY)->first();

        $run = Run::create([
            'workspace_id' => $workspace->id,
            'rule_id' => $rule->id,
            'status' => RunStatus::OPEN,
        ]);

        $run->runBriefing()->create([
            'briefing_id' => $briefing->id
        ]);

        $strategy = $run->strategy()->create([
            'workspace_id' => $workspace->id,
            'status' => StrategyStatus::OPEN,
        ]);

        RunJob::dispatch($run, GenieSyncAction::CREATE);

        return redirect()->route(
            'genie.strategies.index',
            [
                'workspace' => WorkspaceManager::current()->uuid,
                'strategy' => $strategy->uuid,
            ]
        )->with('success', __('genie.generating_strategy'));
    }

    /**
     * @return RedirectResponse
     */
    public function store(StoreStrategy $storeStrategy)
    {
        $record = $storeStrategy->handle();

        return redirect()->route(
            'genie.strategies.edit',
            [
                'workspace' => WorkspaceManager::current()->uuid,
                'strategy' => $record->uuid,
            ]
        )->with('success', __('genie.strategy_created'));
    }

    /**
     * @return Response
     */
    public function edit(Request $request)
    {
        $record = Strategy::firstOrFailByUuid($request->route('strategy'));

        $fieldList = WorkspaceVersion::byWorkspace(WorkspaceManager::current())
            ->with(['version' => ['strategies' => ['options']]])
            ->firstOrFail()
            ->version
            ->toArray();

        $fieldList['strategies'] = $this->groupFieldOptions($fieldList['strategies']);

        return Inertia::render('Genie/Workspace/Strategies/CreateEdit', [
            'fieldList' => $fieldList,
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'record' => new StrategyResource($record),
            'schemas' => $this->getStrategySchemas($record),
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function update(UpdateStrategy $updateStrategy)
    {
        $record = $updateStrategy->handle();

        return redirect()->back()->with('success', __('genie.strategy_updated'));
    }


    /**
     * @return RedirectResponse
     */
    public function approve(ApproveStrategy $approveStrategy)
    {
        $record = $approveStrategy->handle();

        return redirect()->back()->with('success', __('genie.strategy_approved'));
    }

    /**
     * @return RedirectResponse
     */
    public function review_update(Request $request, ReviewUpdateStrategy $updateStrategy)
    {
        $updateStrategy->handle();

        return redirect()->route('genie.strategies.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.strategy_updated'));
    }

    /**
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        $query = Strategy::byWorkspace(WorkspaceManager::current())
            ->where('uuid', $request->route('strategy'))
            ->delete();

        if (! $query) {
            return redirect()
                ->route('genie.strategies.index', ['workspace' => $request->route('workspace')])
                ->with('error', __('genie.strategy_not_found'));
        }

        return redirect()->route('genie.strategies.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.strategy_deleted'));
    }
}
