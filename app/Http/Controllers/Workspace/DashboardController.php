<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Resources\DashboardPostResource;
use App\Models\Version;
use App\Models\WorkspaceVersion;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Enums\PostStatus;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Inovector\Mixpost\Http\Base\Resources\AccountResource;
use Inovector\Mixpost\Models\Account;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Support\EagerLoadPostVersionsMedia;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $workspace = Auth::user()->getActiveWorkspace();
        $workspaceVersion = WorkspaceVersion::byWorkspace(WorkspaceManager::current())->first();

        if (!$workspaceVersion) {
            $version = Version::where('is_default', true)->first();
            WorkspaceVersion::create([
                'workspace_id' => $workspace->id,
                'version_id' => $version->id,
            ]);
        }

        $postsPending = Post::with('accounts', 'user', 'versions', 'tags')
            ->whereIn('status', [PostStatus::DRAFT, PostStatus::NEEDS_APPROVAL])
            ->latest()
            ->paginate(5)
            ->onEachSide(1)
            ->withQueryString();
        $postsScheduled = Post::with('accounts', 'user', 'versions', 'tags')
            ->where(['status' => PostStatus::SCHEDULED])
            ->latest()
            ->paginate(5)
            ->onEachSide(1)
            ->withQueryString();

        EagerLoadPostVersionsMedia::apply($postsScheduled);

        return Inertia::render('Genie/Workspace/Dashboard', [
            'accounts' => AccountResource::collection(Account::oldest()->get())->resolve(),
            'posts_pending_review' => DashboardPostResource::collection($postsPending)->additional([
                'filter' => [
                    'accounts' => Arr::map($request->query('accounts', []), 'intval')
                ]
            ]),
            'posts_scheduled' => DashboardPostResource::collection($postsScheduled)->additional([
                'filter' => [
                    'accounts' => Arr::map($request->query('accounts', []), 'intval')
                ]
            ]),
        ]);
    }
}
