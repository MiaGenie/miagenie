<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\IdeaStatus;
use App\Models\Idea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Facades\WorkspaceManager;

class RestoreIdeasController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $records = Idea::whereIn('uuid', $request->input('ideas'))->byWorkspace(WorkspaceManager::current());

        $filter = $request->input('filter') ?? [];

        $records->update(['status' => IdeaStatus::PENDING_REVIEW]);

        return redirect()
            ->route('genie.ideas.index', ['workspace' => $request->route('workspace')] + $filter)
            ->with('success', __('genie.restore_ideas_success'));
    }
}
