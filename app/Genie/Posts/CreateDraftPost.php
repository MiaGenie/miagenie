<?php

namespace App\Genie\Posts;

use App\Enums\GeniePostStatus;
use App\Enums\IdeaStatus;
use App\Enums\Modality;
use App\Enums\RuleSubType;
use App\Enums\RuleType;
use App\Enums\RunStatus;
use App\Jobs\AiRunJob;
use App\Models\AiRun;
use App\Models\GeniePost;
use App\Models\Idea;
use App\Models\Rule;
use App\Models\WorkspaceVersion;
use Illuminate\Support\Facades\DB;
use Inovector\Mixpost\Enums\PostStatus;
use Inovector\Mixpost\Events\Post\PostCreated;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Inovector\Mixpost\Models\Account;
use Inovector\Mixpost\Models\Post;

/**
 * Turns an approved idea into a Mixpost draft and starts the run that fills it.
 *
 * The draft exists before the run so the user lands on the editor at once and Mixpost's list
 * shows it: accounts from the idea's publish_to, one empty original version, the Genie tag. The
 * Genie post row tracks the production; the run's parameters point at both.
 */
class CreateDraftPost
{
    public function __construct(protected PostsStepGate $gate = new PostsStepGate) {}

    /**
     * @throws PostsException when the idea is not approved, already has a post, or no step can generate for it
     */
    public function forIdea(Idea $idea, int $userId): GeniePost
    {
        if ($idea->status !== IdeaStatus::APPROVED) {
            throw PostsException::ideaNotApproved();
        }

        if ($idea->post()->exists()) {
            throw PostsException::alreadyExists();
        }

        $workspace = WorkspaceManager::current();

        $rule = $this->rule($workspace->id);

        [$post, $run] = DB::transaction(function () use ($idea, $userId, $workspace, $rule) {
            $mixpostPost = Post::create([
                'user_id' => $userId,
                'status' => PostStatus::DRAFT,
                'scheduled_at' => null,
            ]);

            $mixpostPost->accounts()->attach(
                Account::query()->whereIn('provider', $idea->publish_to ?? [])->pluck('id')->all()
            );

            $mixpostPost->versions()->create([
                'account_id' => 0,
                'is_original' => true,
                'content' => [['body' => '', 'media' => [], 'url' => null, 'video_thumbs' => []]],
                'options' => MixpostVersionOptions::defaults($idea->theme),
            ]);

            $mixpostPost->tags()->attach(GenieTag::resolve()->id);

            $post = GeniePost::create([
                'workspace_id' => $workspace->id,
                'idea_id' => $idea->id,
                'post_id' => $mixpostPost->id,
                'post_format' => $idea->post_format,
                'status' => GeniePostStatus::GENERATING,
            ]);

            $run = $this->run($post, $rule, new PostsRequest($idea->id, $post->id, $idea->post_format));

            $post->update(['run_id' => $run->id]);

            return [$post->setRelation('mixpostPost', $mixpostPost), $run];
        });

        PostCreated::dispatch($post->mixpostPost);

        AiRunJob::dispatch($run);

        return $post;
    }

    /**
     * Redo the caption of an existing post with the reviewer's feedback, as a step of the post's
     * own run.
     *
     * A post has one run. The first pass recorded the revise-text step as SKIPPED; regenerating
     * rewrites the run's parameters (`only = text`, the feedback), reopens that step and dispatches
     * the same run again, so the loop executes the reopened step inside the run's conversation —
     * the model sees the caption it wrote and the feedback on it — and the admin sees every
     * regeneration as a step of that run.
     *
     * @throws PostsException when the post is busy or not ready, or the rule has no revise-text step
     */
    public function regenerate(GeniePost $post, ?string $only, ?string $feedback): AiRun
    {
        if ($post->isBusy()) {
            throw PostsException::busy();
        }

        $run = $post->run ? AiRun::withoutWorkspace()->find($post->run->id) : null;

        if (! $run || $post->status !== GeniePostStatus::READY) {
            throw PostsException::notReady();
        }

        $step = $post->stepFor(RuleSubType::POSTS_REVISE_TEXT) ?? throw PostsException::noReviseTextStep();

        $request = new PostsRequest($post->idea?->id ?? 0, $post->id, $post->post_format, $only, $feedback);

        DB::transaction(function () use ($post, $run, $step, $request) {
            $run->update([
                'parameters' => $request->toArray(),
                'status' => RunStatus::RUNNING,
                'completed_at' => null,
            ]);

            $run->steps()->updateOrCreate(
                ['step_id' => $step->id],
                [
                    'position' => $step->position,
                    'modality' => $step->modality ?? Modality::TEXT,
                    'status' => RunStatus::OPEN,
                    'error' => null,
                    'error_details' => null,
                ],
            );

            $post->update(['status' => GeniePostStatus::GENERATING, 'error' => null]);
        });

        AiRunJob::dispatch($run->fresh());

        return $run;
    }

    /**
     * @throws PostsException when the workspace's version has no POSTS rule
     */
    protected function rule(int $workspaceId): Rule
    {
        $version = WorkspaceVersion::where('workspace_id', $workspaceId)->first();

        $rule = $version
            ? Rule::where('version_id', $version->version_id)->where('rule_type', RuleType::POSTS)->first()
            : null;

        return $rule ?? throw PostsException::noRule();
    }

    /**
     * Build and save the post's run, refusing first when no step would run for the request.
     */
    protected function run(GeniePost $post, Rule $rule, PostsRequest $request): AiRun
    {
        $run = new AiRun([
            'workspace_id' => $post->workspace_id,
            'rule_id' => $rule->id,
            'strategy_id' => $post->idea?->strategy_id,
            'parameters' => $request->toArray(),
            'status' => RunStatus::OPEN,
        ]);

        $run->setRelation('rule', $rule);

        if ($this->gate->generators($run)->isEmpty()) {
            throw PostsException::noGeneratorForFormat();
        }

        $run->save();

        return $run;
    }
}
