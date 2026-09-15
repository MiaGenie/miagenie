<?php

namespace App\Http\Controllers\Workspace;

use App\Genie\Posts\GenieTag;
use App\Http\Resources\GeniePostResource;
use App\Models\GeniePost;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Configs\GeneralConfig;
use Inovector\Mixpost\Configs\MediaConfig;
use Inovector\Mixpost\Facades\AIManager;
use Inovector\Mixpost\Facades\ServiceManager;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Inovector\Mixpost\Http\Base\Requests\Workspace\Post\UpdatePost;
use Inovector\Mixpost\Http\Base\Resources\AccountResource;
use Inovector\Mixpost\Http\Base\Resources\PostResource;
use Inovector\Mixpost\Http\Base\Resources\TagResource;
use Inovector\Mixpost\Models\Account;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Models\Tag;
use Inovector\Mixpost\PostingSchedule;
use Inovector\Mixpost\Support\EagerLoadPostVersionsMedia;

class PostsController extends Controller
{
    /**
     * Save the post as the package does, with one correction: the Genie tag marks the posts Genie
     * created and is not the user's to add or remove, so its presence in `tags` follows whether the
     * post has a Genie record, whatever the form sent.
     */
    public function update(UpdatePost $updatePost): PostResource
    {
        $genieTagId = Tag::where('name', GenieTag::NAME)->value('id');

        if ($genieTagId) {
            $tags = array_values(array_filter(
                array_map('intval', (array) $updatePost->input('tags', [])),
                fn (int $id) => $id !== (int) $genieTagId,
            ));

            if (GeniePost::where('post_id', $updatePost->getPost()->id)->exists()) {
                $tags[] = (int) $genieTagId;
            }

            $updatePost->merge(['tags' => $tags]);
        }

        $updatePost->handle();

        // PostResource serialises tags only when loaded; the editor reads them back to correct its picker.
        return new PostResource($updatePost->getPost()->load('tags'));
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Mixpost/Workspace/Posts/CreateEdit', [
            //            'default_accounts' => Settings::get('default_accounts'),
            'user_can_approve' => Auth::user()->canApprove(WorkspaceManager::current()),
            'accounts' => AccountResource::collection(Account::oldest()->get())->resolve(),
            'tags' => TagResource::collection(Tag::latest()->get())->resolve(),
            'has_available_times' => PostingSchedule::hasAvailableTimes(),
            'post' => null,
            'scheduleAt' => [
                'date' => Str::before($request->route('schedule_at'), ' '),
                'time' => Str::after($request->route('schedule_at'), ' '),
            ],
            'prefill' => [
                'body' => $request->query('body', ''),
                'title' => $request->query('title', ''),
                'link' => $request->query('link', ''),
            ],
            'is_configured_service' => ServiceManager::isActive(),
            'service_configs' => ServiceManager::exposedConfiguration(),
            'general_configs' => (new GeneralConfig)->all(),
            'ai_is_ready_to_use' => AIManager::isReadyToUse(),
            'stock_photo_provider' => app(MediaConfig::class)->get('stock_photo_provider'),
        ]);
    }

    public function edit(Request $request): Response
    {
        $post = Post::firstOrFailTrashedByUuid($request->route('post'));

        $post->load('accounts', 'versions', 'tags');

        EagerLoadPostVersionsMedia::apply($post);

        // Set when Genie produced this post from an idea; the editor then shows the Genie panel.
        $geniePost = GeniePost::with(['idea', 'mixpostPost'])->where('post_id', $post->id)->first();

        return Inertia::render('Mixpost/Workspace/Posts/CreateEdit', [
            'user_can_approve' => Auth::user()->canApprove(WorkspaceManager::current()),
            'accounts' => AccountResource::collection(Account::oldest()->get())->resolve(),
            'tags' => TagResource::collection(Tag::latest()->get())->resolve(),
            'has_available_times' => PostingSchedule::hasAvailableTimes(),
            'post' => new PostResource($post),
            'has_activities_ns' => $post->hasNotificationSubscriptionForActivities(user: Auth::id()),
            'is_configured_service' => ServiceManager::isActive(),
            'service_configs' => ServiceManager::exposedConfiguration(),
            'general_configs' => (new GeneralConfig)->all(), // TODO: Use specific config. Not all configs are needed here.
            'ai_is_ready_to_use' => AIManager::isReadyToUse(),
            'stock_photo_provider' => app(MediaConfig::class)->get('stock_photo_provider'),
            'geniePost' => $geniePost ? new GeniePostResource($geniePost) : null,
        ]);
    }
}
