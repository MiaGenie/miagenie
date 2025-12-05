<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Resources\DraftResource;
use App\Models\Draft;
use App\Models\PrePost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Actions\Post\RedirectAfterDeletedPost;
use Inovector\Mixpost\Builders\Post\PostQuery;
use Inovector\Mixpost\Events\Post\PostDeleted;
use Inovector\Mixpost\Facades\AIManager;
use Inovector\Mixpost\Facades\ServiceManager;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Inovector\Mixpost\Http\Base\Requests\Workspace\Post\StorePost;
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
    public function edit(Request $request): Response
    {
        $post = Post::firstOrFailTrashedByUuid($request->route('post'));

        $post->load('accounts', 'versions', 'tags');

        EagerLoadPostVersionsMedia::apply($post);

        $draft = PrePost::where(['post_id' => $post->id])->first()?->draft;

        return Inertia::render('Genie/Workspace/Posts/CreateEdit', [
            'user_can_approve' => Auth::user()->canApprove(WorkspaceManager::current()),
            'accounts' => AccountResource::collection(Account::oldest()->get())->resolve(),
            'tags' => TagResource::collection(Tag::latest()->get())->resolve(),
            'has_available_times' => PostingSchedule::hasAvailableTimes(),
            'post' => new PostResource($post),
            'has_activities_ns' => $post->hasNotificationSubscriptionForActivities(user: Auth::id()),
            'is_configured_service' => ServiceManager::isActive(),
            'service_configs' => ServiceManager::exposedConfiguration(),
            'ai_is_ready_to_use' => AIManager::isReadyToUse(),
            'draft' => $draft ? new DraftResource($draft) : null,
        ]);
    }

}
