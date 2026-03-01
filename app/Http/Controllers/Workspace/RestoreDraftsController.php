<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\DraftStatus;
use App\Models\Draft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Facades\WorkspaceManager;

class RestoreDraftsController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $records = Draft::whereIn('uuid', $request->input('drafts'))->byWorkspace(WorkspaceManager::current());

        $filter = $request->input('filter') ?? [];

        $records->update(['status' => DraftStatus::PENDING_REVIEW]);

        return redirect()
            ->route('genie.drafts.index', ['workspace' => $request->route('workspace')] + $filter)
            ->with('success', __('genie.restore_drafts_success'));
    }
}
