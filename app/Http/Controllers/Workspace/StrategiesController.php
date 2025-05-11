<?php

namespace App\Http\Controllers\Workspace;

use App\Concerns\Controller\HasFieldOptions;
use App\Enums\FormFieldType;
use App\Http\Requests\Workspace\Strategy\StoreStrategy;
use App\Http\Requests\Workspace\Strategy\UpdateStrategy;
use App\Http\Resources\StrategyResource;
use App\Models\Strategy;
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

    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
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

        return Inertia::render('Genie/Workspace/Strategies/Index', [
            'filter' => [
                'keyword' => $request->query('keyword', ''),
            ],
            'records' => fn () => StrategyResource::collection($records),
            'fieldList' => $fieldList,
        ]);
    }

    /**
     * @return Response
     */
    public function create()
    {
        $fieldList = WorkspaceVersion::byWorkspace(WorkspaceManager::current())
            ->with(['version' => ['strategies' => ['options']]])
            ->firstOrFail()
            ->version
            ->toArray();

        $fieldList['strategies'] = $this->groupFieldOptions($fieldList['strategies']);

        return Inertia::render('Genie/Workspace/Strategies/CreateEdit', [
            'mode' => 'create',
            'fieldList' => $fieldList,
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'record' => null,
        ]);
    }

    /**
     * @param StoreStrategy $storeStrategy
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
     * @param Request $request
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
            'mode' => 'edit',
            'fieldList' => $fieldList,
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'record' => new StrategyResource($record),
        ]);
    }

    /**
     * @param UpdateStrategy $updateStrategy
     * @return RedirectResponse
     */
    public function update(UpdateStrategy $updateStrategy)
    {
        $updateStrategy->handle();

        return redirect()->back()->with('success', __('genie.strategy_updated'));
    }

    /**
     * @param Request $request
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
