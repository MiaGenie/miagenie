<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\DraftStatus;
use App\Models\Draft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Facades\WorkspaceManager;

class DeleteDraftsController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $records = Draft::whereIn('uuid', $request->input('drafts'))->byWorkspace(WorkspaceManager::current());

        $filter = $request->input('filter') ?? [];

        $redirect = redirect()->route(
            'genie.drafts.index',
            [
                'workspace' => $request->route('workspace'),
            ] + $filter
        );

        if ((int) ($filter['status'] ?? null) === DraftStatus::TRASH->value) {

            $result = $records->delete();
            if ($result) {
                return $redirect->with('success', __('genie.delete_drafts_success'));
            }

        } else {

            $result = $records->update(['status' => DraftStatus::TRASH]);
            if ($result) {
                return $redirect->with('success', __('genie.trash_drafts_success'));
            }
        }

        return redirect()
            ->route('genie.drafts.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.delete_drafts_success'));
    }
}
