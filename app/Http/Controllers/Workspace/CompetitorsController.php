<?php

namespace App\Http\Controllers\Workspace;

use App\Concerns\Controller\HasFieldOptions;
use App\Enums\FormFieldFileType;
use App\Enums\FormFieldType;
use App\Enums\FormInputType;
use App\Http\Requests\Workspace\Competitor\StoreCompetitor;
use App\Http\Requests\Workspace\Competitor\UpdateCompetitor;
use App\Http\Resources\CompetitorResource;
use App\Models\Competitor;
use App\Models\WorkspaceVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Facades\WorkspaceManager;

class CompetitorsController extends Controller
{
    use HasFieldOptions;

    /**
     * @return Response
     */
    public function index()
    {
        $records = Competitor::query()
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        $fieldList = WorkspaceVersion::where('workspace_id', WorkspaceManager::current()->id)
            ->with(['version' => ['competitors']])
            ->firstOrFail()
            ->version
            ->competitors
            ->toArray();

        return Inertia::render('Genie/Workspace/Competitors/Index', [
            'records' => fn () => CompetitorResource::collection($records),
            'fieldList' => $fieldList,
        ]);
    }

    /**
     * @return Response
     */
    public function create()
    {
        $fieldList = WorkspaceVersion::where('workspace_id', WorkspaceManager::current()->id)
            ->with(['version' => ['competitors' => ['options']]])
            ->firstOrFail()
            ->version
            ->toArray();

        $fieldList['competitors'] = $this->groupFieldOptions($fieldList['competitors']);

        return Inertia::render('Genie/Workspace/Competitors/CreateEdit', [
            'mode' => 'create',
            'fieldList' => $fieldList,
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'fileTypes' => FormFieldFileType::withTitle(),
            'inputTypes' => FormInputType::withInputOptions(),
            'record' => null,
        ]);
    }

    /**
     * @param StoreCompetitor $storeCompetitor
     * @return RedirectResponse
     */
    public function store(StoreCompetitor $storeCompetitor)
    {
        $record = $storeCompetitor->handle();

        return redirect()->route(
            'genie.competitors.edit',
            [
                'workspace' => WorkspaceManager::current()->uuid,
                'competitor' => $record->uuid,
            ]
        )->with('success', __('genie.competitor_created'));
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function edit(Request $request)
    {
        $record = Competitor::firstOrFailByUuid($request->route('competitor'));

        $fieldList = WorkspaceVersion::where('workspace_id', WorkspaceManager::current()->id)
            ->with(['version' => ['competitors' => ['options']]])
            ->firstOrFail()
            ->version
            ->toArray();

        $fieldList['competitors'] = $this->groupFieldOptions($fieldList['competitors']);

        return Inertia::render('Genie/Workspace/Competitors/CreateEdit', [
            'mode' => 'edit',
            'fieldList' => $fieldList,
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'fileTypes' => FormFieldFileType::withTitle(),
            'inputTypes' => FormInputType::withInputOptions(),
            'record' => new CompetitorResource($record),
        ]);
    }

    /**
     * @param UpdateCompetitor $updateCompetitor
     * @return RedirectResponse
     */
    public function update(UpdateCompetitor $updateCompetitor)
    {
        $updateCompetitor->handle();

        return redirect()->back()->with('success', __('genie.competitor_updated'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        $query = Competitor::byWorkspace(WorkspaceManager::current())
            ->where('uuid', $request->route('competitor'))
            ->delete();

        if (! $query) {
            return redirect()
                ->route('genie.config.config', ['workspace' => $request->route('workspace')])
                ->with('error', __('genie.competitor_not_found'));
        }

        return redirect()->route('genie.config.config', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.competitor_deleted'));
    }
}
