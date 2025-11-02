<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\GenieSyncAction;
use App\Enums\RuleType;
use App\Enums\RunStatus;
use App\Jobs\RunPrePostJob;
use App\Models\Draft;
use App\Models\Rule;
use App\Models\Run;
use App\Models\Strategy;
use App\Models\WorkspaceVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Facades\WorkspaceManager;

class GeneratePrePostsController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $drafts = Draft::whereIn('uuid', $request->input('drafts'));

        $workspace = WorkspaceManager::current();
        $workspaceVersion = WorkspaceVersion::where('workspace_id', $workspace->id)->first();

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

        $drafts->each(function (Draft $draft) use ($run) {
            $run->runDrafts()->create(['draft_id' => $draft->id]);
        });

        RunPrePostJob::dispatch($run, GenieSyncAction::CREATE);

        return redirect()
            ->route('genie.drafts.index', ['workspace' => $request->route('workspace')])
            ->with('success', __('genie.generating_pre_posts'));
    }
}
