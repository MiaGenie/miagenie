<?php

namespace App\Http\Controllers\Workspace;

use App\Concerns\Controller\HasFieldOptions;
use App\Enums\FormFieldType;
use App\Http\Requests\Workspace\Briefing\StoreBriefing;
use App\Http\Requests\Workspace\Briefing\UpdateBriefing;
use App\Http\Resources\BriefingResource;
use App\Models\Briefing;
use App\Models\WorkspaceVersion;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inovector\Mixpost\Facades\WorkspaceManager;

class BriefingsController extends Controller
{
    use HasFieldOptions;

    /**
     * @param Request $request
     */
    public function index(Request $request)
    {

        $records = Briefing::query()
            ->latest()
            ->paginate(20)
            ->onEachSide(1)
            ->withQueryString();

        $fieldList = WorkspaceVersion::byWorkspace(WorkspaceManager::current())
            ->with(['version' => ['briefings']])
            ->firstOrFail()
            ->version
            ->briefings
            ->toArray();

        return Inertia::render('Genie/Workspace/Briefings/Index', [
            'filter' => [
                'keyword' => $request->query('keyword', ''),
            ],
            'records' => fn () => BriefingResource::collection($records),
            'fieldList' => $fieldList
        ]);
    }

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
