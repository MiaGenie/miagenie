<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\GenieSyncAction;
use App\Enums\RuleType;
use App\Enums\RunStatus;
use App\Jobs\RunPrePostJob;
use App\Models\PrePost;
use App\Models\Rule;
use App\Models\Run;
use App\Models\Strategy;
use App\Models\WorkspaceVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inovector\Mixpost\Enums\PostStatus;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Inovector\Mixpost\Models\Post;

class GeneratePostsController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $prePosts = PrePost::whereIn('uuid', $request->input('pre_posts'));

        $workspace = WorkspaceManager::current();
        $workspaceVersion = WorkspaceVersion::where('workspace_id', $workspace->id)->first();

        $prePosts->each(function (PrePost $prePost) use ($workspace) {

            DB::transaction(function () use ($prePost, $workspace) {

                $post = Post::create([
                    'user_id' => $workspace->owner_id,
                    'status' => PostStatus::DRAFT,
                    'scheduled_at' => null,
                ]);

                $post->versions()->create([
                    'account_id' => 0,
                    'is_original' => 0,
                    'content' => [
                        'body' => $prePost->caption ?? '',
                        'media' => [],
                        'url' => null,
                    ],
                    'options' => []
                ]);

            });

        });


        $data = Arr::map($prePosts->toArray(), function ($prePost) {
            return [
                'account_id' => 0,
                'is_original' => 0,
                'content' => [
                    'body' => $prePost['caption'] ?? '',
                    'media' => [],
                    'url' => null,
                ],
                'options' => []
            ];
        });

        $post = DB::transaction(function () use ($data, $workspace) {
            $record = Post::create([
                'user_id' => $workspace->owner_id,
                'status' => PostStatus::DRAFT,
                'scheduled_at' => $this->getScheduledAt(),
            ]);

            $record->versions()->createMany($this->inputVersions());

            return $record;
        });

        $rule = Rule::where('version_id', $workspaceVersion->version_id)->where('rule_type', RuleType::PRE_POSTS)->first();

        $run = Run::create([
            'workspace_id' => $workspace->id,
            'rule_id' => $rule->id,
            'status' => RunStatus::OPEN,
        ]);

        $strategy = Strategy::whereHas('workspace', function ($query) use ($workspace) {
            $query->where('id', $workspace->id);
        })->latest()->first();

        $run->runStrategy()->create([
            'strategy_id' => $strategy->id
        ]);

        $prePosts->each(function (PrePost $draft) use ($run) {
            $run->runDrafts()->create(['draft_id' => $draft->id]);
        });

        RunPrePostJob::dispatch($run, GenieSyncAction::CREATE);

        return redirect()
            ->route('genie.drafts.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.generating_pre_posts'));
    }
}
