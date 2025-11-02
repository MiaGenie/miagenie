<?php

namespace App\Concerns\Controller;

use App\Enums\GenieSyncAction;
use App\Enums\RuleType;
use App\Enums\RunStatus;
use App\Jobs\RunPrePostJob;
use App\Models\Draft;
use App\Models\Rule;
use App\Models\Run;
use App\Models\Strategy;
use App\Models\WorkspaceVersion;
use Illuminate\Database\Eloquent\Collection;
use Inovector\Mixpost\Facades\WorkspaceManager;

trait PrePostGeneration
{

    /**
     * @param Collection<Draft> $drafts
     */
    private function prePostGeneration(Collection $drafts): void
    {
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

    }
}
