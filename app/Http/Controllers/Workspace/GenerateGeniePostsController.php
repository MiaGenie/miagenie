<?php

namespace App\Http\Controllers\Workspace;

use App\Genie\Posts\CreateDraftPost;
use App\Genie\Posts\PostsException;
use App\Models\Idea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inovector\Mixpost\Facades\WorkspaceManager;

/**
 * Create one Mixpost draft per selected idea and start their runs.
 *
 * An idea that cannot be turned into a post — not approved, already has one — is reported and
 * skipped; the others go ahead.
 */
class GenerateGeniePostsController extends Controller
{
    public function __invoke(Request $request, CreateDraftPost $drafts): RedirectResponse
    {
        $ideas = Idea::whereIn('uuid', (array) $request->input('ideas', []))
            ->byWorkspace(WorkspaceManager::current())
            ->get();

        $images = $request->boolean('images', true);

        $created = 0;
        $errors = [];

        foreach ($ideas as $idea) {
            try {
                $drafts->forIdea($idea, Auth::id(), $images);
                $created++;
            } catch (PostsException $exception) {
                $errors[] = $idea->theme.': '.$exception->getMessage();
            }
        }

        $redirect = redirect()->route('genie.ideas.index', [
            'workspace' => $request->route('workspace'),
        ] + ($request->input('filter') ?? []));

        if ($created > 0) {
            $redirect->with('success', __('genie.posts_created', ['count' => $created]));
        }

        if ($errors !== []) {
            $redirect->with('error', implode(' ', $errors));
        }

        return $redirect;
    }
}
