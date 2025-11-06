<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\IdeaStatus;
use App\Models\Idea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Facades\WorkspaceManager;

class DeleteIdeasController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $records = Idea::whereIn('uuid', $request->input('ideas'))->byWorkspace(WorkspaceManager::current());

        $filter = $request->input('filter') ?? [];

        $redirect = redirect()->route(
            'genie.ideas.index',
            [
                'workspace' => $request->route('workspace'),
            ] + $filter
        );

        if ((int) ($filter['status'] ?? null) === IdeaStatus::TRASH->value) {

            $result = $records->delete();
            if ($result) {
                return $redirect->with('success', __('genie.delete_ideas_success'));
            }

        } else {

            $result = $records->update(['status' => IdeaStatus::TRASH]);
            if ($result) {
                return $redirect->with('success', __('genie.trash_ideas_success'));
            }
        }

        return $redirect->with('error', __('genie.delete_ideas_failed'));
    }
}
