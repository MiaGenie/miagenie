<?php

namespace App\Http\Controllers\Workspace;

use App\Models\Idea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DeleteIdeasController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $delete = Idea::whereIn('uuid', $request->input('ideas'))->delete();

        if (!$delete) {
            return redirect()
                ->route('genie.ideas.index', ['workspace' => $request->route('workspace')])
                ->with('error', __('genie.delete_ideas_failed'));
        }

        return redirect()
            ->route('genie.ideas.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.delete_ideas_success'));
    }
}
