<?php

namespace App\Http\Controllers\Workspace;

use App\Concerns\Controller\HasFieldOptions;
use App\Concerns\Controller\HasWorkspaceLocale;
use App\Enums\FormFieldFileType;
use App\Enums\FormFieldType;
use App\Enums\FormInputType;
use App\Http\Requests\Workspace\Briefing\SaveBriefingWizard;
use App\Http\Requests\Workspace\Briefing\StoreBriefing;
use App\Http\Requests\Workspace\Briefing\UpdateBriefing;
use App\Http\Resources\BriefingResource;
use App\Models\Briefing;
use App\Models\WorkspaceVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Facades\WorkspaceManager;

class BriefingsController extends Controller
{
    use HasFieldOptions;
    use HasWorkspaceLocale;

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

        $version = WorkspaceVersion::where('workspace_id', WorkspaceManager::current()->id)
            ->with(['version' => ['briefings']])
            ->firstOrFail()
            ->version;

        $fieldList = $this->localizedFields($version->briefings);

        return Inertia::render('Genie/Workspace/Briefings/Index', [
            'records' => BriefingResource::collection($records),
            'fieldList' => $fieldList,
        ]);
    }

    /**
     * @return Response
     */
    public function create()
    {
        $version = WorkspaceVersion::where('workspace_id', WorkspaceManager::current()->id)
            ->with(['version' => ['briefings' => ['options']]])
            ->firstOrFail()
            ->version;

        $fieldList = $version->toArray();

        $fieldList['briefings'] = $this->groupFieldOptions($this->localizedFields($version->briefings));

        return Inertia::render('Genie/Workspace/Briefings/CreateEdit', [
            'mode' => 'create',
            'fieldList' => $fieldList,
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'fileTypes' => FormFieldFileType::withTitle(),
            'inputTypes' => FormInputType::withInputOptions(),
            'record' => null,
        ]);
    }

    /**
     * The guided, one-question-at-a-time briefing. A workspace holds a single briefing, so an
     * existing one is edited rather than a second being created.
     *
     * @return Response
     */
    public function wizard()
    {
        $record = Briefing::latest()->first();

        $version = WorkspaceVersion::where('workspace_id', WorkspaceManager::current()->id)
            ->with(['version' => ['briefings' => ['options']]])
            ->firstOrFail()
            ->version;

        $fieldList = $version->toArray();

        $fieldList['briefings'] = $this->groupFieldOptions($this->localizedFields($version->briefings));

        return Inertia::render('Genie/Workspace/Briefings/Wizard', [
            'mode' => $record ? 'edit' : 'create',
            'fieldList' => $fieldList,
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'fileTypes' => FormFieldFileType::withTitle(),
            'inputTypes' => FormInputType::withInputOptions(),
            'record' => $record ? new BriefingResource($record) : null,
        ]);
    }

    /**
     * Write what the wizard has so far.
     *
     * A draft answers back without a flash: this runs on every question, and Notifications.vue
     * toasts whatever it finds in the flash bag.
     */
    public function wizardSave(SaveBriefingWizard $request): RedirectResponse
    {
        $record = $request->handle();

        if ($request->boolean('draft')) {
            return redirect()->back();
        }

        return redirect()->back()->with(
            'success',
            __($record->wasRecentlyCreated ? 'genie.briefing_created' : 'genie.briefing_updated')
        );
    }

    public function store(StoreBriefing $storeBriefing)
    {
        $record = $storeBriefing->handle();

        return redirect()->route(
            'genie.briefings.edit',
            [
                'workspace' => WorkspaceManager::current()->uuid,
                'briefing' => $record->uuid,
            ]
        )->with('success', __('genie.briefing_created'));
    }

    public function edit(Request $request)
    {
        $record = Briefing::firstOrFailByUuid($request->route('briefing'));

        $version = WorkspaceVersion::where('workspace_id', WorkspaceManager::current()->id)
            ->with(['version' => ['briefings' => ['options']]])
            ->firstOrFail()
            ->version;

        $fieldList = $version->toArray();

        $fieldList['briefings'] = $this->groupFieldOptions($this->localizedFields($version->briefings));

        return Inertia::render('Genie/Workspace/Briefings/CreateEdit', [
            'mode' => 'edit',
            'fieldList' => $fieldList,
            'fieldTypes' => FormFieldType::withFieldOptions(),
            'fileTypes' => FormFieldFileType::withTitle(),
            'inputTypes' => FormInputType::withInputOptions(),
            'record' => new BriefingResource($record),
        ]);
    }

    public function update(UpdateBriefing $updateBriefing)
    {
        $updateBriefing->handle();

        return redirect()->back()->with('success', __('genie.briefing_updated'));
    }

    public function destroy(Request $request)
    {
        $query = Briefing::byWorkspace(WorkspaceManager::current())
            ->where('uuid', $request->route('briefing'))
            ->delete();

        if (! $query) {
            return redirect()
                ->route('genie.briefings.index', ['workspace' => $request->route('workspace')])
                ->with('error', __('genie.briefing_not_found'));
        }

        return redirect()->route('genie.briefings.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.briefing_deleted'));
    }
}
