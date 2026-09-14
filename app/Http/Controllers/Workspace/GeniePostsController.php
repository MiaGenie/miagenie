<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\GeniePostStatus;
use App\Enums\ImageRevisionMode;
use App\Enums\RuleSubType;
use App\Enums\RuleType;
use App\Genie\Images\ImageStore;
use App\Genie\Posts\CreateDraftPost;
use App\Genie\Posts\PostMediaSync;
use App\Genie\Posts\PostsException;
use App\Genie\Posts\PostsRequest;
use App\Genie\Runs\Runners;
use App\Http\Requests\Workspace\Post\RegeneratePostImage;
use App\Http\Requests\Workspace\Post\RegeneratePostText;
use App\Jobs\AiRunJob;
use App\Jobs\RevisePostImageJob;
use App\Models\AiRunStepFile;
use App\Models\GeniePost;
use App\Models\Idea;
use App\Models\WorkspaceFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inovector\Mixpost\Events\Post\PostUpdated;
use Inovector\Mixpost\Facades\WorkspaceManager;

/**
 * Starts and redoes the generation of a post; the post itself is edited in Mixpost's editor.
 */
class GeniePostsController extends Controller
{
    public function __construct(protected CreateDraftPost $drafts) {}

    /**
     * Create the Mixpost draft for an idea, start the run, and open the editor.
     */
    public function generate(Request $request): RedirectResponse
    {
        $idea = Idea::firstOrFailByUuid($request->route('idea'));

        try {
            $post = $this->drafts->forIdea($idea, Auth::id());
        } catch (PostsException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->route('mixpost.posts.edit', [
            'workspace' => WorkspaceManager::current()->uuid,
            'post' => $post->mixpostPost->uuid,
        ])->with('success', __('genie.post_created'));
    }

    public function regenerateText(RegeneratePostText $request): RedirectResponse
    {
        $post = $this->post($request);

        try {
            $this->drafts->regenerate($post, PostsRequest::ONLY_TEXT, $request->feedback());
        } catch (PostsException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('success', __('genie.post_regeneration_started'));
    }

    public function regenerateImage(RegeneratePostImage $request, ImageStore $store): RedirectResponse
    {
        $post = $this->post($request);

        if ($post->isBusy()) {
            return redirect()->back()->with('error', __('genie.post_busy'));
        }

        $image = $this->image($post, $request->route('file'));

        if (! $image) {
            return redirect()->back()->with('error', __('genie.post_image_missing'));
        }

        // No fallback: the rule must carry a prompt written for this exact action.
        $mode = $request->mode();
        $template = $post->stepFor($mode === ImageRevisionMode::FIX ? RuleSubType::POSTS_IMAGE_FIX : RuleSubType::POSTS_IMAGE_RETHINK);

        if (! $template) {
            return redirect()->back()->with('error', __($mode === ImageRevisionMode::FIX ? 'genie.post_no_image_fix_step' : 'genie.post_no_image_rethink_step'));
        }

        // Busy before the job even starts: the response that follows must already say so, or the
        // editor starts no poll and never learns the new image landed.
        $store->markRevising($image);

        RevisePostImageJob::dispatch($post, $image, $template, $mode, $request->feedback(), $request->brief());

        return redirect()->back()->with('success', __('genie.post_image_regeneration_started'));
    }

    /**
     * Put a generated image the user removed from the post back at its slot.
     */
    public function reattachImage(Request $request, PostMediaSync $sync): RedirectResponse
    {
        $post = $this->post($request);

        $image = $this->image($post, $request->route('file'));

        if (! $image) {
            return redirect()->back()->with('error', __('genie.post_image_missing'));
        }

        $sync->reattach($post, (int) $image->position);

        PostUpdated::dispatch($post->mixpostPost);

        return redirect()->back()->with('success', __('genie.post_image_reattached'));
    }

    /**
     * Pick a failed run back up where it stopped.
     */
    public function retry(Request $request): RedirectResponse
    {
        $post = $this->post($request);

        $run = $post->run;

        if ($post->status !== GeniePostStatus::FAILED || ! $run || $run->rule?->rule_type !== RuleType::POSTS) {
            return redirect()->back()->with('error', __('genie.post_not_failed'));
        }

        Runners::for(RuleType::POSTS)->retryFailed($run);

        $post->update(['status' => GeniePostStatus::GENERATING, 'error' => null]);

        AiRunJob::dispatch($run->fresh());

        return redirect()->back()->with('success', __('genie.post_regeneration_started'));
    }

    protected function post(Request $request): GeniePost
    {
        return GeniePost::firstOrFailByUuid($request->route('post'));
    }

    /**
     * The slot row of one of the post's generated files, by the file's uuid.
     */
    protected function image(GeniePost $post, string $fileUuid): ?AiRunStepFile
    {
        $file = WorkspaceFile::where('uuid', $fileUuid)->first();

        if (! $file || ! in_array($file->id, array_column($post->images ?? [], 'file_id'), true)) {
            return null;
        }

        return AiRunStepFile::where('workspace_file_id', $file->id)->current()->latest('id')->first();
    }
}
