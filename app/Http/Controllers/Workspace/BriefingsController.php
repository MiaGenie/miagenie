<?php

namespace App\Http\Controllers\Workspace;

use App\Concerns\Controller\HasFieldOptions;
use App\Enums\FormFieldFileType;
use App\Enums\FormFieldType;
use App\Enums\FormInputType;
use App\Http\Requests\Workspace\Briefing\StoreBriefing;
use App\Http\Requests\Workspace\Briefing\UpdateBriefing;
use App\Http\Resources\BriefingResource;
use App\Models\Briefing;
use App\Models\WorkspaceVersion;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Facades\WorkspaceManager;

class BriefingsController extends Controller
{
    use HasFieldOptions;

    /**
     * @return Response
     */
    public function index()
    {

        $records = Briefing::query()
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        $fieldList = WorkspaceVersion::byWorkspace(WorkspaceManager::current())
            ->with(['version' => ['briefings']])
            ->firstOrFail()
            ->version
            ->briefings
            ->toArray();

        return Inertia::render('Genie/Workspace/Briefings/Index', [
            'records' => BriefingResource::collection($records),
            'fieldList' => $fieldList
        ]);
    }

    /**
     * @return Response
     */
    public function create()
    {
        $fieldList = WorkspaceVersion::byWorkspace(WorkspaceManager::current())
            ->with(['version' => ['briefings' => ['options']]])
            ->firstOrFail()
            ->version
            ->toArray();

        $fieldList['briefings'] = $this->groupFieldOptions($fieldList['briefings']);

        return Inertia::render('Genie/Workspace/Briefings/CreateEdit', [
            'mode' => 'create',
            'fieldList' => $fieldList,
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'fileTypes' => FormFieldFileType::withTitle(),
            'inputTypes' => FormInputType::withInputOptions(),
            'record' => null
        ]);
    }

    /**
     * @param StoreBriefing $storeBriefing
     */
    public function store(StoreBriefing $storeBriefing)
    {
        $record = $storeBriefing->handle();

        return redirect()->route(
            'genie.briefings.edit',
            [
                'workspace' => WorkspaceManager::current()->uuid,
                'briefing' => $record->uuid
            ]
        )->with('success', __('genie.briefing_created'));
    }

    /**
     * @param Request $request
     */
    public function edit(Request $request)
    {
        $record = Briefing::firstOrFailByUuid($request->route('briefing'));

        $fieldList = WorkspaceVersion::byWorkspace(WorkspaceManager::current())
            ->with(['version' => ['briefings' => ['options']]])
            ->firstOrFail()
            ->version
            ->toArray();

        $fieldList['briefings'] = $this->groupFieldOptions($fieldList['briefings']);

        return Inertia::render('Genie/Workspace/Briefings/CreateEdit', [
            'mode' => 'edit',
            'fieldList' => $fieldList,
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'fileTypes' => FormFieldFileType::withTitle(),
            'inputTypes' => FormInputType::withInputOptions(),
            'record' => new BriefingResource($record)
        ]);
    }

    /**
     * @param UpdateBriefing $updateBriefing
     */
    public function update(UpdateBriefing $updateBriefing)
    {
        $updateBriefing->handle();

        return redirect()->back()->with('success', __('genie.briefing_updated'));
    }

    /**
     * @param Request $request
     */
    public function destroy(Request $request)
    {
        $query = Briefing::byWorkspace(WorkspaceManager::current())
            ->where('uuid', $request->route('briefing'))
            ->delete();

        if (!$query) {
            return redirect()
                ->route('genie.briefings.index', ['workspace' => $request->route('workspace')])
                ->with('error', __('genie.briefing_not_found'));
        }

        return redirect()->route('genie.briefings.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.briefing_deleted'));
    }
}
