<?php

namespace App\Http\Controllers\Workspace;

use App\Concerns\Controller\HasFieldOptions;
use App\Concerns\Controller\HasWorkspaceLocale;
use App\Enums\FormFieldFileType;
use App\Enums\FormFieldType;
use App\Enums\FormInputType;
use App\Http\Requests\Workspace\Briefing\SaveBriefingWizard;
use App\Http\Resources\BriefingResource;
use App\Models\Briefing;
use App\Models\WorkspaceVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Facades\WorkspaceManager;

class BriefingsController extends Controller
{
    use HasFieldOptions;
    use HasWorkspaceLocale;

    /**
     * The guided, one-question-at-a-time briefing. A workspace holds a single briefing, so an
     * existing one is edited rather than a second being created.
     */
    public function wizard(): Response
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
}
