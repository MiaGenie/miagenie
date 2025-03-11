<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\FormFieldType;
use App\Http\Requests\Workspace\Competitor\StoreCompetitor;
use App\Http\Requests\Workspace\Competitor\UpdateCompetitor;
use App\Http\Resources\CompetitorResource;
use App\Models\Competitor;
use App\Models\Version;
use App\Models\WorkspaceVersion;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inovector\Mixpost\Facades\WorkspaceManager;

class CompetitorsController extends Controller
{
    /**
     * @param Request $request
     */
    public function index(Request $request)
    {

        $records = Competitor::query()
            ->latest()
            ->paginate(20)
            ->onEachSide(1)
            ->withQueryString();

        $fieldList = WorkspaceVersion::byWorkspace(WorkspaceManager::current())
            ->with(['version' => ['competitors']])
            ->firstOrFail()
            ->version
            ->competitors
            ->toArray();

        return Inertia::render('Genie/Workspace/Competitors/Index', [
            'filter' => [
                'keyword' => $request->query('keyword', ''),
            ],
            'records' => fn () => CompetitorResource::collection($records),
            'fieldList' => $fieldList
        ]);
    }

    public function create()
    {
        $fieldList = WorkspaceVersion::byWorkspace(WorkspaceManager::current())
            ->with(['version' => ['competitors' => ['options']]])
            ->firstOrFail()
            ->version
            ->toArray();

        return Inertia::render('Genie/Workspace/Competitors/CreateEdit', [
            'mode' => 'create',
            'fieldList' => $fieldList,
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'record' => null
        ]);
    }

    /**
     * @param StoreCompetitor $storeCompetitor
     */
    public function store(StoreCompetitor $storeCompetitor)
    {
        $record = $storeCompetitor->handle();

        return redirect()->route(
            'genie.competitors.edit',
            [
                'workspace' => WorkspaceManager::current()->uuid,
                'competitor' => $record->uuid
            ]
        )->with('success', __('genie.competitor_created'));
    }

    /**
     * @param Request $request
     */
    public function edit(Request $request)
    {
        $record = Competitor::firstOrFailByUuid($request->route('competitor'));

        $fieldList = WorkspaceVersion::byWorkspace(WorkspaceManager::current())
            ->with(['version' => ['competitors' => ['options']]])
            ->firstOrFail()
            ->version
            ->toArray();

        return Inertia::render('Genie/Workspace/Competitors/CreateEdit', [
            'mode' => 'edit',
            'fieldList' => $fieldList,
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'record' => new CompetitorResource($record)
        ]);
    }

    /**
     * @param UpdateCompetitor $updateCompetitor
     */
    public function update(UpdateCompetitor $updateCompetitor)
    {
        $updateCompetitor->handle();

        return redirect()->back()->with('success', __('genie.competitor_updated'));
    }

    /**
     * @param Request $request
     */
    public function destroy(Request $request)
    {
        $query = Competitor::byWorkspace(WorkspaceManager::current())
            ->where('uuid', $request->route('competitor'))
            ->delete();

        if (!$query) {
            return redirect()
                ->route('genie.competitors.index', ['workspace' => $request->route('workspace')])
                ->with('error', __('genie.competitor_not_found'));
        }

        return redirect()->route('genie.competitors.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.competitor_deleted'));
    }
}
